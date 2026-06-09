<?php

namespace App\Services;

use App\Models\Resume;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

/**
 * Extracts plain text from resume binaries.
 *
 * Supports PDF (smalot/pdfparser) and DOCX/DOC (phpoffice/phpword).
 * Two entry points are provided:
 *  - extract(Resume)       — reads file_data from a persisted Resume record.
 *  - extractContent(...)   — works directly with raw binary content, used when
 *                            the file has not yet been (and will never be) stored.
 */
class ResumeTextExtractor
{
    /**
     * Extract plain text from a Resume's stored binary.
     *
     * Delegates to extractContent() after reading file_data from the model.
     * Returns null when file_data is absent or parsing fails.
     */
    public function extract(Resume $resume): ?string
    {
        $content = null;

        if (is_resource($resume->file_data)) {
            rewind($resume->file_data);
            $content = stream_get_contents($resume->file_data);
        } elseif (is_string($resume->file_data) && $resume->file_data !== '') {
            $content = $resume->file_data;
        }

        if ($content === null) {
            Log::warning("No file data for resume ID {$resume->id}; skipping text extraction.");

            return null;
        }

        return $this->extractContent($content, $resume->mime_type);
    }

    /**
     * Extract plain text directly from raw binary content without a Resume model.
     *
     * Used in the upload pipeline where the raw file is processed in-memory and
     * intentionally never written to the database.
     *
     * @param  string  $content   Raw binary file content.
     * @param  string  $mimeType  MIME type used to choose the parser.
     * @return string|null Extracted text, or null on failure / unsupported type.
     */
    public function extractContent(string $content, string $mimeType): ?string
    {
        try {
            if ($mimeType === 'application/pdf') {
                return $this->extractPdfContent($content);
            }

            if (in_array($mimeType, [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])) {
                return $this->extractDocxContent($content);
            }

            Log::warning("Unsupported MIME type '{$mimeType}' for text extraction.");

            return null;
        } catch (Exception $e) {
            Log::error("Text extraction failed for MIME '{$mimeType}': {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Parse a PDF binary and return its concatenated text content.
     */
    private function extractPdfContent(string $content): ?string
    {
        $parser = new Parser;
        $pdf = $parser->parseContent($content);

        return $pdf->getText() ?: null;
    }

    /**
     * Write DOCX binary to a temp file, parse it with PhpWord, and return text.
     *
     * The temp file is always deleted before returning.
     */
    private function extractDocxContent(string $content): ?string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'resume_').'.docx';
        file_put_contents($tmpPath, $content);

        if (! file_exists($tmpPath)) {
            Log::error('Failed to create temp file for DOCX extraction.');

            return null;
        }

        try {
            $phpWord = IOFactory::load($tmpPath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText();
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $text .= $child->getText();
                            }
                        }
                    }
                    $text .= ' ';
                }
                $text .= "\n";
            }

            return trim($text) ?: null;
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }
    }
}
