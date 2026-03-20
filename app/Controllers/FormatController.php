<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\DocxFormatterService;
use RuntimeException;

class FormatController
{
    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST' && ($_POST['action'] ?? '') === 'format') {
            $this->startFormatting();
            return;
        }

        $this->showUI();
    }

    private function startFormatting(): void
    {
        try {
            // ── Detect silent POST oversize failure ──────────────────
            // When post_max_size is exceeded, $_POST and $_FILES are both
            // empty and PHP emits the warning. We detect it here cleanly.
            if (
                empty($_POST) &&
                empty($_FILES) &&
                isset($_SERVER['CONTENT_LENGTH']) &&
                (int)$_SERVER['CONTENT_LENGTH'] > 0
            ) {
                $maxBytes    = $this->parseBytes(ini_get('post_max_size') ?: '40M');
                $sentBytes   = (int)$_SERVER['CONTENT_LENGTH'];
                $maxReadable = $this->formatBytes($maxBytes);
                $sentReadable = $this->formatBytes($sentBytes);

                throw new RuntimeException(
                    "Your file is too large ({$sentReadable}). " .
                    "The server currently accepts a maximum of {$maxReadable} per upload. " .
                    "Please reduce the file size and try again."
                );
            }

            if (!isset($_FILES['manuscript'])) {
                throw new RuntimeException('No manuscript file was uploaded.');
            }

            $file = $_FILES['manuscript'];

            if (!is_array($file)) {
                throw new RuntimeException('Invalid file upload data.');
            }

            // ── Handle per-file upload error codes ───────────────────
            $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($errorCode !== UPLOAD_ERR_OK) {
                throw new RuntimeException($this->uploadErrorMessage((int)$errorCode));
            }

            $originalName = (string)($file['name']     ?? 'manuscript.docx');
            $tmpPath      = (string)($file['tmp_name'] ?? '');

            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                throw new RuntimeException('Uploaded file could not be verified.');
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($ext !== 'docx') {
                throw new RuntimeException('Only .docx files are supported.');
            }

            $selectedSections = $this->collectSelections('sections', 'sections_m', [
                'preliminary', 'chapters', 'appendices',
            ]);

            $selectedRules = $this->collectSelections('rules', 'rules_m', [
                'spacing', 'indentation', 'alignment', 'captions',
                'continuation', 'borders', 'pagination',
            ]);

            $outputDir  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thesis_formatter';
            $safeName   = $this->sanitizeFilename(pathinfo($originalName, PATHINFO_FILENAME));
            $outputPath = $outputDir . DIRECTORY_SEPARATOR . $safeName . '_formatted.docx';

            if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
                throw new RuntimeException('Could not create output directory.');
            }

            $formatter = new DocxFormatterService();
            $formatter->format($tmpPath, $outputPath, [
                'sections' => $selectedSections,
                'rules'    => $selectedRules,
            ]);

            $this->downloadFile($outputPath, $safeName . '_formatted.docx');

        } catch (\Throwable $e) {
            $this->renderError($e->getMessage());
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => sprintf(
                'The file exceeds the server upload limit (%s). Please use a smaller file.',
                $this->formatBytes($this->parseBytes(ini_get('upload_max_filesize') ?: '40M'))
            ),
            UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the form size limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE    => 'No file was selected for upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server error: temporary upload directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Server error: could not write the uploaded file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Server error: a PHP extension blocked the upload.',
            default               => "Upload failed with error code {$code}.",
        };
    }

    private function collectSelections(string $desktopKey, string $mobileKey, array $defaults): array
    {
        $values = [];

        if (isset($_POST[$desktopKey]) && is_array($_POST[$desktopKey])) {
            $values = array_merge($values, $_POST[$desktopKey]);
        }
        if (isset($_POST[$mobileKey]) && is_array($_POST[$mobileKey])) {
            $values = array_merge($values, $_POST[$mobileKey]);
        }

        $values = array_values(array_unique(array_filter(array_map(
            static fn ($v) => is_scalar($v) ? trim((string)$v) : '',
            $values
        ))));

        return $values !== [] ? $values : $defaults;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^\pL\pN_\-]+/u', '_', $name) ?? 'manuscript';
        $name = trim($name, '_-');
        return $name !== '' ? $name : 'manuscript';
    }

    private function downloadFile(string $filePath, string $downloadName): void
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('Formatted file was not generated.');
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
        header('Content-Length: ' . (string)filesize($filePath));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: 0');

        $fh = fopen($filePath, 'rb');
        if ($fh === false) {
            throw new RuntimeException('Could not open formatted file for download.');
        }

        while (!feof($fh)) {
            echo fread($fh, 8192);
        }

        fclose($fh);
        @unlink($filePath);
        exit;
    }

    private function renderError(string $message): void
    {
        http_response_code(422);
        echo '<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Error — Thesis Formatter</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, sans-serif;
      background: #f1f5f9;
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 24px;
    }
    .card {
      background: #fff; border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      padding: 40px 36px; max-width: 480px; width: 100%; text-align: center;
    }
    .icon { font-size: 48px; margin-bottom: 16px; }
    h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
    p  { font-size: 0.9rem; color: #475569; line-height: 1.6; margin-bottom: 24px; }
    a  {
      display: inline-block; background: #2563eb; color: #fff;
      padding: 10px 24px; border-radius: 10px; text-decoration: none;
      font-size: 0.875rem; font-weight: 600;
    }
    a:hover { background: #1d4ed8; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">⚠️</div>
    <h2>Upload Failed</h2>
    <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
    <a href="javascript:history.back()">← Go back</a>
  </div>
</body>
</html>';
    }

    /**
     * Parse shorthand like "200M", "2G", "512K" into bytes.
     */
    private function parseBytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $num  = (int)$val;

        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    /**
     * Format bytes into a human-readable string.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' bytes';
    }

    private function showUI(): void
    {
        $rules = [
            ['spacing',      'fa-arrows-up-down',  'Spacing'],
            ['indentation',  'fa-indent',           'Indentation'],
            ['alignment',    'fa-align-left',       'Alignment'],
            ['captions',     'fa-image',            'Figure / Table Captions'],
            ['continuation', 'fa-rotate-right',     'Continuation Labels'],
            ['borders',      'fa-border-style',     'Figure Borders'],
            ['pagination',   'fa-file-lines',       'Margins / Pagination'],
        ];

        require UI;
    }
}