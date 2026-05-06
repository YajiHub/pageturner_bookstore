<?php

return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'gemini'),
    'fallback_enabled' => env('AI_FALLBACK_ENABLED', true),
    
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3-flash-preview'),
    ],
    
    'ollama' => [
        'enabled' => env('OLLAMA_ENABLED', true),
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2'),
    ],
];
