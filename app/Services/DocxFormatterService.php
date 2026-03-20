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
 *   - Footer row top      : explicitly suppressed (none) on each footer cell
 *   - Footer row bottom   : explicitly double on each footer cell (ensures tbl bottom is double)
 *   - Left/Right/insideV  : none
 *   - insideH             : none
 *
 * AFTER-TABLE FLOW:
 *   Table sets afterTable=true.
 *   Legend and Continuation do NOT reset afterTable.
 *   The first real body paragraph after the table receives 12pt before spacing.
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

        $this->sections = $this->normalizeMap($options['sections'] ?? ['chapters']);
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

        // ── Find zone boundaries ────────────────────────────────────────
        // Collect only body children that fall between CHAPTER 1 (inclusive)
        // and APPENDIX/end-of-body (exclusive). Pre-passes and the main loop
        // all work on this same slice so preliminary and appendices are never touched.
        $allChildren   = [];
        foreach ($body->childNodes as $c) {
            if ($c instanceof DOMElement) { $allChildren[] = $c; }
        }

        $chapterStart = -1;
        $appendixEnd  = count($allChildren); // exclusive upper bound

        foreach ($allChildren as $idx => $child) {
            if ($child->localName !== 'p') { continue; }
            $t = '';
            foreach ($xpath->query('.//w:t', $child) as $tn) { $t .= $tn->textContent; }
            $t = trim(preg_replace('/\s+/', ' ', $t) ?? $t);
            if ($chapterStart === -1
                && preg_match('/^chapter\s+([ivxlcdm]+|\d+)$/iu', $t) === 1
            ) {
                $chapterStart = $idx;
            }
            if ($chapterStart !== -1
                && preg_match('/^appendix(?:es)?(\s+[a-zA-Z0-9])?$/iu', $t) === 1
            ) {
                $appendixEnd = $idx;
                break;
            }
        }

        // Nothing to format if we never found a chapter label
        if ($chapterStart === -1) {
            $zip->addFromString('word/document.xml', $dom->saveXML() ?: $xml);
            return;
        }

        $scopedChildren = array_slice($allChildren, $chapterStart, $appendixEnd - $chapterStart);

        // ── Pre-pass A: hoist captions placed after tables to before them ──
        $this->hoistTableCaptions($body, $xpath, $scopedChildren);

        // ── Pre-pass B: table continuation (tblHeader + split + labels) ──
        if (isset($this->rules['continuation'])) {
            $this->handleTableContinuation($body, $xpath, $scopedChildren);
        }

        $state = [
            'zone'               => 'preliminary',
            'currentChapter'     => 0,
            'expectChapterTitle' => false,
            'isFirstParagraph'   => true,
            'afterTable'         => false,
            'lastTableNumber'    => '',
        ];

        foreach ($scopedChildren as $child) {
            if (!$child instanceof DOMElement) { continue; }
            if ($child->localName === 'p') {
                $this->processParagraph($child, $xpath, $state);
            } elseif ($child->localName === 'tbl') {
                $this->processTable($child, $xpath, $state);
                $state['afterTable'] = true;
            }
        }

        $zip->addFromString('word/document.xml', $dom->saveXML() ?: $xml);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Pre-pass A: hoist table captions
    // ═══════════════════════════════════════════════════════════════════

    /** @param DOMElement[] $children */
    private function hoistTableCaptions(DOMElement $body, DOMXPath $xpath, array $children = []): void
    {
        if (empty($children)) {
            foreach ($body->childNodes as $child) {
                if ($child instanceof DOMElement) { $children[] = $child; }
            }
        }

        foreach ($children as $i => $child) {
            if ($child->localName !== 'tbl') { continue; }

            $captionNode   = null;
            $emptysBetween = [];

            for ($j = $i + 1; $j < count($children); $j++) {
                $sib = $children[$j];
                if ($sib->localName !== 'p') { break; }

                $text = '';
                foreach ($xpath->query('.//w:t', $sib) as $t) { $text .= $t->textContent; }
                $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

                if ($text === '') { $emptysBetween[] = $sib; continue; }

                if (preg_match('/^table\s+\d+[\-\.]\d+\b/iu', $text) === 1
                    && mb_strlen($text) <= 120
                    && preg_match('/^table\s+[\d\-\.]+\s+\w+\s+(presents|shows|describes|summarizes|lists|displays|illustrates|contains|provides|compares|indicates|reveals|demonstrates)\b/iu', $text) === 0
                ) {
                    $captionNode = $sib;
                }
                break;
            }

            if ($captionNode === null) { continue; }

            $body->insertBefore($captionNode, $child);
            foreach ($emptysBetween as $empty) {
                if ($empty->parentNode !== null) { $empty->parentNode->removeChild($empty); }
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Pre-pass B: table continuation
    // ═══════════════════════════════════════════════════════════════════

    /**
     * 1. Set w:tblHeader on first row of every table.
     * 2. Split tables at w:lastRenderedPageBreak (written by Word after rendering).
     * 3. Handle already-split pairs (two adjacent w:tbl with only empty/break
     *    paragraphs between them) — remove gap, fix borders, insert label.
     */
    /** @param DOMElement[] $scopedChildren */
    private function handleTableContinuation(DOMElement $body, DOMXPath $xpath, array $scopedChildren = []): void
    {
        $wNs = self::W_NS;

        // Remove any existing tblHeader from all scoped tables so no row is locked/uneditable
        foreach ($scopedChildren as $child) {
            if (!$child instanceof DOMElement || $child->localName !== 'tbl') { continue; }
            foreach ($xpath->query('w:tr/w:trPr/w:tblHeader', $child) as $th) {
                if ($th->parentNode instanceof DOMElement) {
                    $th->parentNode->removeChild($th);
                }
            }
        }

        // Step 2: handle already-split pairs within scoped children only
        $snap2 = $scopedChildren;
        $lastTableNumber = '';

        for ($i = 0; $i < count($snap2); $i++) {
            $child = $snap2[$i];

            if ($child->localName === 'p') {
                $text = '';
                foreach ($xpath->query('.//w:t', $child) as $t) { $text .= $t->textContent; }
                $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
                $m = [];
                if (preg_match('/^table\s+([\d]+[\-\.][\d]+)\b/iu', $text, $m)) {
                    $lastTableNumber = $m[1];
                }
                continue;
            }
            if ($child->localName !== 'tbl') { continue; }
            if ($child->parentNode === null)  { continue; }

            // Skip if already has a continuation label anywhere between this
            // table and the next non-empty sibling (doSplitTable inserts empty
            // paragraphs before the label, so we must look past them).
            $alreadyHasLabel = false;
            for ($k = $i + 1; $k < count($snap2); $k++) {
                $sib = $snap2[$k];
                if ($sib->localName === 'tbl') { break; }
                if ($sib->localName !== 'p') { break; }
                $t = '';
                foreach ($xpath->query('.//w:t', $sib) as $tn) { $t .= $tn->textContent; }
                if (trim($t) === '') { continue; } // empty paragraph — skip over
                if (preg_match('/^continuation\s+of\s+(table|figure)/iu', trim($t)) === 1) {
                    $alreadyHasLabel = true;
                }
                break;
            }
            if ($alreadyHasLabel) {
                // Label already exists — insert a page-break paragraph before
                // it so it starts at the top of the next page.
                for ($k = $i + 1; $k < count($snap2); $k++) {
                    $sib = $snap2[$k];
                    if ($sib->localName === 'tbl') { break; }
                    if ($sib->localName !== 'p') { break; }
                    $t = '';
                    foreach ($xpath->query('.//w:t', $sib) as $tn) { $t .= $tn->textContent; }
                    if (trim($t) === '') { continue; }
                    if (preg_match('/^continuation\s+of\s+(table|figure)/iu', trim($t)) === 1) {
                        $sib->parentNode->insertBefore(
                            $this->buildZeroHeightPageBreakP($body->ownerDocument), $sib
                        );
                    }
                    break;
                }
                continue;
            }

            // Look ahead past empty/break paragraphs for a second table.
            $between          = [];
            $nextTbl          = null;
            $hasExplicitBreak = false;
            for ($j = $i + 1; $j < count($snap2); $j++) {
                $sib = $snap2[$j];
                if ($sib->localName === 'tbl') {
                    $nextTbl = $sib;
                    $i = $j - 1;
                    break;
                }
                if ($sib->localName !== 'p') { break; }
                $t = '';
                foreach ($xpath->query('.//w:t', $sib) as $tn) { $t .= $tn->textContent; }
                $hasBreak = $xpath->query('.//w:br[@w:type="page"]', $sib)->length > 0;
                if ($hasBreak) { $hasExplicitBreak = true; }
                if (trim($t) === '' || $hasBreak) { $between[] = $sib; continue; }
                break; // non-empty paragraph between tables — stop looking
            }

            if ($nextTbl === null) { continue; }

            if (!$hasExplicitBreak) {
                // Two tables separated only by empty paragraphs — merge them into one.
                // Move all rows from the second table into the first, then remove the
                // second table and all gap paragraphs.
                $rows2 = [];
                foreach ($xpath->query('w:tr', $nextTbl) as $row) {
                    if ($row instanceof DOMElement) { $rows2[] = $row; }
                }
                foreach ($rows2 as $row) {
                    $child->appendChild($row);
                }
                foreach ($between as $gap) {
                    if ($gap->parentNode !== null) { $gap->parentNode->removeChild($gap); }
                }
                if ($nextTbl->parentNode !== null) {
                    $nextTbl->parentNode->removeChild($nextTbl);
                }
                $this->applyTableBordersSpec($child, $xpath);
                continue;
            }

            // Explicit page break in gap — tables are intentionally split by the author.
            // Remove the gap, apply borders, insert page-break + continuation label.
            foreach ($between as $gap) {
                if ($gap->parentNode !== null) { $gap->parentNode->removeChild($gap); }
            }

            $this->applyTableBordersSpec($child,   $xpath);
            $this->applyTableBordersSpec($nextTbl, $xpath);

            $label = $lastTableNumber !== ''
                ? 'Continuation of Table ' . $lastTableNumber . '...'
                : 'Continuation of Table...';

            $body->insertBefore($this->buildZeroHeightPageBreakP($body->ownerDocument), $nextTbl);
            $body->insertBefore($this->buildContinuationP($body->ownerDocument, $label), $nextTbl);
        }
    }

    /** @param DOMElement[] $rows */
    private function doSplitTable(
        DOMElement $tbl,
        array      $rows,
        int        $splitAt,
        DOMElement $body,
        DOMXPath   $xpath,
        string     $lastTableNumber
    ): void {
        $dom     = $tbl->ownerDocument;
        $wNs     = self::W_NS;
        $tblPr   = $this->getChild($tbl, 'tblPr');
        $tblGrid = null;
        foreach ($tbl->childNodes as $c) {
            if ($c instanceof DOMElement && $c->localName === 'tblGrid') {
                $tblGrid = $c; break;
            }
        }

        $tbl1 = $dom->createElementNS($wNs, 'w:tbl');
        $tbl2 = $dom->createElementNS($wNs, 'w:tbl');
        foreach ([$tbl1, $tbl2] as $half) {
            if ($tblPr   !== null) { $half->appendChild($tblPr->cloneNode(true)); }
            if ($tblGrid !== null) { $half->appendChild($tblGrid->cloneNode(true)); }
        }
        for ($i = 0; $i < $splitAt; $i++) {
            $tbl1->appendChild($rows[$i]->cloneNode(true));
        }
        for ($i = $splitAt; $i < count($rows); $i++) {
            $tbl2->appendChild($rows[$i]->cloneNode(true));
        }

        $this->applyTableBordersSpec($tbl1, $xpath);
        $this->applyTableBordersSpec($tbl2, $xpath);

        $body->insertBefore($tbl1, $tbl);
        // Insert empty paragraphs after tbl1 so the continuation label and tbl2
        // flow naturally to the next page without a hard page break.
        for ($e = 0; $e < 4; $e++) {
            $ep  = $dom->createElementNS($wNs, 'w:p');
            $ePr = $dom->createElementNS($wNs, 'w:pPr');
            $sp  = $dom->createElementNS($wNs, 'w:spacing');
            $sp->setAttributeNS($wNs, 'w:before',   '0');
            $sp->setAttributeNS($wNs, 'w:after',    '0');
            $sp->setAttributeNS($wNs, 'w:line',     '240');
            $sp->setAttributeNS($wNs, 'w:lineRule', 'auto');
            $ePr->appendChild($sp);
            $ep->appendChild($ePr);
            $body->insertBefore($ep, $tbl);
        }
        $body->insertBefore($tbl2, $tbl);
        $body->removeChild($tbl);
    }

    /**
     * Apply full table border spec:
     *   tblBorders: top=double, bottom=double, others=none
     *   Header cells bottom : single 0.5pt  (separator line under header row)
     *   Footer cells top    : none           (suppress inherited tblBorders top=double)
     *   Footer cells bottom : double 0.5pt   (explicit — ensures the outer bottom is
     *                                         always double even when Word ignores tblBorders
     *                                         in favour of cell-level resolution on split tables)
     *   All other cells     : tcBorders wiped
     */
    private function applyTableBordersSpec(DOMElement $tbl, DOMXPath $xpath): void
    {
        // Always de-float — continuation tables come here without going through processTable
        $this->deFloatTable($tbl);

        // Remove empty paragraphs inside cells
        foreach ($xpath->query('.//w:tc', $tbl) as $tc) {
            if (!$tc instanceof DOMElement) { continue; }
            $cellParas = [];
            foreach ($xpath->query('w:p', $tc) as $cp) {
                if ($cp instanceof DOMElement) { $cellParas[] = $cp; }
            }
            $nonEmpty = array_filter($cellParas, function ($cp) use ($xpath) {
                $t = '';
                foreach ($xpath->query('.//w:t', $cp) as $tn) { $t .= trim($tn->textContent ?? ''); }
                return $t !== '' || $xpath->query('.//w:drawing|.//w:pict', $cp)->length > 0;
            });
            if (count($nonEmpty) > 0) {
                foreach ($cellParas as $cp) {
                    $t = '';
                    foreach ($xpath->query('.//w:t', $cp) as $tn) { $t .= trim($tn->textContent ?? ''); }
                    if ($t === '' && $xpath->query('.//w:drawing|.//w:pict', $cp)->length === 0
                        && $cp->parentNode !== null) {
                        $cp->parentNode->removeChild($cp);
                    }
                }
            }
        }

        // Remove empty rows
        $allRows   = [];
        $emptyRows = [];
        foreach ($xpath->query('w:tr', $tbl) as $r) {
            if ($r instanceof DOMElement) { $allRows[] = $r; }
        }
        foreach ($allRows as $row) {
            $t = '';
            foreach ($xpath->query('.//w:t', $row) as $tn) { $t .= trim($tn->textContent ?? ''); }
            if ($t === '' && $xpath->query('.//w:drawing|.//w:pict', $row)->length === 0) {
                $emptyRows[] = $row;
            }
        }
        if (count($emptyRows) < count($allRows)) {
            foreach ($emptyRows as $row) {
                if ($row->parentNode !== null) { $row->parentNode->removeChild($row); }
            }
        }

        // Remove fixed row heights and set table to full page width
        foreach ($xpath->query('w:tr/w:trPr/w:trHeight', $tbl) as $trH) {
            if ($trH instanceof DOMElement && $trH->parentNode) {
                $trH->parentNode->removeChild($trH);
            }
        }
        $tblPrW = $this->ensureChild($tbl, 'tblPr');
        $this->removeChildren($tblPrW, 'tblW');
        $tblW = $tbl->ownerDocument->createElementNS(self::W_NS, 'w:tblW');
        $tblW->setAttributeNS(self::W_NS, 'w:type', 'pct');
        $tblW->setAttributeNS(self::W_NS, 'w:w',    '5000');
        $tblPrW->appendChild($tblW);
        foreach ($xpath->query('w:tr/w:tc/w:tcPr/w:tcW', $tbl) as $tcW) {
            if ($tcW instanceof DOMElement && $tcW->parentNode) {
                $tcW->parentNode->removeChild($tcW);
            }
        }

        // ── Step 1: Rebuild tblBorders — top+bottom always double ─────
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

        $makeBorder('top',     'double', '4');
        $makeBorder('left',    'none',   '0');
        $makeBorder('bottom',  'double', '4');
        $makeBorder('right',   'none',   '0');
        $makeBorder('insideH', 'none',   '0');
        $makeBorder('insideV', 'none',   '0');

        // ── Step 2: Get direct rows only ──────────────────────────────
        $rows     = $xpath->query('w:tr', $tbl);
        $rowCount = $rows->length;
        if ($rowCount === 0) { return; }

        $firstRow = $rows->item(0);
        $lastRow  = $rows->item($rowCount - 1);

        $wNs = self::W_NS;

        // ── Step 3: Wipe tcBorders on all cells EXCEPT the last row ──────────
        // The last row's top border may be user-defined (e.g. a single line above
        // a totals/footer row). We preserve it and handle it explicitly in Step 5.
        foreach ($rows as $row) {
            if (!$row instanceof DOMElement) { continue; }
            if ($row === $lastRow) { continue; }   // handled in Step 5
            foreach ($xpath->query('w:tc', $row) as $tc) {
                if (!$tc instanceof DOMElement) { continue; }
                $tcPr = $this->getChild($tc, 'tcPr');
                if ($tcPr instanceof DOMElement) {
                    $this->removeChildren($tcPr, 'tcBorders');
                }
            }
        }

        // ── Step 4: Header row — bottom single 0.5pt ──────────────────
        if ($firstRow instanceof DOMElement) {
            foreach ($xpath->query('w:tc', $firstRow) as $tc) {
                if (!$tc instanceof DOMElement) { continue; }
                $tcPr = $this->getChild($tc, 'tcPr');
                if ($tcPr === null) {
                    $tcPr = $tc->ownerDocument->createElementNS($wNs, 'w:tcPr');
                    $tc->insertBefore($tcPr, $tc->firstChild);
                }
                $tcBdr = $tc->ownerDocument->createElementNS($wNs, 'w:tcBorders');
                $tcPr->appendChild($tcBdr);
                $el = $tc->ownerDocument->createElementNS($wNs, 'w:bottom');
                $el->setAttributeNS($wNs, 'w:val',   'single');
                $el->setAttributeNS($wNs, 'w:sz',    '4');
                $el->setAttributeNS($wNs, 'w:space', '0');
                $el->setAttributeNS($wNs, 'w:color', '000000');
                $tcBdr->appendChild($el);
            }
        }

        // ── Step 5: Footer row — preserve user top border, force double bottom ─
        //
        // top  : if the user already set a top border on the footer cells, keep it.
        //        If not, write top=none to suppress tblBorders top=double inheritance.
        //
        // bottom=double : explicit cell-level override ensures the outer bottom
        //                 border is always double even when Word ignores tblBorders.
        if ($lastRow instanceof DOMElement && $rowCount > 1) {
            foreach ($xpath->query('w:tc', $lastRow) as $tc) {
                if (!$tc instanceof DOMElement) { continue; }
                $tcPr = $this->getChild($tc, 'tcPr');
                if ($tcPr === null) {
                    $tcPr = $tc->ownerDocument->createElementNS($wNs, 'w:tcPr');
                    $tc->insertBefore($tcPr, $tc->firstChild);
                }

                // Read existing top border before wiping (user-defined)
                $existingTopVal = null;
                $existingTopSz  = null;
                $existingTopClr = null;
                $existingTopSpc = null;
                $oldTcBdr = $this->getChild($tcPr, 'tcBorders');
                if ($oldTcBdr instanceof DOMElement) {
                    foreach ($oldTcBdr->childNodes as $bdrChild) {
                        if ($bdrChild instanceof DOMElement
                            && $bdrChild->namespaceURI === $wNs
                            && $bdrChild->localName === 'top'
                        ) {
                            $existingTopVal = $bdrChild->getAttributeNS($wNs, 'val');
                            $existingTopSz  = $bdrChild->getAttributeNS($wNs, 'sz');
                            $existingTopClr = $bdrChild->getAttributeNS($wNs, 'color');
                            $existingTopSpc = $bdrChild->getAttributeNS($wNs, 'space');
                            break;
                        }
                    }
                }
                $this->removeChildren($tcPr, 'tcBorders');

                $tcBdr = $tc->ownerDocument->createElementNS($wNs, 'w:tcBorders');
                $tcPr->appendChild($tcBdr);

                // Top: restore user-defined border or suppress tblBorders inheritance
                $elTop = $tc->ownerDocument->createElementNS($wNs, 'w:top');
                if ($existingTopVal !== null && $existingTopVal !== '' && $existingTopVal !== 'none') {
                    $elTop->setAttributeNS($wNs, 'w:val',   $existingTopVal);
                    $elTop->setAttributeNS($wNs, 'w:sz',    $existingTopSz  ?: '4');
                    $elTop->setAttributeNS($wNs, 'w:space', $existingTopSpc ?: '0');
                    $elTop->setAttributeNS($wNs, 'w:color', $existingTopClr ?: '000000');
                } else {
                    $elTop->setAttributeNS($wNs, 'w:val',   'none');
                    $elTop->setAttributeNS($wNs, 'w:sz',    '0');
                    $elTop->setAttributeNS($wNs, 'w:space', '0');
                    $elTop->setAttributeNS($wNs, 'w:color', 'auto');
                }
                $tcBdr->appendChild($elTop);

                // Bottom: always explicit double to guarantee outer border
                $elBot = $tc->ownerDocument->createElementNS($wNs, 'w:bottom');
                $elBot->setAttributeNS($wNs, 'w:val',   'double');
                $elBot->setAttributeNS($wNs, 'w:sz',    '4');
                $elBot->setAttributeNS($wNs, 'w:space', '0');
                $elBot->setAttributeNS($wNs, 'w:color', '000000');
                $tcBdr->appendChild($elBot);
            }
        }
    }

    private function buildContinuationP(DOMDocument $dom, string $label): DOMElement
    {
        $wNs   = self::W_NS;
        $contP = $dom->createElementNS($wNs, 'w:p');
        $pPr   = $dom->createElementNS($wNs, 'w:pPr');
        $contP->appendChild($pPr);

        // NO pageBreakBefore here — we insert a separate zero-height page-break
        // paragraph before this one instead (see doSplitTable / handleTableContinuation).
        // pageBreakBefore bleeds into adjacent paragraphs; w:br does not.

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

        // Build Garamond 13pt italic rPr
        $makeRPr = static function () use ($dom, $wNs): DOMElement {
            $rPr    = $dom->createElementNS($wNs, 'w:rPr');
            $rFonts = $dom->createElementNS($wNs, 'w:rFonts');
            $rFonts->setAttributeNS($wNs, 'w:ascii',    'Garamond');
            $rFonts->setAttributeNS($wNs, 'w:hAnsi',    'Garamond');
            $rFonts->setAttributeNS($wNs, 'w:eastAsia', 'Garamond');
            $rFonts->setAttributeNS($wNs, 'w:cs',       'Garamond');
            $rPr->appendChild($rFonts);
            $rPr->appendChild($dom->createElementNS($wNs, 'w:i'));
            $rPr->appendChild($dom->createElementNS($wNs, 'w:iCs'));
            $sz = $dom->createElementNS($wNs, 'w:sz');
            $sz->setAttributeNS($wNs, 'w:val', '26');
            $sc = $dom->createElementNS($wNs, 'w:szCs');
            $sc->setAttributeNS($wNs, 'w:val', '26');
            $rPr->appendChild($sz);
            $rPr->appendChild($sc);
            return $rPr;
        };

        $pPr->appendChild($makeRPr());

        $run = $dom->createElementNS($wNs, 'w:r');
        $run->appendChild($makeRPr());
        $tEl = $dom->createElementNS($wNs, 'w:t');
        $tEl->textContent = $label;
        $run->appendChild($tEl);
        $contP->appendChild($run);

        return $contP;
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
        $isReferences    = preg_match('/^references$/iu', $normalized) === 1;
        $isAppendixLabel = preg_match('/^appendix(?:es)?(\s+[a-zA-Z0-9])?$/iu', $normalized) === 1;

        // Entering chapters zone
        if ($isChapterLabel) {
            $state['zone']               = 'chapters';
            $state['currentChapter']     = $this->chapterToInt($chapterMatch[1]);
            $state['expectChapterTitle'] = true;
            $state['isFirstParagraph']   = true;
        } elseif ($isReferences) {
            $state['zone']             = 'references';
            $state['isFirstParagraph'] = true;
        } elseif ($isAppendixLabel) {
            // Appendices start — exit active zone, stop formatting
            $state['zone'] = 'appendices';
        }

        // Only format chapters and references — skip everything else
        $zone = (string)$state['zone'];
        if ($zone !== 'chapters' && $zone !== 'references') { return; }

        $hasDrawing = $xpath->query('.//w:drawing | .//w:pict', $p)->length > 0;

        if ($hasDrawing && $normalized === '' && !$this->isInTable($xpath, $p)) {
            $state['afterTable']       = false;
            $state['isFirstParagraph'] = false;
            $this->applyFigureParagraph($xpath, $p);
            return;
        }

        if ($normalized === '') {
            // In the references section, empty paragraphs are intentional blank lines
            // between entries — give them a visible line height (11pt, 1.0×).
            // Everywhere else collapse them to zero so they add no visual space.
            if ((string)$state['zone'] === 'references') {
                $this->applyReferenceEmptyLine($xpath, $p);
            } else {
                $this->applyEmptyParagraph($xpath, $p);
            }
            // Do NOT reset afterTable — empty paragraphs between a table and the
            // first real paragraph must not consume the flag or add visual space.
            return;
        }

        $hasNumbering = $xpath->query('./w:pPr/w:numPr', $p)->length > 0;
        $isInTable    = $this->isInTable($xpath, $p);

        $isFigureCaption = !$state['isFirstParagraph']
            && preg_match('/^figure\s+\d+[\-\.]\d+\b/iu', $normalized) === 1
            && mb_strlen($normalized) <= 120
            && preg_match('/^figure\s+[\d\-\.]+\s+\w+\s+(presents|shows|describes|summarizes|lists|displays|illustrates|contains|provides|compares|indicates|reveals|demonstrates)\b/iu', $normalized) === 0;

        $isTableCaption = !$state['isFirstParagraph']
            && preg_match('/^table\s+\d+[\-\.]\d+\b/iu', $normalized) === 1
            && mb_strlen($normalized) <= 120
            && preg_match('/^table\s+[\d\-\.]+\s+\w+\s+(presents|shows|describes|summarizes|lists|displays|illustrates|contains|provides|compares|indicates|reveals|demonstrates)\b/iu', $normalized) === 0;

        $isContinuation = preg_match(
            '/^continuation\s+of\s+(table|figure)(\s+[\d\-\.]+)?/iu', $normalized
        ) === 1;

        $isLegend = preg_match('/^legend\s*:/iu', $normalized) === 1;

        if ($styleId === 'Heading1') {
            if ($isChapterLabel) {
                $state['afterTable'] = false;
                $this->applyChapterLabel($xpath, $p);
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
            if ($isReferences) {
                $state['afterTable'] = false;
                $this->applyReferencesTitle($xpath, $p);
                return;
            }
            // Any other Heading1 in chapters zone — treat as chapter title
            $state['afterTable'] = false;
            $this->applyChapterTitle($xpath, $p);
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

        if ($isChapterLabel) {
            $state['afterTable'] = false;
            $this->applyChapterLabel($xpath, $p);
            return;
        }

        if ($isReferences) {
            $state['afterTable'] = false;
            $this->applyReferencesTitle($xpath, $p);
            return;
        }

        $state['isFirstParagraph'] = false;

        if ($hasDrawing) {
            $state['afterTable'] = false;
            $this->applyFigureParagraph($xpath, $p);
            return;
        }

        if ($isFigureCaption) {
            $state['afterTable'] = false;
            $this->applyFigureCaption($xpath, $p);
            return;
        }

        if ($isTableCaption) {
            $state['afterTable'] = false;
            $m = [];
            if (preg_match('/^table\s+([\d]+[\-\.][\d]+)\b/iu', $normalized, $m)) {
                $state['lastTableNumber'] = $m[1];
            }
            $this->applyTableCaption($xpath, $p);
            return;
        }

        if ($isContinuation) {
            $this->applyContinuationLabel($xpath, $p, (string)$state['lastTableNumber']);
            return;
        }

        if ($isLegend) {
            $this->applyLegend($xpath, $p);
            return;
        }

        if ($isInTable) { return; }

        // In the references zone every content paragraph is a reference entry —
        // regardless of whether it starts with [1], "1.", or has no number at all.
        if ((string)$state['zone'] === 'references') {
            $state['afterTable'] = false;
            $this->applyReferenceEntry($xpath, $p);
            return;
        }

        $currentChapter = (int)($state['currentChapter'] ?? 0);

        // Helper: decide which formatter to use based on chapter and text.
        // Chapter 2 non-Synthesis headings → italic heading (12pt bold+italic).
        // Everything else → normal heading (13pt bold).
        $applyCorrectHeading = function () use ($xpath, $p, $normalized, $currentChapter, $state): void {
            if ($currentChapter === 2 && preg_match('/^synthesis\b/iu', $normalized) !== 1) {
                $this->applyItalicHeading($xpath, $p);
            } else {
                $this->applyHeading($xpath, $p);
            }
        };

        // Numbered section headings (e.g. "4.1 Identify Costs") must be checked
        // BEFORE the hasNumbering gate — Word sometimes stores these with a numPr
        // which would otherwise send them to applyListParagraph incorrectly.
        if (preg_match('/^\d+\.\d+(\.\d+)*(\s+\S.*)?$/u', $normalized) === 1) {
            $state['afterTable'] = false;
            $applyCorrectHeading();
            return;
        }

        // Ch4/Ch5 numbered bold objectives: if the paragraph starts with "N. ",
        // is in chapter 4 or 5, and the runs are bold at 13pt (sz=26) — it's a heading.
        if (in_array($currentChapter, [4, 5], true)
            && preg_match('/^\d+\.\s+\S/u', $normalized) === 1
            && preg_match('/^\d+\.\s+\S+\s*[—–-]/u', $normalized) === 0
        ) {
            $boldSz26 = false;
            foreach ($xpath->query('.//w:r', $p) as $run) {
                if (!$run instanceof DOMElement) { continue; }
                if ($xpath->query('.//w:drawing|.//w:pict|.//w:instrText|.//w:fldChar', $run)->length > 0) { continue; }
                $tEl = $run->getElementsByTagNameNS(self::W_NS, 't')->item(0);
                if (!$tEl instanceof DOMElement || trim($tEl->textContent ?? '') === '') { continue; }
                $rPr  = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
                $bold = $rPr instanceof DOMElement && $rPr->getElementsByTagNameNS(self::W_NS, 'b')->length > 0;
                $szEl = $rPr instanceof DOMElement ? $rPr->getElementsByTagNameNS(self::W_NS, 'sz')->item(0) : null;
                $sz   = $szEl instanceof DOMElement ? $szEl->getAttributeNS(self::W_NS, 'val') : '';
                if ($bold && $sz === '26') { $boldSz26 = true; break; }
            }
            if ($boldSz26) {
                $state['afterTable'] = false;
                $applyCorrectHeading();
                return;
            }
        }

        if ($hasNumbering || $styleId === 'ListParagraph') {
            $state['afterTable'] = false;
            $this->applyListParagraph($xpath, $p);
            return;
        }

        if ($this->isHeadingStyleId($styleId)) {
            $state['afterTable'] = false;
            $applyCorrectHeading();
            return;
        }

        // Determine if this paragraph is any kind of heading.
        // isItalicHeading includes Pattern D (numbered bold objectives) which only
        // applies in chapters 4 and 5 — in all other chapters those are list items.
        $looksLikeItalic  = in_array($currentChapter, [2, 4, 5], true)
                            && $this->isItalicHeading($xpath, $p, $normalized);
        $looksLikeHeading = $looksLikeItalic || $this->isHeading($xpath, $p, $normalized);

        if ($looksLikeHeading) {
            $state['afterTable'] = false;
            $applyCorrectHeading();
            return;
        }

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
        $zone = (string)($state['zone'] ?? '');
        if ($zone !== 'chapters' && $zone !== 'references') { return; }

        // ── Remove empty paragraphs inside cells ─────────────────────────────
        foreach ($xpath->query('.//w:tc', $tbl) as $tc) {
            if (!$tc instanceof DOMElement) { continue; }
            $cellParas = [];
            foreach ($xpath->query('w:p', $tc) as $cp) {
                if ($cp instanceof DOMElement) { $cellParas[] = $cp; }
            }
            // Keep at least one paragraph per cell (Word requires it)
            $nonEmpty = array_filter($cellParas, function ($cp) use ($xpath) {
                $t = '';
                foreach ($xpath->query('.//w:t', $cp) as $tn) { $t .= trim($tn->textContent ?? ''); }
                $hasDrawing = $xpath->query('.//w:drawing|.//w:pict', $cp)->length > 0;
                return $t !== '' || $hasDrawing;
            });
            if (count($nonEmpty) > 0) {
                foreach ($cellParas as $cp) {
                    $t = '';
                    foreach ($xpath->query('.//w:t', $cp) as $tn) { $t .= trim($tn->textContent ?? ''); }
                    $hasDrawing = $xpath->query('.//w:drawing|.//w:pict', $cp)->length > 0;
                    if ($t === '' && !$hasDrawing && $cp->parentNode !== null) {
                        $cp->parentNode->removeChild($cp);
                    }
                }
            }
        }

        // ── Remove empty rows (all cells empty after paragraph cleanup) ────────
        $allRows = [];
        foreach ($xpath->query('w:tr', $tbl) as $r) {
            if ($r instanceof DOMElement) { $allRows[] = $r; }
        }
        $emptyRows = [];
        foreach ($allRows as $row) {
            $text = '';
            foreach ($xpath->query('.//w:t', $row) as $tn) { $text .= trim($tn->textContent ?? ''); }
            $hasDrawing = $xpath->query('.//w:drawing|.//w:pict', $row)->length > 0;
            if ($text === '' && !$hasDrawing) { $emptyRows[] = $row; }
        }
        // Never remove all rows
        if (count($emptyRows) < count($allRows)) {
            foreach ($emptyRows as $row) {
                if ($row->parentNode !== null) { $row->parentNode->removeChild($row); }
            }
        }

        // ── De-float: remove ALL positioning properties, force inline ─────────
        $this->deFloatTable($tbl);

        // ── Compress: remove fixed row heights so rows shrink to content ─────
        foreach ($xpath->query('w:tr/w:trPr/w:trHeight', $tbl) as $trH) {
            if ($trH instanceof DOMElement && $trH->parentNode) {
                $trH->parentNode->removeChild($trH);
            }
        }

        // ── Set table to full page width (9360 twips = 6.5 inches) ───────────
        $tblPrForWidth = $this->ensureChild($tbl, 'tblPr');
        $this->removeChildren($tblPrForWidth, 'tblW');
        $tblW = $tbl->ownerDocument->createElementNS(self::W_NS, 'w:tblW');
        $tblW->setAttributeNS(self::W_NS, 'w:type', 'pct');
        $tblW->setAttributeNS(self::W_NS, 'w:w',    '5000');
        $tblPrForWidth->appendChild($tblW);

        // Remove fixed cell widths so columns auto-distribute across full width
        foreach ($xpath->query('w:tr/w:tc/w:tcPr/w:tcW', $tbl) as $tcW) {
            if ($tcW instanceof DOMElement && $tcW->parentNode) {
                $tcW->parentNode->removeChild($tcW);
            }
        }

        if (isset($this->rules['borders'])) {
            $this->applyTableBordersSpec($tbl, $xpath);
        }

        foreach ($xpath->query('.//w:tc', $tbl) as $tc) {
            if (!$tc instanceof DOMElement) { continue; }
            foreach ($xpath->query('.//w:p', $tc) as $p) {
                if (!$p instanceof DOMElement) { continue; }
                $isNumbered = $xpath->query('./w:pPr/w:numPr', $p)->length > 0;
                if (isset($this->rules['alignment'])) {
                    $this->writePAlignment($p, $isNumbered ? 'both' : 'center');
                }
                // before=0, after=0, line=240 — no extra spacing inside cells
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

    // ═══════════════════════════════════════════════════════════════════
    // Paragraph formatters
    // ═══════════════════════════════════════════════════════════════════

    private function applyEmptyParagraph(DOMXPath $xpath, DOMElement $p): void
    {
        $pPr = $this->ensurePPr($p);
        foreach (['pStyle', 'ind', 'spacing', 'jc', 'rPr', 'widowControl',
                  'pageBreakBefore', 'keepNext', 'keepLines', 'outlineLvl',
                  'contextualSpacing', 'snapToGrid'] as $tag) {
            $this->removeChildren($pPr, $tag);
        }
        // Use sz=2 (1pt) so the line collapses to near-zero height.
        // before=0/after=0 kills paragraph spacing.
        $sp = $p->ownerDocument->createElementNS(self::W_NS, 'w:spacing');
        $sp->setAttributeNS(self::W_NS, 'w:before',            '0');
        $sp->setAttributeNS(self::W_NS, 'w:after',             '0');
        $sp->setAttributeNS(self::W_NS, 'w:line',              '20');
        $sp->setAttributeNS(self::W_NS, 'w:lineRule',          'exact');
        $sp->setAttributeNS(self::W_NS, 'w:beforeAutospacing', '0');
        $sp->setAttributeNS(self::W_NS, 'w:afterAutospacing',  '0');
        $pPr->appendChild($sp);
        $cs = $p->ownerDocument->createElementNS(self::W_NS, 'w:contextualSpacing');
        $cs->setAttributeNS(self::W_NS, 'w:val', '1');
        $pPr->appendChild($cs);
        // Paragraph-mark rPr: sz=2 so the line box itself is 1pt tall
        $rPr    = $p->ownerDocument->createElementNS(self::W_NS, 'w:rPr');
        $rFonts = $p->ownerDocument->createElementNS(self::W_NS, 'w:rFonts');
        foreach (['w:ascii','w:hAnsi','w:eastAsia','w:cs'] as $attr) {
            $rFonts->setAttributeNS(self::W_NS, $attr, 'Garamond');
        }
        $rPr->appendChild($rFonts);
        $szEl = $p->ownerDocument->createElementNS(self::W_NS, 'w:sz');
        $szEl->setAttributeNS(self::W_NS, 'w:val', '2');
        $szCs = $p->ownerDocument->createElementNS(self::W_NS, 'w:szCs');
        $szCs->setAttributeNS(self::W_NS, 'w:val', '2');
        $rPr->appendChild($szEl);
        $rPr->appendChild($szCs);
        $pPr->appendChild($rPr);
        // Also collapse any existing runs
        foreach ($xpath->query('.//w:r', $p) as $run) {
            if (!$run instanceof DOMElement) { continue; }
            $this->removeChildren($run, 'rPr');
            $rr    = $p->ownerDocument->createElementNS(self::W_NS, 'w:rPr');
            $szR   = $p->ownerDocument->createElementNS(self::W_NS, 'w:sz');
            $szR->setAttributeNS(self::W_NS, 'w:val', '2');
            $szCsR = $p->ownerDocument->createElementNS(self::W_NS, 'w:szCs');
            $szCsR->setAttributeNS(self::W_NS, 'w:val', '2');
            $rr->appendChild($szR);
            $rr->appendChild($szCsR);
            $run->insertBefore($rr, $run->firstChild);
        }
    }

    private function applyReferenceEmptyLine(DOMXPath $xpath, DOMElement $p): void
    {
        // Visible blank line between reference entries — 11pt Garamond, 1.0× line spacing,
        // no before/after spacing. Matches the entry line height exactly.
        $pPr = $this->ensurePPr($p);
        foreach (['pStyle', 'ind', 'spacing', 'jc', 'rPr', 'widowControl',
                  'pageBreakBefore', 'keepNext', 'keepLines', 'outlineLvl',
                  'contextualSpacing', 'snapToGrid'] as $tag) {
            $this->removeChildren($pPr, $tag);
        }
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
        foreach (['w:ascii', 'w:hAnsi', 'w:eastAsia', 'w:cs'] as $attr) {
            $rFonts->setAttributeNS(self::W_NS, $attr, 'Garamond');
        }
        $rPr->appendChild($rFonts);
        $szEl = $p->ownerDocument->createElementNS(self::W_NS, 'w:sz');
        $szEl->setAttributeNS(self::W_NS, 'w:val', '22');
        $szCs = $p->ownerDocument->createElementNS(self::W_NS, 'w:szCs');
        $szCs->setAttributeNS(self::W_NS, 'w:val', '22');
        $rPr->appendChild($szEl);
        $rPr->appendChild($szCs);
        $pPr->appendChild($rPr);
    }

    private function buildZeroHeightPageBreakP(DOMDocument $dom): DOMElement
    {
        // A paragraph that only contains a page-break run and has zero visual
        // height. Using w:br type="page" inside a run is self-contained —
        // unlike w:pageBreakBefore it does NOT bleed into the next paragraph.
        $wNs = self::W_NS;
        $p   = $dom->createElementNS($wNs, 'w:p');
        $pPr = $dom->createElementNS($wNs, 'w:pPr');
        $p->appendChild($pPr);
        $sp = $dom->createElementNS($wNs, 'w:spacing');
        $sp->setAttributeNS($wNs, 'w:before',   '0');
        $sp->setAttributeNS($wNs, 'w:after',    '0');
        $sp->setAttributeNS($wNs, 'w:line',     '20');
        $sp->setAttributeNS($wNs, 'w:lineRule', 'exact');
        $pPr->appendChild($sp);
        $rPr  = $dom->createElementNS($wNs, 'w:rPr');
        $szEl = $dom->createElementNS($wNs, 'w:sz');
        $szEl->setAttributeNS($wNs, 'w:val', '2');
        $szCs = $dom->createElementNS($wNs, 'w:szCs');
        $szCs->setAttributeNS($wNs, 'w:val', '2');
        $rPr->appendChild($szEl);
        $rPr->appendChild($szCs);
        $pPr->appendChild($rPr);
        $run = $dom->createElementNS($wNs, 'w:r');
        $br  = $dom->createElementNS($wNs, 'w:br');
        $br->setAttributeNS($wNs, 'w:type', 'page');
        $run->appendChild($br);
        $p->appendChild($run);
        return $p;
    }

    private function ensureTrailingPeriod(DOMXPath $xpath, DOMElement $p): void
    {
        // Find the last text run and append a period if it doesn't already end with one.
        $lastTEl = null;
        foreach ($xpath->query('.//w:r[not(.//w:drawing) and not(.//w:pict)]/w:t', $p) as $t) {
            $lastTEl = $t;
        }
        if ($lastTEl === null) { return; }
        $text = $lastTEl->textContent ?? '';
        if (!str_ends_with(rtrim($text), '.')) {
            $lastTEl->textContent = rtrim($text) . '.';
        }
    }

    private function stripLeadingTabRuns(DOMXPath $xpath, DOMElement $p): void
    {
        // Remove any leading runs that contain only a w:tab element.
        // These are manual tab characters the author used for indentation
        // before we apply proper firstLine indent — leaving them causes
        // double indentation.
        foreach ($xpath->query('./w:r', $p) as $run) {
            if (!$run instanceof DOMElement) { break; }
            // If this run has a w:tab and no w:t text content, remove it
            $hasTab  = $xpath->query('./w:tab', $run)->length > 0;
            $hasTxt  = $xpath->query('./w:t',   $run)->length > 0;
            $hasOther = $xpath->query(
                './w:drawing|./w:pict|./w:instrText|./w:fldChar|./w:br', $run
            )->length > 0;
            if ($hasTab && !$hasTxt && !$hasOther) {
                $run->parentNode->removeChild($run);
            } else {
                break; // stop at the first non-tab run
            }
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
        // Spec: Garamond 14pt (sz=28), bold, uppercase, centered,
        //       3.0 line spacing (line=720), 0 before, 0 after, no indent.
        $this->stripAll($xpath, $p);
        $this->uppercaseParagraphText($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'center'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 720); }
        $this->writeRuns($xpath, $p, 'Garamond', 28, true, false);
        $this->writePPrRPr($p, 'Garamond', 28, true, false);
    }

    /**
     * Unified heading formatter.
     * Spec: Garamond 13pt (sz=26), bold, left-aligned, 2.0 line spacing (480),
     *       0 before, 0 after, first-line indent = 0.
     * Covers H2–H9 style headings, bold-only headings, and bold+italic inline headings.
     */
    private function applyHeading(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        $this->stripLeadingTabRuns($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }
        $this->writeRuns($xpath, $p, 'Garamond', 26, true, false);
        $this->writePPrRPr($p, 'Garamond', 26, true, false);
    }

    // Aliases so all existing call-sites resolve to the unified spec
    private function applyHeading2(DOMXPath $xpath, DOMElement $p): void      { $this->applyHeading($xpath, $p); }
    private function applyBoldHeading(DOMXPath $xpath, DOMElement $p): void   { $this->applyHeading($xpath, $p); }

    /**
     * Italic heading formatter — Chapter 2 inline headings and Chapter 4/5 bold+italic headings.
     * Spec: Garamond 12pt (sz=24), bold, italic, left-aligned, 2.0 line spacing (480),
     *       0 before, 0 after, first-line indent = 0.
     */
    private function applyItalicHeading(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        $this->stripLeadingTabRuns($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }

        // Force all indent attributes to zero explicitly
        $pPr = $this->ensurePPr($p);
        $this->removeChildren($pPr, 'ind');
        $ind = $p->ownerDocument->createElementNS(self::W_NS, 'w:ind');
        $ind->setAttributeNS(self::W_NS, 'w:firstLine', '0');
        $ind->setAttributeNS(self::W_NS, 'w:hanging',   '0');
        $ind->setAttributeNS(self::W_NS, 'w:left',      '0');
        $ind->setAttributeNS(self::W_NS, 'w:right',     '0');
        $pPr->appendChild($ind);

        $this->writeRuns($xpath, $p, 'Garamond', 24, true, true);
        $this->writePPrRPr($p, 'Garamond', 24, true, true);
    }

    // Keep old alias pointing to italic variant (was the original applyInlineHeading behaviour)
    private function applyInlineHeading(DOMXPath $xpath, DOMElement $p): void { $this->applyItalicHeading($xpath, $p); }

    private function applyBodyParagraph(DOMXPath $xpath, DOMElement $p, int $beforeSpacing = 0): void
    {
        $this->stripAll($xpath, $p);
        $this->stripLeadingTabRuns($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'both'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 720); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, $beforeSpacing, 0, 480); }
        $this->removePBdr($p);
        $this->writeRuns($xpath, $p, 'Garamond', 24, null, null);
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
    }

    private function applyReferenceEntry(DOMXPath $xpath, DOMElement $p): void
    {
        // Spec: Garamond 11pt (sz=22), justified, hanging indent 1.27cm (720 twips),
        //       1.0 line spacing (line=240), 0 before, 0 after.
        // The one blank line between entries is an actual empty paragraph the author typed.
        $this->stripAll($xpath, $p);
        $this->stripLeadingTabRuns($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'both'); }
        if (isset($this->rules['indentation'])) { $this->writePHangingIndent($p, 720, 720); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        $this->writeRuns($xpath, $p, 'Garamond', 22, false, false);
        $this->writePPrRPr($p, 'Garamond', 22, false, false);
    }

    private function applyListParagraph(DOMXPath $xpath, DOMElement $p): void
    {
        // Body-level list items: Garamond 12pt, 2.0 line spacing, justified.
        // Keep numPr (list numbering) — do not strip it.
        $pPr = $this->getChild($p, 'pPr');
        if ($pPr instanceof DOMElement) {
            foreach (['pStyle', 'rPr', 'widowControl', 'ind', 'spacing',
                      'jc', 'pageBreakBefore', 'keepNext', 'keepLines'] as $tag) {
                $this->removeChildren($pPr, $tag);
            }
        }
        $this->stripLeadingTabRuns($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'both'); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 480); }
        $this->writeRuns($xpath, $p, 'Garamond', 24, false, false);
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
    }

    private function applyFigureParagraph(DOMXPath $xpath, DOMElement $p): void
    {
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
        $this->ensureTrailingPeriod($xpath, $p);
        $this->writeRuns($xpath, $p, 'Garamond', 24, false, false);
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
    }

    private function applyTableCaption(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        $this->removePBdr($p);
        $this->ensureTrailingPeriod($xpath, $p);
        $this->writeRuns($xpath, $p, 'Garamond', 24, false, false);
        $this->writePPrRPr($p, 'Garamond', 24, false, false);
    }

    private function applyLegend(DOMXPath $xpath, DOMElement $p): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }
        $this->writeRuns($xpath, $p, 'Garamond', 22, false, false);
        $this->writePPrRPr($p, 'Garamond', 22, false, false);
    }

    private function applyContinuationLabel(DOMXPath $xpath, DOMElement $p, string $tableNumber = ''): void
    {
        $this->stripAll($xpath, $p);
        if (isset($this->rules['alignment']))   { $this->writePAlignment($p, 'left'); }
        if (isset($this->rules['indentation'])) { $this->writePIndent($p, 0); }
        if (isset($this->rules['spacing']))     { $this->writePSpacing($p, 0, 0, 240); }

        // Remove any w:br type="page" runs left from old page-break approach
        foreach ($xpath->query('.//w:br[@w:type="page"]', $p) as $br) {
            if ($br->parentNode instanceof DOMElement) {
                $br->parentNode->parentNode?->removeChild($br->parentNode);
            }
        }

        if ($tableNumber !== '') {
            $canonical = 'Continuation of Table ' . $tableNumber . '...';
            $toRemove  = [];
            foreach ($xpath->query('.//w:r', $p) as $run) {
                if ($run instanceof DOMElement) { $toRemove[] = $run; }
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

    // ─────────────────────────────────────────────────────────────────
    // Heading detection
    // ─────────────────────────────────────────────────────────────────

    /**
     * Returns true when the paragraph's named style is any heading level H2–H9.
     * Word uses several naming conventions for the same logical style, so we
     * check all of them.
     */
    private function isHeadingStyleId(string $styleId): bool
    {
        if ($styleId === '') { return false; }
        // Canonical Word names: Heading2 … Heading9
        if (preg_match('/^Heading[2-9]$/i', $styleId) === 1) { return true; }
        // Short numeric aliases Word sometimes stores: 2 … 9
        if (preg_match('/^[2-9]$/', $styleId) === 1) { return true; }
        // Lower-case variants: heading2 … heading9
        if (preg_match('/^heading[2-9]$/i', $styleId) === 1) { return true; }
        return false;
    }

    /**
     * Heuristic heading detection for paragraphs that carry no heading style.
     *
     * A paragraph is treated as a heading when it passes ALL of these gates:
     *   1. Not empty, not ending in sentence-ending punctuation.
     *   2. No first-line indent (body paragraphs are indented).
     *   3. Not a known special prefix (figure / table / chapter / appendix /
     *      continuation / legend / reference entries).
     *   4. ≤ 150 characters (headings are short).
     *   5. Matches one of:
     *        a. Numbered-section pattern  — "1.", "1.1", "1.1.1", "2.3.4" …
     *           optionally followed by text, e.g. "2.1 Background"
     *        b. Bold-majority             — ≥ 60 % of text runs are bold
     *        c. Bold+italic               — at least one bold+italic run and
     *                                       ≤ 20 % bold-only runs
     */
    private function isHeading(DOMXPath $xpath, DOMElement $p, string $text): bool
    {
        if ($text === '') { return false; }
        // Gate 1: no sentence-ending punctuation
        if (preg_match('/[.!?]$/u', $text) === 1) { return false; }
        // Gate 3: exclude known special prefixes
        if (preg_match(
            '/^(figure|fig\.?|table|chapter|appendix|continuation|legend)\b/iu', $text
        ) === 1) { return false; }
        // Exclude bracket-style reference entries "[1]"
        if (preg_match('/^\[\d+\]/u', $text) === 1) { return false; }
        // Gate 4: length cap
        if (mb_strlen($text) > 150) { return false; }

        // Gate 5a: numbered section headings — checked BEFORE the indent gate
        // because authors often leave the paragraph indent on these.
        // Form A: "4.1", "2.3 Background", "1.2.3 Overview"
        if (preg_match('/^\d+\.\d+(\.\d+)*(\s+\S.*)?$/u', $text) === 1) { return true; }

        // Gate 2: no first-line indent (only applied to non-numbered headings)
        if ($this->getPFirstLineIndent($p) > 0) { return false; }

        // Gate 5b / 5c: bold-majority or bold+italic
        $runs  = $xpath->query('.//w:r', $p);
        $total = $runs->length;
        if ($total === 0) { return false; }

        $textRunsTotal  = 0;
        $textRunsBold   = 0;
        $boldItalicRuns = 0;
        $boldOnlyRuns   = 0;

        foreach ($runs as $run) {
            if (!$run instanceof DOMElement) { continue; }
            // Skip drawing / field runs
            if ($xpath->query(
                './/w:drawing|.//w:pict|.//w:instrText|.//w:fldChar', $run
            )->length > 0) { continue; }
            $tEl = $run->getElementsByTagNameNS(self::W_NS, 't')->item(0);
            if (!$tEl instanceof DOMElement || trim($tEl->textContent ?? '') === '') { continue; }

            $textRunsTotal++;
            $rPr       = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
            $hasBold   = $rPr instanceof DOMElement
                && $rPr->getElementsByTagNameNS(self::W_NS, 'b')->length > 0;
            $hasItalic = $rPr instanceof DOMElement
                && $rPr->getElementsByTagNameNS(self::W_NS, 'i')->length > 0;

            if ($hasBold && $hasItalic) { $boldItalicRuns++; $textRunsBold++; }
            elseif ($hasBold)           { $boldOnlyRuns++;   $textRunsBold++; }
        }

        if ($textRunsTotal === 0) { return false; }

        // 5b: bold-majority, not italic (pure bold headings — Ch4/Ch5 numbered/single-word)
        if (($textRunsBold / $textRunsTotal) >= 0.6 && $boldItalicRuns === 0) { return true; }

        // 5d: font-size 13pt pattern — majority of runs explicitly sz=26
        $sz13Runs = 0;
        foreach ($runs as $run) {
            if (!$run instanceof DOMElement) { continue; }
            if ($xpath->query(
                './/w:drawing|.//w:pict|.//w:instrText|.//w:fldChar', $run
            )->length > 0) { continue; }
            $tEl = $run->getElementsByTagNameNS(self::W_NS, 't')->item(0);
            if (!$tEl instanceof DOMElement || trim($tEl->textContent ?? '') === '') { continue; }
            $rPr  = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
            $szEl = $rPr instanceof DOMElement
                ? $rPr->getElementsByTagNameNS(self::W_NS, 'sz')->item(0)
                : null;
            if ($szEl instanceof DOMElement
                && $szEl->getAttributeNS(self::W_NS, 'val') === '26'
            ) { $sz13Runs++; }
        }
        if ($sz13Runs === 0) {
            $pPrSz = $xpath->query('./w:pPr/w:rPr/w:sz', $p)->item(0);
            if ($pPrSz instanceof DOMElement
                && $pPrSz->getAttributeNS(self::W_NS, 'val') === '26'
            ) { return true; }
        }
        if ($textRunsTotal > 0 && ($sz13Runs / $textRunsTotal) >= 0.6) { return true; }

        return false;
    }

    /**
     * Detects bold+italic headings at 12pt — Chapter 2 inline headings and
     * Chapter 4 / 5 highlighted headings.
     *
     * A paragraph qualifies when it passes the shared gates (same as isHeading)
     * AND one of:
     *   A) Majority of text runs are bold+italic (catches Ch2 inline style)
     *   B) Majority of text runs are bold+italic AND font size is 12pt (sz=24)
     *      (catches Ch4/Ch5 manually-sized headings)
     *   C) Paragraph-mark rPr declares sz=24 (whole-paragraph size inheritance)
     */
    private function isItalicHeading(DOMXPath $xpath, DOMElement $p, string $text): bool
    {
        if ($text === '') { return false; }

        // ── Pattern D (checked first, bypasses period + indent gates) ────────
        // Numbered bold objectives: "1. To develop...", "2. To evaluate..."
        // These start with "N. " and are entirely bold (not italic), 12pt Garamond.
        // They may end in a period and may carry a first-line indent — both allowed here.
        // Guards:
        //   - Length cap: real objectives are short labels, not full body paragraphs.
        //   - No em-dash after the first word: "1. Observation—..." is a list item, not a heading.
        if (preg_match('/^\d+\.\s+\S/u', $text) === 1
            && mb_strlen($text) <= 300
            && preg_match('/^\d+\.\s+\S+\s*[—–-]/u', $text) === 0
            && !preg_match('/^(figure|fig\.?|table|chapter|appendix|continuation|legend)\b/iu', $text)
            && !preg_match('/^\[\d+\]/u', $text)
        ) {
            $runs = $xpath->query('.//w:r', $p);
            $boldTotal = 0; $sz12Total = 0; $runTotal = 0;
            foreach ($runs as $run) {
                if (!$run instanceof DOMElement) { continue; }
                if ($xpath->query('.//w:drawing|.//w:pict|.//w:instrText|.//w:fldChar', $run)->length > 0) { continue; }
                $tEl = $run->getElementsByTagNameNS(self::W_NS, 't')->item(0);
                if (!$tEl instanceof DOMElement || trim($tEl->textContent ?? '') === '') { continue; }
                $runTotal++;
                $rPr   = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
                $bold  = $rPr instanceof DOMElement && $rPr->getElementsByTagNameNS(self::W_NS, 'b')->length > 0;
                $szEl  = $rPr instanceof DOMElement ? $rPr->getElementsByTagNameNS(self::W_NS, 'sz')->item(0) : null;
                if ($bold) { $boldTotal++; }
                if ($szEl instanceof DOMElement && $szEl->getAttributeNS(self::W_NS, 'val') === '24') { $sz12Total++; }
            }
            if ($runTotal > 0 && ($boldTotal / $runTotal) >= 0.6) { return true; }
            if ($runTotal > 0 && ($sz12Total / $runTotal) >= 0.6) { return true; }
            // Fallback: paragraph-mark declares 12pt
            $pPrSz = $xpath->query('./w:pPr/w:rPr/w:sz', $p)->item(0);
            if ($pPrSz instanceof DOMElement && $pPrSz->getAttributeNS(self::W_NS, 'val') === '24') { return true; }
        }

        // ── Shared gates for all other italic-heading patterns ────────────────
        if (preg_match('/[.!?]$/u', $text) === 1) { return false; }
        // NOTE: no first-line indent gate here — Ch2 theme headings are often
        // stored with firstLine=720 because the author formatted them as body
        // paragraphs. Gates 1 (punctuation) and 4 (length) are sufficient.
        if (preg_match(
            '/^(figure|fig\.?|table|chapter|appendix|continuation|legend)\b/iu', $text
        ) === 1) { return false; }
        if (preg_match('/^\[\d+\]/u', $text) === 1) { return false; }
        if (mb_strlen($text) > 150) { return false; }

        $runs = $xpath->query('.//w:r', $p);
        if ($runs->length === 0) { return false; }

        $textRunsTotal  = 0;
        $boldItalicRuns = 0;
        $sz12Runs       = 0;

        foreach ($runs as $run) {
            if (!$run instanceof DOMElement) { continue; }
            if ($xpath->query(
                './/w:drawing|.//w:pict|.//w:instrText|.//w:fldChar', $run
            )->length > 0) { continue; }
            $tEl = $run->getElementsByTagNameNS(self::W_NS, 't')->item(0);
            if (!$tEl instanceof DOMElement || trim($tEl->textContent ?? '') === '') { continue; }

            $textRunsTotal++;
            $rPr       = $run->getElementsByTagNameNS(self::W_NS, 'rPr')->item(0);
            $hasBold   = $rPr instanceof DOMElement
                && $rPr->getElementsByTagNameNS(self::W_NS, 'b')->length > 0;
            $hasItalic = $rPr instanceof DOMElement
                && $rPr->getElementsByTagNameNS(self::W_NS, 'i')->length > 0;
            $szEl      = $rPr instanceof DOMElement
                ? $rPr->getElementsByTagNameNS(self::W_NS, 'sz')->item(0)
                : null;

            if ($hasBold && $hasItalic) { $boldItalicRuns++; }
            if ($szEl instanceof DOMElement
                && $szEl->getAttributeNS(self::W_NS, 'val') === '24'
            ) { $sz12Runs++; }
        }

        if ($textRunsTotal === 0) { return false; }

        // Pattern A: majority bold+italic (Ch2 inline headings)
        if (($boldItalicRuns / $textRunsTotal) >= 0.6) { return true; }

        // Pattern B: any bold+italic runs AND majority 12pt (Ch4/Ch5 manual headings)
        if ($boldItalicRuns > 0 && ($sz12Runs / $textRunsTotal) >= 0.6) { return true; }

        // Pattern C: paragraph-mark declares 12pt AND at least one bold+italic run
        $pPrSz = $xpath->query('./w:pPr/w:rPr/w:sz', $p)->item(0);
        if ($pPrSz instanceof DOMElement
            && $pPrSz->getAttributeNS(self::W_NS, 'val') === '24'
            && $boldItalicRuns > 0
        ) { return true; }

        return false;
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
            foreach (['pStyle', 'rPr', 'widowControl', 'ind', 'spacing',
                      'jc', 'pageBreakBefore', 'keepNext', 'keepLines',
                      'numPr'] as $tag) {
                $this->removeChildren($pPr, $tag);
            }
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

    // ═══════════════════════════════════════════════════════════════════
    // XML DOM helpers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Remove every floating/positioned property from a table so Word
     * renders it inline with the text flow.
     */
    private function deFloatTable(DOMElement $tbl): void
    {
        $tblPr = $this->getChild($tbl, 'tblPr');
        if (!$tblPr instanceof DOMElement) { return; }
        $this->removeChildren($tblPr, 'tblpPr');
        $this->removeChildren($tblPr, 'positionH');
        $this->removeChildren($tblPr, 'positionV');
        $tblInd = $this->getChild($tblPr, 'tblInd');
        if ($tblInd instanceof DOMElement) {
            $tblInd->setAttributeNS(self::W_NS, 'w:w',    '0');
            $tblInd->setAttributeNS(self::W_NS, 'w:type', 'dxa');
        }
    }

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