<?php

return [
    'api_key' => env('GEMINI_API_KEY', ''),
    // Modelo por defecto; si falla por cuota, el asistente prueba otros modelos automáticamente
    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
];
