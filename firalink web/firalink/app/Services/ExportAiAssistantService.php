<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class ExportAiAssistantService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Provide contextual trade documentation advice, validation, and auto-completion.
     */
    public function assist(string $action, array $documentData, string $templateCode, ?string $userPrompt = null): array
    {
        $status = $this->gemini->getStatus();

        // If Gemini is not configured, fall back to robust deterministic expert rules
        if (!$status['configured']) {
            return $this->deterministicTradeGuidance($action, $documentData, $templateCode, $userPrompt);
        }

        try {
            $prompt = $this->buildSystemPrompt($action, $documentData, $templateCode, $userPrompt);
            $aiResponse = $this->gemini->generateContent($prompt);

            $disclaimer = "\n\n> **Regulatory Notice:** Official ECTA export inspection and Phytosanitary certificates must be obtained directly from competent state authorities; this studio prepares your exporter-issued commercial documents.";
            if (stripos($aiResponse, 'ECTA') === false) {
                $aiResponse .= $disclaimer;
            }

            return [
                'success' => true,
                'source' => 'gemini',
                'action' => $action,
                'advice' => $aiResponse,
                'suggested_updates' => $this->extractSuggestedUpdates($action, $aiResponse, $documentData),
            ];
        } catch (Exception $e) {
            Log::warning("ExportAiAssistantService: Gemini call failed, falling back to rule engine. " . $e->getMessage());
            return $this->deterministicTradeGuidance($action, $documentData, $templateCode, $userPrompt);
        }
    }

    /**
     * Build prompt with strict trade negative guardrails.
     */
    protected function buildSystemPrompt(string $action, array $data, string $templateCode, ?string $userPrompt): string
    {
        $payloadJson = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are Fira AI Trade Documentation Assistant, an expert in Ethiopian coffee export documentation, international trade compliance, Incoterms 2020, and Ethiopian regulatory standards (ECTA, National Bank of Ethiopia, International Coffee Organization).

STRICT GUARDRAILS:
1. You MUST NEVER fabricate or claim to generate official government certificates (such as ECTA Quality Certificates, Phytosanitary Certificates issued by Ministry of Agriculture, or Bank Exchange Permits). Clarify that official certificates are issued solely by competent state authorities.
2. Only suggest standard exporter-issued declarations, commercial terms, and document consistency improvements.
3. Do not execute code or return unverified mathematical figures. Weight and monetary calculations are handled deterministically by the system.
4. Output professional, concise trade advice.

Current Document Template: {$templateCode}
Current Document Data:
{$payloadJson}

User Action: {$action}
User Request: {$userPrompt}

Provide helpful, accurate advice, highlight any trade risks (e.g. missing ICO number, mismatched CAD bank, non-standard Incoterms), and if relevant, provide recommended field values.
PROMPT;
    }

    /**
     * Deterministic rule-based trade guidance fallback when AI key is missing or offline.
     */
    protected function deterministicTradeGuidance(string $action, array $data, string $templateCode, ?string $userPrompt): array
    {
        $tips = [];
        $suggestions = [];

        // ICO Number rule
        if (empty($data['ico_number'])) {
            $tips[] = "ICO mark is missing. Ethiopian export ICO marks follow the format: 010/[ExporterCode]/[SerialLot] (e.g. 010/0568/0011).";
            $suggestions['ico_number'] = "010/0568/0001";
        }

        // Port of Loading check
        if (empty($data['port_of_loading']) || strtolower($data['port_of_loading']) !== 'port of djibouti') {
            $tips[] = "For Ethiopian overland transit, the customary ocean port of loading is 'Port of Djibouti'.";
            $suggestions['port_of_loading'] = "Port of Djibouti";
        }

        // Packaging check
        if (empty($data['packaging_type'])) {
            $tips[] = "Standard export packaging for specialty arabica is '60kg Jute Bags with GrainPro Liners'.";
            $suggestions['packaging_type'] = "60kg Jute Bags with GrainPro Liner";
        }

        // HS Code
        if (empty($data['hs_code'])) {
            $suggestions['hs_code'] = "0901.11 (Coffee, not roasted, not decaffeinated)";
            $tips[] = "Recommended HS Code for Ethiopian green coffee: 0901.11.";
        }

        // Payment terms check
        if (!empty($data['payment_terms']) && stripos($data['payment_terms'], 'CAD') !== false && empty($data['bank_name'])) {
            $tips[] = "Cash Against Documents (CAD) requires collecting/remitting bank particulars (e.g. Commercial Bank of Ethiopia or Addis International Bank).";
        }

        $advice = "### Fira Trade Guidance\n\n" . implode("\n\n- ", array_merge(["Document check completed."], $tips)) . "\n\n> **Note:** Official ECTA export certificates and Phytosanitary approvals must be obtained directly from government authorities; this studio prepares your exporter-issued documents.";

        return [
            'success' => true,
            'source' => 'deterministic_engine',
            'action' => $action,
            'advice' => $advice,
            'suggested_updates' => $suggestions,
        ];
    }

    /**
     * Extract suggested JSON updates if present in response.
     */
    protected function extractSuggestedUpdates(string $action, string $aiResponse, array $currentData): array
    {
        // Check if response contains a ```json code block
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $aiResponse, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
