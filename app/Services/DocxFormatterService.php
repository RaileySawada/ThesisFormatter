<?php
declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * DocxFormatterService
 *
 * Formats a DOCX manuscript (Chapters 1–References) per the official guidelines.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * COMPLETE SPEC (all values in Word XML units)
 * ═══════════════════════════════════════════════════════════════════════════════
 * ELEMENT                FONT      SZ  B  I  JC      IND(tw)          LINE  BEF  AFT
 * ───────────────────────────────────────────────────────────────────────────────
 * Chapter number         Garamond  28  Y  -  center  fl=0             480   0    0
 * Chapter title          Garamond  28  Y  -  center  fl=0             720   0    0
 * Preliminary title      Garamond  28  Y  -  center  fl=0             720   0    0
 * Appendix label         Garamond  28  Y  -  center  fl=0             240   0    0
 * Appendix title         Garamond  28  Y  -  center  fl=0             720   0    0
 * Heading (H2)           Garamond  26  Y  -  left    fl=0 l=0 r=0    480   0    0
 * Italic heading (Ch2)   Garamond  24  Y  Y  left    fl=0 l=0 r=0    480   0    0
 * Body paragraph         Garamond  24  -  -  both    fl=720 l=0 r=0  480   0    0
 * 1st para after table   Garamond  24  -  -  both    fl=720 l=0 r=0  480   240  0
 * Figure caption         Garamond  24  -  -  center  fl=0 l=0 r=0    480   0    0
 * Table caption          Garamond  24  -  -  left    fl=0 l=0 r=0    240   0    0
 * Legend                 Garamond  22  -  -  left    fl=0 l=0 r=0    240   0    0
 * Continuation label     Garamond  26  -  Y  left    fl=0 l=0 r=0    240   0    0
 * References title       Garamond  28  Y  -  center  fl=0             720   0    0
 * Reference entry        Garamond  22  -  -  both    l=720 hang=720  240   0    0
 * List paragraph         Garamond  26  -  -  both    (keep numPr)    480   0    0
 * Table cell             Arial     20  -  -  center  fl=0             240   0    0
 * Figure paragraph       (keep own formatting, add 3pt box border)
 * ───────────────────────────────────────────────────────────────────────────────
 * UNIT CONVERSIONS:
 *   1 pt  = 2 half-points (sz units)   14pt=28  13pt=26  12pt=24  11pt=22  10pt=20
 *   1 in  = 1440 twips                 1.27 cm = 720 twips (first-line / hanging indent)
 *   line  = 240xn  (lineRule="auto")   1.0=240  2.0=480  3.0=720
 *   12pt before spacing               = 240 twips  (1pt = 20 twips)
 *   border sz = 1/8 pt units          0.5pt=4  3pt=24
 *
 * TABLE BORDER RULES:
 *   - Top outer border    : double solid 0.5pt (sz=4)
 *   - Bottom outer border : double solid 0.5pt (sz=4)
 *   - Header row bottom   : single solid 0.5pt applied to bottom of each cell in row 1
 *   - Footer row top      : single solid 0.5pt applied to top of each cell in last row
 *   - Left/Right/insideV  : none
 *   - insideH             : none  (row separators handled per-cell on header/footer only)
 *
 * AFTER-TABLE FLOW:
 *   Table sets afterTable=true.
 *   Legend and Continuation do NOT reset afterTable — they are "table accessories".
 *   The first real body paragraph after the table (after any legend/continuation)
 *   receives 12pt (240 twips) before spacing, then afterTable is reset to false.
 * ═══════════════════════════════════════════════════════════════════════════════
 */
class DocxFormatterService
{
    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @var array<string, bool> */
    private array $sections = [];

    /** @var array<string, bool> */
    private array $rules = [];

    // ═══════════════════════════════════════════════════════════════════
    // Public API
    // ═══════════════════════════════════════════════════════════════════

    public function format(string $inputPath, string $outputPath, array $options = []): void
    {
        if (!is_file($inputPath)) {
            throw new RuntimeException('Input DOCX file does not exist.');
        }
        if (!copy($inputPath, $outputPath)) {
            throw new RuntimeException('Could not create working copy of uploaded DOCX.');
        }

        $this->sections = $this->normalizeMap($options['sections'] ?? [
            'preliminary', 'chapters', 'appendices',
        ]);
        $this->rules = $this->normalizeMap($options['rules'] ?? [
            'spacing', 'indentation', 'alignment', 'captions',
            'continuation', 'borders', 'pagination',
        ]);

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== true) {
            throw new RuntimeException('Could not open DOCX archive.');
        }
        try {
            $this->processMainDocument($zip);
            $this->patchStylesXml($zip);
        } finally {
            $zip->close();
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Normalisation
    // ═══════════════════════════════════════════════════════════════════

    /** @return array<string, bool> */
    private function normalizeMap(array $values): array
    {
        $map = [];
        foreach ($values as $v) {
            if (!is_scalar($v)) { continue; }
            $k = trim((string)$v);
            if ($k !== '') { $map[$k] = true; }
        }
        return $map;
    }

    // ═══════════════════════════════════════════════════════════════════
    // Patch styles.xml
    // ═══════════════════════════════════════════════════════════════════

    private function patchStylesXml(ZipArchive $zip): void
    {
        $xml = $zip->getFromName('word/styles.xml');
        if ($xml === false) { return; }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        if (!$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) { return; }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        $docDefaults = $xpath->query('//w:docDefaults/w:rPrDefault/w:rPr')->item(0);
        if ($docDefaults instanceof DOMElement) {
            $this->overrideRPrToGaramond($docDefaults, $dom, 24);
        }

        foreach ($xpath->query('//w:style[@w:styleId="Normal"]') as $style) {
            if (!$style instanceof DOMElement) { continue; }
            $rPr = $xpath->query('./w:rPr', $style)->item(0);
            if (!$rPr instanceof DOMElement) {
                $rPr = $dom->createElementNS(self::W_NS, 'w:rPr');
                $style->appendChild($rPr);
            }
            $this->overrideRPrToGaramond($rPr, $dom, 24);
        }

        foreach ($xpath->query('//w:style[@w:styleId="DefaultParagraphFont"]') as $style) {
            if (!$style instanceof DOMElement) { continue; }
            $rPr = $xpath->query('./w:rPr', $style)->item(0);
            if ($rPr instanceof DOMElement) {
                $this->overrideRPrToGaramond($rPr, $dom, 24);
            }
        }

        $headingIds = ['Heading1', 'Heading2', 'Heading3', 'Heading4',
                       '1', '2', '3', '4',
                       'heading1', 'heading2', 'heading3'];
        foreach ($headingIds as $hId) {
            foreach ($xpath->query(sprintf('//w:style[@w:styleId="%s"]', $hId)) as $style) {
                if (!$style instanceof DOMElement) { continue; }
                $rPr = $xpath->query('./w:rPr', $style)->item(0);
                if ($rPr instanceof DOMElement) { $style->removeChild($rPr); }
                foreach ($xpath->query('./w:pPr/w:rPr', $style) as $pPrRPr) {
                    if ($pPrRPr instanceof DOMElement && $pPrRPr->parentNode) {
                        $pPrRPr->parentNode->removeChild($pPrRPr);
                    }
                }
            }
        }

        foreach ($xpath->query('//w:style[@w:styleId="ListParagraph"]') as $style) {
            if (!$style instanceof DOMElement) { continue; }
            $rPr = $xpath->query('./w:rPr', $style)->item(0);
            if ($rPr instanceof DOMElement) { $style->removeChild($rPr); }
        }

        $zip->addFromString('word/styles.xml', $dom->saveXML() ?: $xml);
    }

    private function overrideRPrToGaramond(DOMElement $rPr, DOMDocument $dom, int $sz): void
    {
        $szStr = (string)$sz;
        foreach (['rFonts', 'sz', 'szCs'] as $tag) {
            $nodes = [];
            foreach ($rPr->childNodes as $child) {
                if ($child instanceof DOMElement
                    && $child->namespaceURI === self::W_NS
                    && $child->localName === $tag
                ) { $nodes[] = $child; }
            }
            foreach ($nodes as $n) { $rPr->removeChild($n); }
        }
        $rFonts = $dom->createElementNS(self::W_NS, 'w:rFonts');
        $rFonts->setAttributeNS(self::W_NS, 'w:ascii',    'Garamond');
        $rFonts->setAttributeNS(self::W_NS, 'w:hAnsi',    'Garamond');
        $rFonts->setAttributeNS(self::W_NS, 'w:eastAsia', 'Garamond');
        $rFonts->setAttributeNS(self::W_NS, 'w:cs',       'Garamond');
        $rPr->insertBefore($rFonts, $rPr->firstChild);
        $szEl   = $dom->createElementNS(self::W_NS, 'w:sz');
        $szElCs = $dom->createElementNS(self::W_NS, 'w:szCs');
        $szEl->setAttributeNS(self::W_NS,   'w:val', $szStr);
        $szElCs->setAttributeNS(self::W_NS, 'w:val', $szStr);
        $rPr->appendChild($szEl);
        $rPr->appendChild($szElCs);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Document processing
    // ═══════════════════════════════════════════════════════════════════

    private function processMainDocument(ZipArchive $zip): void
    {
        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            throw new RuntimeException('word/document.xml not found inside DOCX.');
        }

        [$dom, $xpath] = $this->loadXml($xml);

        $body = $xpath->query('/w:document/w:body')->item(0);
        if (!$body instanceof DOMElement) {
            throw new RuntimeException('DOCX body element not found.');
        }

        $state = [
            'zone'                => 'preliminary',
            'currentChapter'      => 0,
            'expectChapterTitle'  => false,
            'expectAppendixTitle' => false,
            'isFirstParagraph'    => true,
            'afterTable'          => false,
            'lastTableNumber'     => '',
        ];

        foreach ($body->childNodes as $child) {
            if (!$child instanceof DOMElement) { continue; }
            if ($child->localName === 'p') {
                $this->processParagraph($child, $xpath, $state);
            } elseif ($child->localName === 'tbl') {
                $this->processTable($child, $xpath, $state);
                $state['afterTable'] = true;

                // If this table has a lastRenderedPageBreak inside it (meaning
                // Word already broke it across pages) AND it is NOT already
                // followed by a continuation paragraph, insert one now.
                if (isset($this->rules['continuation'])) {
                    $this->maybeInsertContinuation($child, $xpath, $state, $body);
                }
            }
        }

        $zip->addFromString('word/document.xml', $dom->saveXML() ?: $xml);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Paragraph routing
    // ═══════════════════════════════════════════════════════════════════

    /** @param array<string, mixed> $state */
    private function processParagraph(DOMElement $p, DOMXPath $xpath, array &$state): void
    {
        $text       = $this->getParagraphText($xpath, $p);
        $normalized = $this->normalizeText($text);
        $styleId    = $this->getParagraphStyleId($xpath, $p);

        // ── Zone / state transitions ──────────────────────────────────
        $chapterMatch    = [];
        $isChapterLabel  = preg_match('/^chapter\s+([ivxlcdm]+|\d+)$/iu', $normalized, $chapterMatch) === 1;
        $isAppendixLabel = preg_match('/^appendix(?:es)?(?:\s+[a-zA-Z0-9])?$/iu', $normalized) === 1;
        $isReferences    = preg_match('/^references$/iu', $normalized) === 1;

        if ($isChapterLabel) {
            $state['zone']                = 'chapters';
            $state['currentChapter']      = $this->chapterToInt($chapterMatch[1]);
            $state['expectChapterTitle']  = true;
            $state['expectAppendixTitle'] = false;
            $state['isFirstParagraph']    = true;
        } elseif ($isAppendixLabel) {
            $state['zone']                = 'appendices';
            $state['expectAppendixTitle'] = true;
            $state['expectChapterTitle']  = false;
            $state['isFirstParagraph']    = true;
        } elseif ($isReferences) {
            $state['zone']                = 'chapters';
            $state['expectChapterTitle']  = false;
            $state['expectAppendixTitle'] = false;
            $state['isFirstParagraph']    = true;
        }

        if (!isset($this->sections[(string)$state['zone']])) { return; }

        $hasDrawing = $xpath->query('.//w:drawing | .//w:pict', $p)->length > 0;

        // Figure paragraphs with no text caught BEFORE empty-text check.
        if ($hasDrawing && $normalized === '' && !$this->isInTable($xpath, $p)) {
            $state['afterTable']       = false;
            $state['isFirstParagraph'] = false;
            $this->applyFigureParagraph($xpath, $p);
            return;
        }

        // Empty paragraphs: clear Calibri leak, skip routing.
        if ($normalized === '') {
            $this->applyEmptyParagraph($xpath, $p);
            return;
        }

        $hasNumbering = $xpath->query('./w:pPr/w:numPr', $p)->length > 0;
        $isInTable    = $this->isInTable($xpath, $p);

        // ── Caption / continuation / legend detection ─────────────────
        $isFigureCaption = !$state['isFirstParagraph']
            && preg_match('/^figure\s+\d+[\-\.]\d+\b/iu', $normalized) === 1
            && mb_strlen($normalized) <= 120
            && preg_match('/^figure\s+[\d\-\.]+\s+\w+\s+(presents|shows|describes|summarizes|lists|displays|illustrates|contains|provides|compares|indicates|reveals|demonstrates)\b/iu', $normalized) === 0;

        // Table caption: starts with "Table X-X" but is NOT a closing/discussion
        // paragraph like "Table 3-1 presents..." or "Table 3-1 shows...".
        // Captions are short (≤120 chars) and don't contain sentence verbs
        // immediately after the table number.
        $isTableCaption = !$state['isFirstParagraph']
            && preg_match('/^table\s+\d+[\-\.]\d+\b/iu', $normalized) === 1
            && mb_strlen($normalized) <= 120
            && preg_match('/^table\s+[\d\-\.]+\s+\w+\s+(presents|shows|describes|summarizes|lists|displays|illustrates|contains|provides|compares|indicates|reveals|demonstrates)\b/iu', $normalized) === 0;

        // Matches "Continuation of Table 1-1", "Continuation of Figure 2.3", etc.
        $isContinuation = preg_match(
            '/^continuation\s+of\s+(table|figure|appendix)(\s+[\d\-\.]+)?/iu', $normalized
        ) === 1;

        $isLegend = preg_match('/^legend\s*:/iu', $normalized) === 1;

        // ── Heading1 style block ──────────────────────────────────────
        if ($styleId === 'Heading1') {
            if ($isChapterLabel) {
                $state['afterTable'] = false;
                $this->applyChapterLabel($xpath, $p);
                return;
            }
            if ($isAppendixLabel) {
                $state['afterTable'] = false;
                $this->applyAppendixLabel($xpath, $p);
                return;
            }
            if ((bool)$state['expectChapterTitle']
                && !$hasDrawing && !$isFigureCaption && !$isTableCaption && !$hasNumbering
            ) {
                $state['expectChapterTitle'] = false;
                $state['isFirstParagraph']   = true;
                $state['afterTable']         = false;
                $this->applyChapterTitle($xpath, $p);
                return;
            }
            if ((bool)$state['expectAppendixTitle']
                && !$hasDrawing && !$isFigureCaption && !$isTableCaption && !$hasNumbering
            ) {
                $state['expectAppendixTitle'] = false;
                $state['isFirstParagraph']    = true;
                $state['afterTable']          = false;
                $this->applyAppendixTitle($xpath, $p);
                return;
            }
            if ($isReferences) {
                $state['afterTable'] = false;
                $this->applyReferencesTitle($xpath, $p);
                return;
            }
            $state['afterTable'] = false;
            $this->applyPreliminaryTitle($xpath, $p);
            return;
        }

        // ── Chapter title (non-Heading1) ──────────────────────────────
        if ((bool)$state['expectChapterTitle']
            && !$hasDrawing && !$isFigureCaption && !$isTableCaption && !$hasNumbering
        ) {
            $state['expectChapterTitle'] = false;
            $state['isFirstParagraph']   = true;
            $state['afterTable']         = false;
            $this->applyChapterTitle($xpath, $p);
            return;
        }

        // ── Appendix title ────────────────────────────────────────────
        if ((bool)$state['expectAppendixTitle']
            && !$hasDrawing && !$isFigureCaption && !$isTableCaption && !$hasNumbering
        ) {
            $state['expectAppendixTitle'] = false;
            $state['isFirstParagraph']    = true;
            $state['afterTable']          = false;
            $this->applyAppendixTitle($xpath, $p);
            return;
        }

        // ── Plain appendix label (non-Heading1) ──────────────────────
        if ($isAppendixLabel) {
            $state['afterTable'] = false;
            $this->applyAppendixLabel($xpath, $p);
            return;
        }

        // ── Chapter label (non-Heading1) ─────────────────────────────
        if ($isChapterLabel) {
            $state['afterTable'] = false;
            $this->applyChapterLabel($xpath, $p);
            return;
        }

        // ── References title (non-Heading1) ──────────────────────────
        if ($isReferences) {
            $state['afterTable'] = false;
            $this->applyReferencesTitle($xpath, $p);
            return;
        }

        $state['isFirstParagraph'] = false;

        // ── Figure paragraph ──────────────────────────────────────────
        if ($hasDrawing) {
            $state['afterTable'] = false;
            $this->applyFigureParagraph($xpath, $p);
            return;
        }

        // ── Figure caption ────────────────────────────────────────────
        if ($isFigureCaption) {
            $state['afterTable'] = false;
            $this->applyFigureCaption($xpath, $p);
            return;
        }

        // ── Table caption ─────────────────────────────────────────────
        if ($isTableCaption) {
            $state['afterTable'] = false;
            // Extract table number e.g. "3-2" from "Table 3-2. Customer Purposive Sampling."
            $m = [];
            if (preg_match('/^table\s+([\d]+[\-\.][\d]+)\b/iu', $normalized, $m)) {
                $state['lastTableNumber'] = $m[1];
            }
            $this->applyTableCaption($xpath, $p);
            return;
        }

        // ── Continuation label ────────────────────────────────────────
        // Does NOT reset afterTable — it is a table accessory. The first
        // real body paragraph after the continuation still gets 12pt before.
        if ($isContinuation) {
            $this->applyContinuationLabel($xpath, $p, (string)$state['lastTableNumber']);
            return;
        }

        // ── Legend ────────────────────────────────────────────────────
        // Does NOT reset afterTable — same rule as continuation above.
        if ($isLegend) {
            $this->applyLegend($xpath, $p);
            return;
        }

        // ── Table-internal paragraphs handled in processTable ─────────
        if ($isInTable) { return; }

        // ── Reference entries ─────────────────────────────────────────
        if ((string)$state['zone'] === 'chapters'
            && preg_match('/^\[\d+\]|^\d+\.\s/u', $normalized) === 1
        ) {
            $state['afterTable'] = false;
            $this->applyReferenceEntry($xpath, $p);
            return;
        }

        // ── Numbered / bulleted lists ─────────────────────────────────
        if ($hasNumbering || $styleId === 'ListParagraph') {
            $state['afterTable'] = false;
            $this->applyListParagraph($xpath, $p);
            return;
        }

        // ── Heading2 style ────────────────────────────────────────────
        if ($styleId === 'Heading2') {
            $state['afterTable'] = false;
            $this->applyHeading2($xpath, $p);
            return;
        }

        // ── Inline heading (bold+italic) ──────────────────────────────
        if ($this->isInlineHeading($xpath, $p, $normalized)) {
            $state['afterTable'] = false;
            $this->applyInlineHeading($xpath, $p);
            return;
        }

        // ── Bold-only heading ─────────────────────────────────────────
        if ($this->isBoldHeading($xpath, $p, $normalized)) {
            $state['afterTable'] = false;
            $this->applyBoldHeading($xpath, $p);
            return;
        }

        // ── Body paragraph (including closing paragraph after table) ──
        // $beforeSpacing is computed HERE so it always reads the live
        // afterTable value — even when legend/continuation passed through
        // without resetting it.
        $beforeSpacing       = (bool)$state['afterTable'] ? 240 : 0;
        $state['afterTable'] = false;
        $this->applyBodyParagraph($xpath, $p, $beforeSpacing);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Table processing
    // ═══════════════════════════════════════════════════════════════════

    /** @param array<string, mixed> $state */
    private function processTable(DOMElement $tbl, DOMXPath $xpath, array &$state): void
    {
        if (!isset($this->sections[(string)($state['zone'] ?? '')])) { return; }

        if (isset($this->rules['borders'])) {
            $this->setTableBorders($tbl, $xpath);
        }

        foreach ($xpath->query('.//w:tc', $tbl) as $tc) {
            if (!$tc instanceof DOMElement) { continue; }
            foreach ($xpath->query('.//w:p', $tc) as $p) {
                if (!$p instanceof DOMElement) { continue; }
                $isNumbered = $xpath->query('./w:pPr/w:numPr', $p)->length > 0;
                if (isset($this->rules['alignment'])) {
                    $this->writePAlignment($p, $isNumbered ? 'both' : 'center');
                }
                if (isset($this->rules['spacing'])) {
                    $this->writePSpacing($p, 0, 0, 240);
                }
                if (isset($this->rules['indentation'])) {
                    $this->writePIndent($p, 0);
                }
                $this->writeRuns($xpath, $p, 'Arial', 20, false, false);
                $this->writePPrRPr($p, 'Arial', 20, false, false);
            }
        }
    }

    /**
     * Insert a continuation label paragraph after a table IF:
     *   1. The table contains a w:lastRenderedPageBreak — meaning Word has
     *      already rendered this table as spanning multiple pages.
     *   2. The next sibling is NOT already a continuation paragraph.
     *
     * This is the only reliable way to detect multi-page tables without
     * a full rendering engine. w:lastRenderedPageBreak is written by Word
     * when it saves the file after rendering.
     *
     * @param array<string, mixed> $state
     */
    private function maybeInsertContinuation(
        DOMElement $tbl,
        DOMXPath   $xpath,
        array      $state,
        DOMElement $body
    ): void {
        // Check if table spans multiple pages via lastRenderedPageBreak
        $hasPageBreak = $xpath->query(
            './/w:lastRenderedPageBreak', $tbl
        )->length > 0;

        if (!$hasPageBreak) { return; }

        // Check next sibling is not already a continuation paragraph
        $next     = $tbl->nextSibling;
        $nextText = '';
        if ($next instanceof DOMElement && $next->localName === 'p') {
            foreach ($xpath->query('.//w:t', $next) as $t) {
                $nextText .= $t->textContent;
            }
            $nextText = trim(preg_replace('/\s+/', ' ', $nextText) ?? $nextText);
        }
        if (preg_match('/^continuation\s+of\s+(table|figure|appendix)/iu', $nextText) === 1) {
            return; // already has one
        }

        // Build the label text from lastTableNumber in state
        $tableNum = (string)$state['lastTableNumber'];
        $label    = $tableNum !== ''
            ? 'Continuation of Table ' . $tableNum . '...'
            : 'Continuation of Table...';

        $dom   = $tbl->ownerDocument;
        $wNs   = self::W_NS;
        $contP = $dom->createElementNS($wNs, 'w:p');

        // pPr
        $pPr = $dom->createElementNS($wNs, 'w:pPr');
        $contP->appendChild($pPr);

        $pbBefore = $dom->createElementNS($wNs, 'w:pageBreakBefore');
        $pPr->appendChild($pbBefore);

        $jc = $dom->createElementNS($wNs, 'w:jc');
        $jc->setAttributeNS($wNs, 'w:val', 'left');
        $pPr->appendChild($jc);

        $ind = $dom->createElementNS($wNs, 'w:ind');
        $ind->setAttributeNS($wNs, 'w:firstLine', '0');
        $ind->setAttributeNS($wNs, 'w:left',      '0');
        $ind->setAttributeNS($wNs, 'w:right',     '0');
        $pPr->appendChild($ind);

        $sp = $dom->createElementNS($wNs, 'w:spacing');
        $sp->setAttributeNS($wNs, 'w:before',            '0');
        $sp->setAttributeNS($wNs, 'w:after',             '0');
        $sp->setAttributeNS($wNs, 'w:line',              '240');
        $sp->setAttributeNS($wNs, 'w:lineRule',          'auto');
        $sp->setAttributeNS($wNs, 'w:beforeAutospacing', '0');
        $sp->setAttributeNS($wNs, 'w:afterAutospacing',  '0');
        $pPr->appendChild($sp);

        // pPr/rPr: Garamond 13pt italic
        $pPrRPr  = $dom->createElementNS($wNs, 'w:rPr');
        $rFonts  = $dom->createElementNS($wNs, 'w:rFonts');
        $rFonts->setAttributeNS($wNs, 'w:ascii',    'Garamond');
        $rFonts->setAttributeNS($wNs, 'w:hAnsi',    'Garamond');
        $rFonts->setAttributeNS($wNs, 'w:eastAsia', 'Garamond');
        $rFonts->setAttributeNS($wNs, 'w:cs',       'Garamond');
        $pPrRPr->appendChild($rFonts);
        $pPrRPr->appendChild($dom->createElementNS($wNs, 'w:i'));
        $pPrRPr->appendChild($dom->createElementNS($wNs, 'w:iCs'));
        $szEl = $dom->createElementNS($wNs, 'w:sz');
        $szEl->setAttributeNS($wNs, 'w:val', '26');
        $szCs = $dom->createElementNS($wNs, 'w:szCs');
        $szCs->setAttributeNS($wNs, 'w:val', '26');
        $pPrRPr->appendChild($szEl);
        $pPrRPr->appendChild($szCs);
        $pPr->appendChild($pPrRPr);

        // run
        $run    = $dom->createElementNS($wNs, 'w:r');
        $runRPr = $dom->createElementNS($wNs, 'w:rPr');
        $rF2    = $dom->createElementNS($wNs, 'w:rFonts');
        $rF2->setAttributeNS($wNs, 'w:ascii',    'Garamond');
        $rF2->setAttributeNS($wNs, 'w:hAnsi',    'Garamond');
        $rF2->setAttributeNS($wNs, 'w:eastAsia', 'Garamond');
        $rF2->setAttributeNS($wNs, 'w:cs',       'Garamond');
        $runRPr->appendChild($rF2);
        $runRPr->appendChild($dom->createElementNS($wNs, 'w:i'));
        $runRPr->appendChild($dom->createElementNS($wNs, 'w:iCs'));
        $sz2 = $dom->createElementNS($wNs, 'w:sz');
        $sz2->setAttributeNS($wNs, 'w:val', '26');
        $sC2 = $dom->createElementNS($wNs, 'w:szCs');
        $sC2->setAttributeNS($wNs, 'w:val', '26');
        $runRPr->appendChild($sz2);
        $runRPr->appendChild($sC2);
        $run->appendChild($runRPr);

        $tEl = $dom->createElementNS($wNs, 'w:t');
        $tEl->textContent = $label;
        $run->appendChild($tEl);
        $contP->appendChild($run);

        // Insert immediately after the table
        $nextSibling = $tbl->nextSibling;
        if ($nextSibling !== null) {
            $body->insertBefore($contP, $nextSibling);
        } else {
            $body->appendChild($contP);
        }
    }

    private function applyEmptyParagraph(DOMXPath $xpath, DOMElement $p): void
    {
        $pPr = $this->getChild($p, 'pPr');
        if ($pPr instanceof DOMElement) {
            $this->removeChildren($pPr, 'pStyle');
        }
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
        if ($xpath->query('.//w:r', $p)->length > 0) {
            $this->writeRuns($xpath, $p, 'Garamond', 24, false, false);
        }
    }

    private function applyChapterLabel(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        $this->titleCaseParagraphText($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }
        if (isset($this->rules['pagination']))  { $this->writePageBreakBefore($p, true); }
        $this->writeRuns($xpath, $p, 'Garamond', 28, true, false);
        $this->writePPrRPr($p, 'Garamond', 28, true, false);
    }

    private function applyChapterTitle(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        $this->stripOrphanedBookmarks($p);
        $this->uppercaseParagraphText($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 720); }
        $this->writeRuns($xpath, $p, 'Garamond', 28, true, false);
        $this->writePPrRPr($p, 'Garamond', 28, true, false);
    }

    private function applyPreliminaryTitle(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        $this->uppercaseParagraphText($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 720); }
        $this->writeRuns($xpath, $p, 'Garamond', 28, true, false);
        $this->writePPrRPr($p, 'Garamond', 28, true, false);
    }

    private function applyAppendixLabel(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        if (isset($this->rules['pagination']))  { $this->writePageBreakBefore($p, true); }
        $this->writeRuns($xpath, $p, 'Garamond', 28, true, false);
        $this->writePPrRPr($p, 'Garamond', 28, true, false);
    }

    private function applyAppendixTitle(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        $this->uppercaseParagraphText($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 720); }
        $this->writeRuns($xpath, $p, 'Garamond', 28, true, false);
        $this->writePPrRPr($p, 'Garamond', 28, true, false);
    }

    private function applyReferencesTitle(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        $this->uppercaseParagraphText($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 720); }
        $this->writeRuns($xpath, $p, 'Garamond', 28, true, false);
        $this->writePPrRPr($p, 'Garamond', 28, true, false);
    }

    private function applyHeading2(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }
        $this->writeRuns($xpath, $p, 'Garamond', 26, true, false);
        $this->writePPrRPr($p, 'Garamond', 26, true, false);
    }

    private function applyInlineHeading(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }
        $this->writeRuns($xpath, $p, 'Garamond', 24, true, true);
        $this->writePPrRPr($p, 'Garamond', 24, true, true);
    }

    private function applyBoldHeading(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }
        $this->writeRuns($xpath, $p, 'Garamond', 26, true, false);
        $this->writePPrRPr($p, 'Garamond', 26, true, false);
    }

    /**
     * Body paragraph — and the "closing paragraph" after a table.
     *
     * When $beforeSpacing = 240 (12pt), this is the first real paragraph
     * after a table (after any legend/continuation that may have followed).
     * null bold/italic preserves inline bold terms on any page.
     */
    private function applyBodyParagraph(DOMXPath $xpath, DOMElement $p, int $beforeSpacing = 0): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'both'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 720); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, $beforeSpacing, 0, 480); }
        $this->removePBdr($p);
        // null = preserve per-run bold/italic so "Term. definition..." works everywhere
        $this->writeRuns($xpath, $p, 'Garamond', 24, null, null);
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
    }

    private function applyReferenceEntry(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'both'); }
        if (isset($this->rules['indentation'])) { $this->writePHangingIndent($p, 720, 720); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        $this->writeRuns($xpath, $p, 'Garamond', 22, false, false);
        $this->writePPrRPr($p, 'Garamond', 22, false, false);
    }

    private function applyListParagraph(DOMXPath $xpath, DOMElement $p): void
    {
        if (isset($this->rules['alignment'])) { $this->writePAlignment($p, 'both'); }
        if (isset($this->rules['spacing']))   { $this->writePSpacing($p, 0, 0, 480); }
        $this->writeRuns($xpath, $p, 'Garamond', 26, false, false);
        $this->writePPrRPr($p, 'Garamond', 26, false, false);
    }

    /**
     * Figure paragraph — inline drawing, centered, 1.0 line, 0 before/after, 3pt border.
     * Converts wp:anchor to wp:inline.
     */
    private function applyFigureParagraph(DOMXPath $xpath, DOMElement $p): void
    {
        // ── 1. Rebuild pPr ────────────────────────────────────────────
        $pPr = $this->ensurePPr($p);
        foreach (['pStyle', 'widowControl', 'jc', 'ind', 'spacing', 'pBdr', 'rPr'] as $tag) {
            $this->removeChildren($pPr, $tag);
        }

        $jc = $p->ownerDocument->createElementNS(self::W_NS, 'w:jc');
        $jc->setAttributeNS(self::W_NS, 'w:val', 'center');
        $pPr->appendChild($jc);

        $ind = $p->ownerDocument->createElementNS(self::W_NS, 'w:ind');
        $ind->setAttributeNS(self::W_NS, 'w:firstLine', '0');
        $ind->setAttributeNS(self::W_NS, 'w:left',      '0');
        $ind->setAttributeNS(self::W_NS, 'w:right',     '0');
        $pPr->appendChild($ind);

        $sp = $p->ownerDocument->createElementNS(self::W_NS, 'w:spacing');
        $sp->setAttributeNS(self::W_NS, 'w:before',            '0');
        $sp->setAttributeNS(self::W_NS, 'w:after',             '0');
        $sp->setAttributeNS(self::W_NS, 'w:line',              '240');
        $sp->setAttributeNS(self::W_NS, 'w:lineRule',          'auto');
        $sp->setAttributeNS(self::W_NS, 'w:beforeAutospacing', '0');
        $sp->setAttributeNS(self::W_NS, 'w:afterAutospacing',  '0');
        $pPr->appendChild($sp);

        $rPr    = $p->ownerDocument->createElementNS(self::W_NS, 'w:rPr');
        $rFonts = $p->ownerDocument->createElementNS(self::W_NS, 'w:rFonts');
        $rFonts->setAttributeNS(self::W_NS, 'w:ascii',    'Garamond');
        $rFonts->setAttributeNS(self::W_NS, 'w:hAnsi',    'Garamond');
        $rFonts->setAttributeNS(self::W_NS, 'w:eastAsia', 'Garamond');
        $rFonts->setAttributeNS(self::W_NS, 'w:cs',       'Garamond');
        $rPr->appendChild($rFonts);
        $szEl = $p->ownerDocument->createElementNS(self::W_NS, 'w:sz');
        $szEl->setAttributeNS(self::W_NS, 'w:val', '24');
        $rPr->appendChild($szEl);
        $szCs = $p->ownerDocument->createElementNS(self::W_NS, 'w:szCs');
        $szCs->setAttributeNS(self::W_NS, 'w:val', '24');
        $rPr->appendChild($szCs);
        $pPr->appendChild($rPr);

        // ── 2. Convert wp:anchor to wp:inline ────────────────────────
        $WP_NS = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
        foreach ($xpath->query('.//w:drawing', $p) as $drawing) {
            if (!$drawing instanceof DOMElement) { continue; }
            $anchor = null;
            foreach ($drawing->childNodes as $child) {
                if ($child instanceof DOMElement
                    && $child->namespaceURI === $WP_NS
                    && $child->localName === 'anchor'
                ) { $anchor = $child; break; }
            }
            if ($anchor === null) { continue; }

            $inline = $p->ownerDocument->createElementNS($WP_NS, 'wp:inline');
            $inline->setAttribute('distT', '0');
            $inline->setAttribute('distB', '0');
            $inline->setAttribute('distL', '0');
            $inline->setAttribute('distR', '0');

            $extent = null;
            foreach ($anchor->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'extent') {
                    $extent = $child; break;
                }
            }
            if ($extent instanceof DOMElement) {
                $newExtent = $p->ownerDocument->createElementNS($WP_NS, 'wp:extent');
                $newExtent->setAttribute('cx', $extent->getAttribute('cx'));
                $newExtent->setAttribute('cy', $extent->getAttribute('cy'));
                $inline->appendChild($newExtent);
            }

            $effExt = $p->ownerDocument->createElementNS($WP_NS, 'wp:effectExtent');
            $effExt->setAttribute('l', '0');
            $effExt->setAttribute('t', '0');
            $effExt->setAttribute('r', '0');
            $effExt->setAttribute('b', '0');
            $inline->appendChild($effExt);

            foreach ($anchor->childNodes as $child) {
                if (!$child instanceof DOMElement) { continue; }
                if (in_array($child->localName, ['docPr', 'cNvGraphicFramePr', 'graphic'], true)) {
                    $inline->appendChild($child->cloneNode(true));
                }
            }
            $drawing->replaceChild($inline, $anchor);
        }

        // ── 3. Set 3pt solid black border on image shape ─────────────
        $A_NS    = 'http://schemas.openxmlformats.org/drawingml/2006/main';
        $lnNodes = $xpath->query(
            './/w:drawing//*[local-name()="ln"] | .//w:pict//*[local-name()="ln"]', $p
        );
        foreach ($lnNodes as $ln) {
            if (!$ln instanceof DOMElement) { continue; }
            if ($ln->parentNode instanceof DOMElement
                && $ln->parentNode->localName !== 'spPr'
            ) { continue; }
            $ln->setAttribute('w', '38100');
            $toRemove = [];
            foreach ($ln->childNodes as $child) {
                if ($child instanceof DOMElement) { $toRemove[] = $child; }
            }
            foreach ($toRemove as $child) { $ln->removeChild($child); }
            $solidFill = $p->ownerDocument->createElementNS($A_NS, 'a:solidFill');
            $srgbClr   = $p->ownerDocument->createElementNS($A_NS, 'a:srgbClr');
            $srgbClr->setAttribute('val', '000000');
            $solidFill->appendChild($srgbClr);
            $ln->appendChild($solidFill);
        }
    }

    private function applyFigureCaption(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }
        $this->removePBdr($p);
        $this->writeRuns($xpath, $p, 'Garamond', 24, false, false);
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
    }

    /**
     * Table caption — appears on top of the table.
     * Garamond 12pt (sz=24), left aligned, 1.0 line spacing (240), 0 before/after.
     */
    private function applyTableCaption(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        $this->removePBdr($p);
        $this->writeRuns($xpath, $p, 'Garamond', 24, false, false);
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
    }

    /**
     * Legend — appears right after the table.
     * Garamond 11pt (sz=22), left aligned, 1.0 line spacing (240), 0 before/after.
     *
     * Does NOT reset afterTable. The first real body paragraph after
     * legend still receives 12pt before spacing.
     */
    private function applyLegend(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        $this->writeRuns($xpath, $p, 'Garamond', 22, false, false);
        $this->writePPrRPr($p, 'Garamond', 22, false, false);
    }

    /**
     * Continuation label — e.g. "Continuation of Table 1-1..."
     * Garamond 13pt (sz=26), italic, left aligned, 1.0 line spacing (240), 0 before/after.
     *
     * Does NOT reset afterTable — same reason as applyLegend.
     */
    /**
     * Continuation label — e.g. "Continuation of Table 3-2..."
     * Garamond 13pt (sz=26), italic, left aligned, 1.0 line spacing (240), 0 before/after.
     * Forces page break before so it always appears at the top of the continued page.
     * Rewrites the paragraph text to the canonical format:
     *   "Continuation of Table X-X..."
     * Does NOT reset afterTable.
     */
    private function applyContinuationLabel(DOMXPath $xpath, DOMElement $p, string $tableNumber = ''): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        if (isset($this->rules['pagination']))  { $this->writePageBreakBefore($p, true); }

        // Rewrite text to canonical format: "Continuation of Table X-X..."
        if ($tableNumber !== '') {
            $canonical = 'Continuation of Table ' . $tableNumber . '...';
            // Remove all existing text runs and replace with a single clean run
            $toRemove = [];
            foreach ($xpath->query('.//w:r', $p) as $run) {
                if (!$run instanceof DOMElement) { continue; }
                $toRemove[] = $run;
            }
            foreach ($toRemove as $run) { $run->parentNode?->removeChild($run); }

            $run = $p->ownerDocument->createElementNS(self::W_NS, 'w:r');
            $tEl = $p->ownerDocument->createElementNS(self::W_NS, 'w:t');
            $tEl->textContent = $canonical;
            $run->appendChild($tEl);
            $p->appendChild($run);
        }

        $this->writeRuns($xpath, $p, 'Garamond', 26, false, true);
        $this->writePPrRPr($p, 'Garamond', 26, false, true);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Classification helpers
    // ═══════════════════════════════════════════════════════════════════

    private function isInlineHeading(DOMXPath $xpath, DOMElement $p, string $text): bool
    {
        if ($text === '') { return false; }
        if ($this->getPFirstLineIndent($p) > 0) { return false; }
        if (preg_match('/[.!?]$/u', $text) === 1) { return false; }
        if (preg_match('/^(figure|fig\.?|table|chapter|appendix|continuation)\b/iu', $text) === 1) {
            return false;
        }
        if (mb_strlen($text) > 150) { return false; }

        $runs  = $xpath->query('.//w:r', $p);
        $total = $runs->length;
        if ($total === 0) { return false; }

        $boldItalicRuns = 0;
        $boldOnlyRuns   = 0;
        foreach ($runs as $run) {
            if (!$run instanceof DOMElement) { continue; }
            $rPr = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
            $hasBold   = $rPr instanceof DOMElement
                && $rPr->getElementsByTagNameNS(self::W_NS, 'b')->length > 0;
            $hasItalic = $rPr instanceof DOMElement
                && $rPr->getElementsByTagNameNS(self::W_NS, 'i')->length > 0;
            if ($hasBold && $hasItalic) { $boldItalicRuns++; }
            if ($hasBold && !$hasItalic) { $boldOnlyRuns++; }
        }

        return $boldItalicRuns > 0 && ($boldOnlyRuns / $total) <= 0.2;
    }

    /**
     * Detects a standalone bold heading (e.g. "Operational Terms").
     *
     * "Term. definition..." pattern is excluded: if bold runs appear before
     * non-bold runs in the same paragraph, returns false so applyBodyParagraph
     * handles it with null bold — preserving the bold term on any page.
     */
    private function isBoldHeading(DOMXPath $xpath, DOMElement $p, string $text): bool
    {
        if ($text === '') { return false; }
        if ($this->getPFirstLineIndent($p) > 0) { return false; }
        if (preg_match('/[.!?]$/u', $text) === 1) { return false; }
        if (preg_match('/^(figure|fig\.?|table|chapter|appendix|continuation)\b/iu', $text) === 1) {
            return false;
        }
        if (mb_strlen($text) > 150) { return false; }
        if ($xpath->query('.//w:r/w:rPr/w:i', $p)->length > 0) { return false; }

        $runs = $xpath->query('.//w:r', $p);
        if ($runs->length === 0) { return false; }

        $textRunsTotal     = 0;
        $textRunsBold      = 0;
        $foundNonBold      = false;
        $boldBeforeNonBold = 0;

        foreach ($runs as $run) {
            if (!$run instanceof DOMElement) { continue; }
            $tEl = $run->getElementsByTagNameNS(self::W_NS, 't')->item(0);
            if (!$tEl instanceof DOMElement) { continue; }
            if (trim($tEl->textContent ?? '') === '') { continue; }
            $textRunsTotal++;
            $rPr    = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
            $isBold = $rPr instanceof DOMElement
                && $rPr->getElementsByTagNameNS(self::W_NS, 'b')->length > 0;
            if ($isBold) {
                $textRunsBold++;
                if (!$foundNonBold) { $boldBeforeNonBold++; }
            } else {
                $foundNonBold = true;
            }
        }

        if ($textRunsTotal === 0) { return false; }

        // "Bold Term. plain definition" — route to body, not heading.
        if ($foundNonBold && $boldBeforeNonBold > 0 && $textRunsBold < $textRunsTotal) {
            return false;
        }

        return ($textRunsBold / $textRunsTotal) >= 0.6;
    }

    private function getPFirstLineIndent(DOMElement $p): int
    {
        $indEl = $p->getElementsByTagNameNS(self::W_NS, 'ind')->item(0);
        if (!$indEl instanceof DOMElement) { return 0; }
        return (int)($indEl->getAttributeNS(self::W_NS, 'firstLine') ?: 0);
    }

    private function getParagraphStyleId(DOMXPath $xpath, DOMElement $p): string
    {
        $node = $xpath->query('./w:pPr/w:pStyle', $p)->item(0);
        if (!$node instanceof DOMElement) { return ''; }
        return (string)$node->getAttributeNS(self::W_NS, 'val');
    }

    private function isInTable(DOMXPath $xpath, DOMElement $p): bool
    {
        return $xpath->query('ancestor::w:tc', $p)->length > 0;
    }

    private function getParagraphText(DOMXPath $xpath, DOMElement $p): string
    {
        $parts = [];
        foreach ($xpath->query(
            './/w:r[not(.//w:drawing) and not(.//w:pict) and not(.//w:instrText)]/w:t', $p
        ) as $t) {
            $parts[] = $t->textContent;
        }
        foreach ($xpath->query('w:t', $p) as $t) {
            $parts[] = $t->textContent;
        }
        return implode('', $parts);
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function uppercaseParagraphText(DOMXPath $xpath, DOMElement $p): void
    {
        foreach ($xpath->query('.//w:r[not(.//w:drawing) and not(.//w:pict)]/w:t', $p) as $t) {
            $t->textContent = mb_strtoupper((string)($t->textContent ?? ''), 'UTF-8');
        }
    }

    private function titleCaseParagraphText(DOMXPath $xpath, DOMElement $p): void
    {
        foreach ($xpath->query('.//w:r[not(.//w:drawing) and not(.//w:pict)]/w:t', $p) as $t) {
            $t->textContent = mb_convert_case((string)($t->textContent ?? ''), MB_CASE_TITLE, 'UTF-8');
        }
    }

    private function chapterToInt(string $token): int
    {
        if (ctype_digit($token)) { return (int)$token; }
        $token  = strtoupper($token);
        $map    = ['M'=>1000,'D'=>500,'C'=>100,'L'=>50,'X'=>10,'V'=>5,'I'=>1];
        $result = 0; $prev = 0;
        for ($i = strlen($token) - 1; $i >= 0; $i--) {
            $val = $map[$token[$i]] ?? 0;
            if ($val < $prev) { $result -= $val; } else { $result += $val; $prev = $val; }
        }
        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════
    // Style stripping
    // ═══════════════════════════════════════════════════════════════════

    private function stripAll(DOMXPath $xpath, DOMElement $p): void
    {
        $pPr = $this->getChild($p, 'pPr');
        if ($pPr instanceof DOMElement) {
            $this->removeChildren($pPr, 'pStyle');
            $this->removeChildren($pPr, 'rPr');
            $this->removeChildren($pPr, 'widowControl');
        }
        foreach ($xpath->query('.//w:r', $p) as $run) {
            if (!$run instanceof DOMElement) { continue; }
            if ($xpath->query('.//w:drawing | .//w:pict | .//w:instrText | .//w:fldChar', $run)->length > 0) {
                continue;
            }
            $rPr = $this->getChild($run, 'rPr');
            if ($rPr instanceof DOMElement) {
                $this->removeChildren($rPr, 'rStyle');
            }
        }
    }

    private function stripOrphanedBookmarks(DOMElement $p): void
    {
        $toRemove = [];
        foreach ($p->childNodes as $child) {
            if (!$child instanceof DOMElement) { continue; }
            if (!in_array($child->localName, ['bookmarkStart', 'bookmarkEnd'], true)) { continue; }
            $name = (string)$child->getAttributeNS(self::W_NS, 'name');
            if (str_starts_with($name, '_') || $name === '') {
                $toRemove[] = $child;
            }
        }
        foreach ($toRemove as $node) {
            $p->removeChild($node);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Paragraph mark rPr writer
    // ═══════════════════════════════════════════════════════════════════

    private function writePPrRPr(
        DOMElement $p,
        string $font,
        int $size,
        bool $bold,
        bool $italic
    ): void {
        $pPr     = $this->ensurePPr($p);
        $sizeStr = (string)$size;
        $this->removeChildren($pPr, 'rPr');
        $rPr = $pPr->ownerDocument->createElementNS(self::W_NS, 'w:rPr');
        $pPr->appendChild($rPr);

        $rFonts = $pPr->ownerDocument->createElementNS(self::W_NS, 'w:rFonts');
        $rFonts->setAttributeNS(self::W_NS, 'w:ascii',    $font);
        $rFonts->setAttributeNS(self::W_NS, 'w:hAnsi',    $font);
        $rFonts->setAttributeNS(self::W_NS, 'w:eastAsia', $font);
        $rFonts->setAttributeNS(self::W_NS, 'w:cs',       $font);
        $rPr->appendChild($rFonts);

        if ($bold) {
            $rPr->appendChild($pPr->ownerDocument->createElementNS(self::W_NS, 'w:b'));
            $rPr->appendChild($pPr->ownerDocument->createElementNS(self::W_NS, 'w:bCs'));
        }
        if ($italic) {
            $rPr->appendChild($pPr->ownerDocument->createElementNS(self::W_NS, 'w:i'));
            $rPr->appendChild($pPr->ownerDocument->createElementNS(self::W_NS, 'w:iCs'));
        }

        $szEl   = $pPr->ownerDocument->createElementNS(self::W_NS, 'w:sz');
        $szElCs = $pPr->ownerDocument->createElementNS(self::W_NS, 'w:szCs');
        $szEl->setAttributeNS(self::W_NS,   'w:val', $sizeStr);
        $szElCs->setAttributeNS(self::W_NS, 'w:val', $sizeStr);
        $rPr->appendChild($szEl);
        $rPr->appendChild($szElCs);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Run writing
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Write font/size/bold/italic to every text run.
     *
     * $bold / $italic:
     *   true  — force ON
     *   false — force OFF
     *   null  — preserve each run's existing state
     */
    private function writeRuns(
        DOMXPath $xpath,
        DOMElement $scope,
        string $font,
        int $size,
        ?bool $bold,
        ?bool $italic
    ): void {
        $sizeStr  = (string)$size;
        $textRuns = [];

        foreach ($xpath->query('.//w:r', $scope) as $run) {
            if (!$run instanceof DOMElement) { continue; }
            $isDrawingRun = $xpath->query(
                './/w:drawing | .//w:pict | .//w:instrText | .//w:fldChar', $run
            )->length > 0;
            if (!$isDrawingRun) { $textRuns[] = $run; }
        }

        if (count($textRuns) === 0) {
            $textContent = '';
            foreach ($xpath->query('.//w:t', $scope) as $tNode) {
                $textContent .= $tNode->textContent;
            }
            $run = $scope->ownerDocument->createElementNS(self::W_NS, 'w:r');
            $tEl = $scope->ownerDocument->createElementNS(self::W_NS, 'w:t');
            $tEl->textContent = $textContent;
            if ($textContent !== ltrim($textContent) || $textContent !== rtrim($textContent)) {
                $tEl->setAttribute('xml:space', 'preserve');
            }
            $run->appendChild($tEl);
            $scope->appendChild($run);
            $textRuns[] = $run;
        }

        foreach ($textRuns as $run) {
            if (!$run instanceof DOMElement) { continue; }

            // Snapshot bold/italic BEFORE wiping rPr
            $existingRPr    = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
            $existingBold   = $existingRPr instanceof DOMElement
                && $existingRPr->getElementsByTagNameNS(self::W_NS, 'b')->length > 0;
            $existingItalic = $existingRPr instanceof DOMElement
                && $existingRPr->getElementsByTagNameNS(self::W_NS, 'i')->length > 0;

            $applyBold   = $bold   ?? $existingBold;
            $applyItalic = $italic ?? $existingItalic;

            $this->removeChildren($run, 'rPr');
            $rPr = $run->ownerDocument->createElementNS(self::W_NS, 'w:rPr');
            if ($run->firstChild !== null) {
                $run->insertBefore($rPr, $run->firstChild);
            } else {
                $run->appendChild($rPr);
            }

            $this->removeChildren($rPr, 'rFonts');
            $rFonts = $this->ensureChild($rPr, 'rFonts');
            foreach (['w:asciiTheme', 'w:hAnsiTheme', 'w:eastAsiaTheme', 'w:cstheme'] as $attr) {
                if ($rFonts->hasAttributeNS(self::W_NS, ltrim($attr, 'w:'))) {
                    $rFonts->removeAttributeNS(self::W_NS, ltrim($attr, 'w:'));
                }
            }
            $rFonts->setAttributeNS(self::W_NS, 'w:ascii',    $font);
            $rFonts->setAttributeNS(self::W_NS, 'w:hAnsi',    $font);
            $rFonts->setAttributeNS(self::W_NS, 'w:eastAsia', $font);
            $rFonts->setAttributeNS(self::W_NS, 'w:cs',       $font);

            $this->removeChildren($rPr, 'sz');
            $this->removeChildren($rPr, 'szCs');
            $this->ensureChild($rPr, 'sz')  ->setAttributeNS(self::W_NS, 'w:val', $sizeStr);
            $this->ensureChild($rPr, 'szCs')->setAttributeNS(self::W_NS, 'w:val', $sizeStr);

            $this->removeChildren($rPr, 'b');
            $this->removeChildren($rPr, 'bCs');
            if ($applyBold) {
                $this->ensureChild($rPr, 'b');
                $this->ensureChild($rPr, 'bCs');
            }

            $this->removeChildren($rPr, 'i');
            $this->removeChildren($rPr, 'iCs');
            if ($applyItalic) {
                $this->ensureChild($rPr, 'i');
                $this->ensureChild($rPr, 'iCs');
            }

            $this->removeChildren($rPr, 'caps');
            $this->removeChildren($rPr, 'color');
            $this->removeChildren($rPr, 'lang');
            $this->removeChildren($rPr, 'noProof');
            $this->removeChildren($rPr, 'rStyle');
            $this->removeChildren($rPr, 'highlight');
            $this->removeChildren($rPr, 'vertAlign');
            $this->removeChildren($rPr, 'effect');
            $this->removeChildren($rPr, 'w14:glow');
            $this->removeChildren($rPr, 'w14:shadow');
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Paragraph property writers
    // ═══════════════════════════════════════════════════════════════════

    private function writePAlignment(DOMElement $p, string $value): void
    {
        $pPr      = $this->ensurePPr($p);
        $existing = $this->getChild($pPr, 'jc');
        if ($existing instanceof DOMElement
            && $existing->getAttributeNS(self::W_NS, 'val') === $value
        ) { return; }
        $this->removeChildren($pPr, 'jc');
        $this->ensureChild($pPr, 'jc')->setAttributeNS(self::W_NS, 'w:val', $value);
    }

    private function writePIndent(DOMElement $p, int $firstTwips): void
    {
        $pPr = $this->ensurePPr($p);
        $this->removeChildren($pPr, 'ind');
        $ind = $this->ensureChild($pPr, 'ind');
        $ind->setAttributeNS(self::W_NS, 'w:firstLine', (string)$firstTwips);
        $ind->setAttributeNS(self::W_NS, 'w:left',      '0');
        $ind->setAttributeNS(self::W_NS, 'w:right',     '0');
    }

    private function writePHangingIndent(DOMElement $p, int $leftTwips, int $hangingTwips): void
    {
        $pPr = $this->ensurePPr($p);
        $this->removeChildren($pPr, 'ind');
        $ind = $this->ensureChild($pPr, 'ind');
        $ind->setAttributeNS(self::W_NS, 'w:left',    (string)$leftTwips);
        $ind->setAttributeNS(self::W_NS, 'w:hanging', (string)$hangingTwips);
    }

    private function writePSpacing(DOMElement $p, int $before, int $after, int $line): void
    {
        $pPr = $this->ensurePPr($p);
        $this->removeChildren($pPr, 'spacing');
        $sp = $this->ensureChild($pPr, 'spacing');
        $sp->setAttributeNS(self::W_NS, 'w:before',            (string)$before);
        $sp->setAttributeNS(self::W_NS, 'w:after',             (string)$after);
        $sp->setAttributeNS(self::W_NS, 'w:line',              (string)$line);
        $sp->setAttributeNS(self::W_NS, 'w:lineRule',          'auto');
        $sp->setAttributeNS(self::W_NS, 'w:beforeAutospacing', '0');
        $sp->setAttributeNS(self::W_NS, 'w:afterAutospacing',  '0');
    }

    private function writePageBreakBefore(DOMElement $p, bool $enabled): void
    {
        $pPr = $this->ensurePPr($p);
        if ($enabled) {
            $this->ensureChild($pPr, 'pageBreakBefore');
        } else {
            $this->removeChildren($pPr, 'pageBreakBefore');
        }
    }

    private function writePBoxBorder(DOMElement $p, int $sizeEighthsPt): void
    {
        $pBdr = $this->ensureChild($this->ensurePPr($p), 'pBdr');
        foreach (['top', 'left', 'bottom', 'right'] as $side) {
            $edge = $this->ensureChild($pBdr, $side);
            $edge->setAttributeNS(self::W_NS, 'w:val',   'single');
            $edge->setAttributeNS(self::W_NS, 'w:sz',    (string)$sizeEighthsPt);
            $edge->setAttributeNS(self::W_NS, 'w:space', '1');
            $edge->setAttributeNS(self::W_NS, 'w:color', '000000');
        }
    }

    private function removePBdr(DOMElement $p): void
    {
        $pPr = $this->getChild($p, 'pPr');
        if ($pPr instanceof DOMElement) {
            $this->removeChildren($pPr, 'pBdr');
        }
    }

    /**
     * Table borders per spec:
     *
     * Table-level (tblBorders):
     *   top    : double solid 0.5pt (sz=4)  — always
     *   bottom : double solid 0.5pt (sz=4)  — always
     *   left, right, insideH, insideV : none
     *
     * Per-cell (tcBorders) — only on header row and footer row:
     *   Header row (row 1)  : single solid 0.5pt on the BOTTOM of each cell
     *   Footer row (last row): single solid 0.5pt on the TOP of each cell
     *   All other cells     : tcBorders wiped so the table-level borders win
     *
     * We wipe all tcBorders first, then re-add only what is needed so no
     * stale single-line borders survive to override the double outer borders.
     */
    private function setTableBorders(DOMElement $tbl, DOMXPath $xpath): void
    {
        // ── Step 1: Wipe ALL existing tcBorders on every cell ────────
        // tcBorders override tblBorders in Word. Any leftover single-line
        // border on a top/bottom cell will beat our double outer border.
        foreach ($xpath->query('.//w:tc', $tbl) as $tc) {
            if (!$tc instanceof DOMElement) { continue; }
            $tcPr = $this->getChild($tc, 'tcPr');
            if ($tcPr instanceof DOMElement) {
                $this->removeChildren($tcPr, 'tcBorders');
            }
        }

        // ── Step 2: Rebuild tblBorders from scratch ───────────────────
        $tblPr = $this->ensureChild($tbl, 'tblPr');
        $this->removeChildren($tblPr, 'tblBorders');
        $tblBorders = $tbl->ownerDocument->createElementNS(self::W_NS, 'w:tblBorders');
        $tblPr->appendChild($tblBorders);

        $makeBorder = function (string $localName, string $val, string $sz) use ($tbl, $tblBorders): void {
            $el = $tbl->ownerDocument->createElementNS(self::W_NS, 'w:' . $localName);
            $el->setAttributeNS(self::W_NS, 'w:val',   $val);
            $el->setAttributeNS(self::W_NS, 'w:sz',    $sz);
            $el->setAttributeNS(self::W_NS, 'w:space', '0');
            $el->setAttributeNS(self::W_NS, 'w:color', '000000');
            $tblBorders->appendChild($el);
        };

        $makeBorder('top',     'double', '4'); // double solid 0.5pt
        $makeBorder('left',    'none',   '0');
        $makeBorder('bottom',  'double', '4'); // double solid 0.5pt
        $makeBorder('right',   'none',   '0');
        $makeBorder('insideH', 'none',   '0'); // row separators via per-cell only
        $makeBorder('insideV', 'none',   '0');

        // ── Step 3: Per-cell borders on header and footer rows only ───
        $rows     = $xpath->query('.//w:tr', $tbl);
        $rowCount = $rows->length;
        if ($rowCount === 0) { return; }

        $wNs     = self::W_NS;
        $makeCell = function (string $side, DOMElement $tc) use ($wNs): void {
            // Get or create tcPr as first child of tc
            $tcPr = null;
            foreach ($tc->childNodes as $child) {
                if ($child instanceof DOMElement
                    && $child->localName === 'tcPr'
                    && $child->namespaceURI === $wNs
                ) { $tcPr = $child; break; }
            }
            if ($tcPr === null) {
                $tcPr = $tc->ownerDocument->createElementNS($wNs, 'w:tcPr');
                $tc->insertBefore($tcPr, $tc->firstChild);
            }

            // Fresh tcBorders — Step 1 already wiped any old one
            $tcBdr = $tc->ownerDocument->createElementNS($wNs, 'w:tcBorders');
            $tcPr->appendChild($tcBdr);

            $el = $tc->ownerDocument->createElementNS($wNs, 'w:' . $side);
            $el->setAttributeNS($wNs, 'w:val',   'single');
            $el->setAttributeNS($wNs, 'w:sz',    '4');
            $el->setAttributeNS($wNs, 'w:space', '0');
            $el->setAttributeNS($wNs, 'w:color', '000000');
            $tcBdr->appendChild($el);
        };

        // Header row (row 1): single 0.5pt on BOTTOM of each cell
        $firstRow = $rows->item(0);
        if ($firstRow instanceof DOMElement) {
            foreach ($xpath->query('w:tc', $firstRow) as $tc) {
                if (!$tc instanceof DOMElement) { continue; }
                $makeCell('bottom', $tc);
            }
        }

        // Footer row (last row): single 0.5pt on TOP of each cell
        // Only when table has more than 1 row so header and footer are distinct
        $lastRow = $rows->item($rowCount - 1);
        if ($lastRow instanceof DOMElement && $rowCount > 1) {
            foreach ($xpath->query('w:tc', $lastRow) as $tc) {
                if (!$tc instanceof DOMElement) { continue; }
                $makeCell('top', $tc);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // XML DOM helpers
    // ═══════════════════════════════════════════════════════════════════

    /** @return array{0:DOMDocument,1:DOMXPath} */
    private function loadXml(string $xml): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        if (!$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new RuntimeException('Could not parse DOCX XML content.');
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);
        return [$dom, $xpath];
    }

    private function ensurePPr(DOMElement $p): DOMElement
    {
        return $this->ensureChild($p, 'pPr', true);
    }

    private function getChild(DOMElement $parent, string $localName): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement
                && $child->namespaceURI === self::W_NS
                && $child->localName   === $localName
            ) { return $child; }
        }
        return null;
    }

    private function ensureChild(
        DOMElement $parent,
        string $localName,
        bool $prepend = false
    ): DOMElement {
        $existing = $this->getChild($parent, $localName);
        if ($existing instanceof DOMElement) { return $existing; }
        $child = $parent->ownerDocument->createElementNS(self::W_NS, 'w:' . $localName);
        if ($prepend && $parent->firstChild instanceof DOMNode) {
            $parent->insertBefore($child, $parent->firstChild);
        } else {
            $parent->appendChild($child);
        }
        return $child;
    }

    private function removeChildren(DOMElement $parent, string $localName): void
    {
        $toRemove = [];
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement
                && $child->namespaceURI === self::W_NS
                && $child->localName   === $localName
            ) { $toRemove[] = $child; }
        }
        foreach ($toRemove as $node) { $parent->removeChild($node); }
    }
}