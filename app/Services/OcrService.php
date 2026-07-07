<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrService
{
    public function extractText(string $absolutePath, string $mimeType): string
    {
        if ($mimeType === 'application/pdf') {
            return $this->extractFromPdf($absolutePath);
        }

        return $this->extractFromImage($absolutePath);
    }

    private function extractFromPdf(string $absolutePath): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($absolutePath);

        return trim($pdf->getText());
    }

    private function extractFromImage(string $absolutePath): string
    {
        return trim(
            (new TesseractOCR($absolutePath))
                ->lang('pol', 'eng')
                ->run()
        );
    }
}