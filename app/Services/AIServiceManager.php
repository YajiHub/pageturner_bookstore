<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AIServiceManager
{
    const TIMEOUT_SECONDS = 30;
    const RETRY_ATTEMPTS = 2;

    /**
     * Generates text using the primary provider, falling back to local models.
     * Gracefully handles network failures and timeouts.
     */
    public function generateWithFallback(string $prompt, string $featureName = 'general'): string
    {
        $providers = config('ai.fallback_enabled', true)
            ? [config('ai.default_provider', 'gemini'), 'ollama']
            : [config('ai.default_provider', 'gemini')];

        $errors = [];

        foreach ($providers as $provider) {
            try {
                $response = $this->callProviderWithRetry($provider, $prompt);
                $this->logUsage($provider, $featureName, $prompt, $response, $provider !== $providers[0]);
                return $response;
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                $errors[] = "{$provider}: {$errorMsg}";
                Log::warning("AI Provider '{$provider}' failed for feature '{$featureName}': " . $errorMsg);
                // Continue to the next provider in the loop
            }
        }

        // Log all failures
        Log::error("All AI providers failed for feature '{$featureName}'.", ['errors' => $errors]);
        
        // Throw a user-friendly error
        throw new Exception("AI Services are currently unavailable. Please try again later.");
    }

    /**
     * Call provider with retry logic for transient failures
     */
    private function callProviderWithRetry(string $provider, string $prompt, int $attempt = 1): string
    {
        try {
            return $this->callProvider($provider, $prompt);
        } catch (Exception $e) {
            if ($attempt < self::RETRY_ATTEMPTS && $this->isTransientError($e)) {
                Log::info("Retrying {$provider} (attempt {$attempt}/{$this->RETRY_ATTEMPTS})");
                usleep(500000); // 500ms backoff
                return $this->callProviderWithRetry($provider, $prompt, $attempt + 1);
            }
            throw $e;
        }
    }

    /**
     * Determine if an error is transient (worth retrying)
     */
    private function isTransientError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        // Network transient errors
        return str_contains($message, 'timeout')
            || str_contains($message, 'temporary failure')
            || str_contains($message, '503')
            || str_contains($message, '502')
            || str_contains($message, '429')
            || str_contains($message, 'connection reset');
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
            throw new Exception("Gemini API key is not configured.");
        }

        $model = config('ai.gemini.model', 'gemini-3-flash-preview');
        
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(10)
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

            if ($response->status() === 429) {
                throw new Exception("Gemini API rate limit exceeded. Please try again in a few moments.");
            }

            if ($response->status() >= 500) {
                throw new Exception("Gemini service temporarily unavailable (HTTP {$response->status()})");
            }

            if ($response->failed()) {
                throw new Exception("Gemini request failed: " . $response->body());
            }

            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            throw new Exception("Unexpected Gemini response format.");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new Exception("Cannot connect to Gemini API. Network error or service unreachable.");
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new Exception("Gemini API error: " . ($e->response?->body() ?? $e->getMessage()));
        }
    }

    private function callOllama(string $prompt): string
    {
        if (!config('ai.ollama.enabled', true)) {
            throw new Exception("Ollama fallback is disabled.");
        }

        $baseUrl = config('ai.ollama.base_url', 'http://localhost:11434');
        $model = config('ai.ollama.model', 'llama3.2');

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(10)
                ->post("{$baseUrl}/api/generate", [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                ]);

            if ($response->failed()) {
                throw new Exception("Ollama request failed: " . $response->body());
            }

            $responseText = $response->json('response');
            if (empty($responseText)) {
                throw new Exception("Empty response from Ollama.");
            }

            return $responseText;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new Exception("Cannot connect to Ollama. Make sure Ollama is running on {$baseUrl}");
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new Exception("Ollama error: " . ($e->response?->body() ?? $e->getMessage()));
        }
    }

    private function logUsage(string $provider, string $feature, string $input, string $output, bool $wasFallback): void
    {
        try {
            // Simple token estimation: 1 word ≈ 1.3 tokens
            $estimatedTokens = (int)(str_word_count($input . $output) * 1.3);
            
            // Free tier cost is 0, but we log the estimate for the lab requirements
            $cost = $provider === 'ollama' ? 0.00 : ($estimatedTokens / 1000) * 0.0001; 

            DB::table('ai_usage_logs')->insert([
                'provider' => $provider,
                'feature' => $feature,
                'tokens_used' => $estimatedTokens,
                'cost_estimate' => $cost,
                'input_prompt' => substr($input, 0, 500), // Truncate for storage
                'output_response' => substr($output, 0, 500), // Truncate for storage
                'was_fallback' => $wasFallback,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Exception $e) {
            // Don't throw - logging failure shouldn't break the feature
            Log::warning("Failed to log AI usage: " . $e->getMessage());
        }
    }
}