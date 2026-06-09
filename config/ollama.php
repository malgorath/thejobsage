<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ollama API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Ollama API. This should point to your Ollama
    | server instance. For Docker environments, use host.docker.internal
    | to reach the host machine.
    |
    */
    'api_url' => env('OLLAMA_API_URL', 'http://localhost:11434/api/generate'),

    /*
    |--------------------------------------------------------------------------
    | Ollama LLM Model
    |--------------------------------------------------------------------------
    |
    | The default model to use for AI operations. Common options:
    | - gemma3:4b (fast, lightweight)
    | - llama3.2:3b (good balance)
    | - llama3.2 (3B params)
    | - mistral (higher quality)
    | - llama3.1:8b (best quality, slower)
    |
    */
    'llm_model' => env('OLLAMA_LLM_MODEL', 'gemma3:4b'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for Ollama API requests. Increase this for
    | larger models or slower connections.
    |
    */
    'timeout' => env('OLLAMA_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | API Type
    |--------------------------------------------------------------------------
    |
    | Controls the request/response format used when talking to the AI backend.
    |
    | 'ollama'  — Ollama native format: POST /api/generate with a "prompt"
    |             field; response contains {"response": "..."}
    |
    | 'openai'  — OpenAI-compatible format: POST /v1/chat/completions with a
    |             "messages" array; response contains choices[0].message.content
    |             Compatible servers: llama.cpp --server, LM Studio, vLLM,
    |             LocalAI, and Ollama's own /v1 endpoint.
    |
    */
    'api_type' => env('OLLAMA_API_TYPE', 'ollama'),
];
