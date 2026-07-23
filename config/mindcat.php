<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Criptografia do diário
    |--------------------------------------------------------------------------
    |
    | O conteúdo das entradas de diário é cifrado at-rest com uma chave DEDICADA,
    | independente da APP_KEY. Motivo: a APP_KEY também assina cookies/sessão;
    | acoplar o diário a ela faria com que rotacionar a chave por um motivo de
    | sessão colocasse todos os diários em risco. Chave separada = raio de
    | explosão contido e rotação independente.
    |
    | Todas as chaves usam o mesmo formato da APP_KEY ("base64:<32 bytes>") e o
    | mesmo cipher, para que o Encrypter dedicado consiga ler tanto o dado novo
    | quanto o legado cifrado com a APP_KEY durante o cutover.
    |
    */
    'diary' => [
        'key' => env('MINDCAT_DIARY_KEY'),
        'previous_keys' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MINDCAT_DIARY_PREVIOUS_KEYS', ''))
        ))),

        'bridge_app_key' => (bool) env('MINDCAT_DIARY_BRIDGE_APP_KEY', false),

        'cipher' => env('MINDCAT_DIARY_CIPHER', 'AES-256-CBC'),
    ],

    'invite' => [
        'ttl_hours'   => (int) env('MINDCAT_INVITE_TTL_HOURS', 72),
        'code_length' => (int) env('MINDCAT_INVITE_CODE_LENGTH', 8),
    ],
    'credential' => [
        'grace_days' => (int) env('MINDCAT_CREDENTIAL_GRACE_DAYS', 7),
    ],
    'auth' => [
        'access_ttl_minutes'    => (int) env('MINDCAT_ACCESS_TTL_MINUTES', 30),
        'refresh_ttl_days'      => (int) env('MINDCAT_REFRESH_TTL_DAYS', 30),
        'refresh_cookie'        => env('MINDCAT_REFRESH_COOKIE', 'mindcat_refresh'),
        'refresh_cookie_path'   => env('MINDCAT_REFRESH_COOKIE_PATH', '/api/refresh'),
        'refresh_cookie_domain' => env('MINDCAT_REFRESH_COOKIE_DOMAIN'),
        'refresh_cookie_secure' => (bool) env('MINDCAT_REFRESH_COOKIE_SECURE', false),
    ],

];