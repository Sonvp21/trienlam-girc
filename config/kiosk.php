<?php

return [
    // Provider mặc định cho STT: openai | groq | fpt
    // (KHÔNG hỗ trợ "local" — xem ghi chú trong KioskService)
    'default_provider' => env('PROVIDER', 'openai'),

    // Provider dùng để match (LLM) khi STT provider không có LLM (vd: fpt)
    'match_provider' => env('MATCH_PROVIDER', env('PROVIDER', 'openai')),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'stt_model' => env('OPENAI_STT_MODEL', 'whisper-1'),
        'match_model' => env('OPENAI_MATCH_MODEL', 'gpt-4o-mini'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'stt_model' => env('GROQ_STT_MODEL', 'whisper-large-v3-turbo'),
        'match_model' => env('GROQ_MATCH_MODEL', 'llama-3.3-70b-versatile'),
    ],

    'fpt' => [
        'api_key' => env('FPT_API_KEY'),
    ],
];
