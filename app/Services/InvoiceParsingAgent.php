<?php

namespace App\Services;

class InvoiceParsingAgent
{
    /**
     * Simplified rule-based agent for extracting structured data from invoice text.
     * Does not use an LLM - relies on regex patterns typical for Polish invoices.
     * Can be extended to use an AI model (e.g. Ollama) in the future while
     * keeping the same public interface.
     */
    public function parse(string $text): array
    {
        return [
            'contractor_name' => $this->extractContractorName($text),
            'nip' => $this->extractNip($text),
            'total_amount' => $this->extractTotalAmount($text),
            'currency' => $this->extractCurrency($text),
            'payment_method' => $this->extractPaymentMethod($text),
        ];
    }

    private function extractNip(string $text): ?string
    {
        // Matches formats like "NIP: 123-456-78-90", "NIP 1234567890", etc.
        if (preg_match('/NIP[:\s]*([0-9]{3}[-\s]?[0-9]{3}[-\s]?[0-9]{2}[-\s]?[0-9]{2}|[0-9]{10})/iu', $text, $matches)) {
            return preg_replace('/[-\s]/', '', $matches[1]);
        }

        return null;
    }

    private function extractContractorName(string $text): ?string
    {
        // Look for a line containing common Polish company suffixes
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/(sp\.?\s*z\s*o\.?\s*o\.?|s\.a\.?|spółka|firma)/iu', $line)) {
                return $line;
            }
        }

        return null;
    }

    private function extractTotalAmount(string $text): ?string
    {
        // First try to find an amount next to explicit "total" keywords
        if (preg_match('/(?:razem|suma|do\s*zap[lł]aty|total)[:\s]*([0-9]+[.,][0-9]{2})/iu', $text, $matches)) {
            return str_replace(',', '.', $matches[1]);
        }

        // Fallback: any amount next to a currency symbol
        if (preg_match('/([0-9]+[.,][0-9]{2})\s*(?:z[lł]|pln|eur|usd)/iu', $text, $matches)) {
            return str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    private function extractCurrency(string $text): string
    {
        if (preg_match('/\b(EUR|USD)\b/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        // Default currency, since most test invoices are expected to be Polish
        return 'PLN';
    }

    private function extractPaymentMethod(string $text): ?string
    {
        if (preg_match('/(przelew|gotówka|got[oó]wka|karta)/iu', $text, $matches)) {
            return mb_strtolower($matches[1]);
        }

        return null;
    }
}