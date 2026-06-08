<?php

namespace App\Services;

use App\Models\Resume;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

/**
 * Extracts plain text from a stored resume binary.
 *
 * Supports PDF (via smalot/pdfparser) and DOCX/DOC (via phpoffice/phpword).
 * Returns null for unsupported types or when parsing fails; callers should
 * treat null as "no text available" and degrade gracefully.
 */
class ResumeTextExtractor
{
    /**
     * Extract plain text from a Resume's stored binary file data.
     *
     * @param  Resume  $resume  The resume whose file_data should be parsed.
     * @return string|null Extracted text, or null on failure.
     */
    public function extract(Resume $resume): ?string
    {
        try {
            if ($resume->mime_type === 'application/pdf') {
                return $this->extractPdf($resume);
            }

            if (in_array($resume->mime_type, [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])) {
                return $this->extractDocx($resume);
            }

            Log::warning("Unsupported mime type '{$resume->mime_type}' for resume ID {$resume->id}.");

            return null;
        } catch (Exception $e) {
            Log::error("Error extracting text from resume ID {$resume->id}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Parse a PDF binary and return its concatenated text content.
     *
     * Handles both stream resources (from MySQL BLOB columns) and plain strings.
     */
    private function extractPdf(Resume $resume): ?string
    {
        $content = null;

        if (is_resource($resume->file_data)) {
            rewind($resume->file_data);
            $content = stream_get_contents($resume->file_data);
        } elseif (is_string($resume->file_data)) {
            $content = $resume->file_data;
        }

        if ($content === null) {
            Log::error("Could not read PDF content for resume ID {$resume->id}.");

            return null;
        }

        $parser = new Parser;
        $pdf = $parser->parseContent($content);

        return $pdf->getText();
    }

    /**
     * Write the DOCX binary to a temp file, parse it with PhpWord, and
     * return concatenated text from all sections.
     *
     * The temp file is always deleted before returning.
     */
    private function extractDocx(Resume $resume): ?string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'resume_').'.docx';
        file_put_contents($tmpPath, $resume->file_data);

        if (! file_exists($tmpPath)) {
            Log::error("Failed to create temp file for resume ID {$resume->id}.");

            return null;
        }

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

        unlink($tmpPath);

        return trim($text) ?: null;
    }
}
