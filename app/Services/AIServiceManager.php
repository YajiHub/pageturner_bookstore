<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Enums\Role;
use GeminiAPI\Resources\Message;

class AIServiceManager
{
    /**
     * Generates text using the primary provider, falling back to local models.
     */
    public function generateWithFallback(string $prompt, string $featureName = 'general'): string
    {
        $providers = config('ai.fallback_enabled', env('AI_FALLBACK_ENABLED', true))
            ? [env('AI_DEFAULT_PROVIDER', 'gemini'), 'ollama']
            : [env('AI_DEFAULT_PROVIDER', 'gemini')];

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
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            throw new Exception("Gemini API key is missing.");
        }

        $client = new Client($apiKey);
        $response = $client->geminiPro()->generateContent(new TextPart($prompt));
        
        return $response->text();
    }

    private function callOllama(string $prompt): string
    {
        $baseUrl = env('OLLAMA_BASE_URL', 'http://localhost:11434');
        $model = env('OLLAMA_MODEL', 'llama3.2');

        if (!env('OLLAMA_ENABLED', true)) {
             throw new Exception("Ollama fallback is disabled.");
        }

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
            'input_prompt' => $input, // Optional: mask this in production if containing PII
            'output_response' => $output,
            'was_fallback' => $wasFallback,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}