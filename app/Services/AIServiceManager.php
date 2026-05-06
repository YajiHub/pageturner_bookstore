<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AIServiceManager
{
    /**
     * Generates text using the primary provider, falling back to local models.
     */
    public function generateWithFallback(string $prompt, string $featureName = 'general'): string
    {
        $providers = config('ai.fallback_enabled', true)
            ? [config('ai.default_provider', 'gemini'), 'ollama']
            : [config('ai.default_provider', 'gemini')];

        foreach ($providers as $provider) {
            try {
                $response = $this->callProvider($provider, $prompt);
                $this->logUsage($provider, $featureName, $prompt, $response, $provider !== $providers[0]);
                return $response;
            } catch (Exception $e) {
                Log::warning("AI Provider '{$provider}' failed for feature '{$featureName}': " . $e->getMessage());
                // Continue to the next provider in the loop
            }
        }

        Log::error("All AI providers failed for feature '{$featureName}'.");
        throw new Exception("AI Services are currently unavailable.");
    }

    private function callProvider(string $provider, string $prompt): string
    {
        return match ($provider) {
            'gemini' => $this->callGemini($prompt),
            'ollama' => $this->callOllama($prompt),
            default => throw new Exception("Unknown AI provider: {$provider}"),
        };
    }

    private function callGemini(string $prompt): string
    {
        $apiKey = config('ai.gemini.api_key');
        if (empty($apiKey)) {
            throw new Exception("Gemini API key is missing. Please set GEMINI_API_KEY in .env");
        }

        $model = config('ai.gemini.model', 'gemini-3-flash-preview');
        $response = Http::timeout(60)
            ->withoutVerifying() 
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

        if ($response->failed()) {
            throw new Exception("Gemini request failed: " . $response->body());
        }

        $data = $response->json();
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new Exception("Unexpected Gemini response format.");
    }

    private function callOllama(string $prompt): string
    {
        if (!config('ai.ollama.enabled', true)) {
             throw new Exception("Ollama fallback is disabled.");
        }

        $baseUrl = config('ai.ollama.base_url', 'http://localhost:11434');
        $model = config('ai.ollama.model', 'llama3.2');

        $response = Http::timeout(60)->post("{$baseUrl}/api/generate", [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ]);

        if ($response->failed()) {
            throw new Exception("Ollama request failed: " . $response->body());
        }

        return $response->json('response');
    }

    private function logUsage(string $provider, string $feature, string $input, string $output, bool $wasFallback): void
    {
        // Simple token estimation: 1 word ≈ 1.3 tokens
        $estimatedTokens = str_word_count($input . $output) * 1.3;
        
        // Free tier cost is 0, but we log the estimate for the lab requirements
        $cost = $provider === 'ollama' ? 0.00 : ($estimatedTokens / 1000) * 0.0001; 

        DB::table('ai_usage_logs')->insert([
            'provider' => $provider,
            'feature' => $feature,
            'tokens_used' => (int) $estimatedTokens,
            'cost_estimate' => $cost,
            'input_prompt' => $input, 
            'output_response' => $output,
            'was_fallback' => $wasFallback,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}