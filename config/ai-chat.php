<?php

return [
    /*
    | Tiempo máximo total intentando proveedores (segundos). Evita que el usuario vea "Pensando..." varios minutos.
    */
    'max_total_seconds' => (int) env('AI_CHAT_MAX_TOTAL_SECONDS', 38),

    /*
    | Tope por llamada HTTP a un proveedor (segundos). El servicio usa el mínimo entre esto y el tiempo restante.
    */
    'per_provider_timeout_cap' => (int) env('AI_CHAT_PER_PROVIDER_TIMEOUT', 18),

    /*
    | Orden de proveedores a intentar. El primero que responda gana.
    | Por defecto: groq (Llama 3.3), openrouter, gemini para priorizar modelos más recientes.
    | Opciones: gemini, groq, openrouter
    */
    'providers' => array_filter(array_map('trim', explode(',', env('AI_CHAT_PROVIDERS', 'groq,openrouter,gemini')))),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY', ''),
    ],
];
