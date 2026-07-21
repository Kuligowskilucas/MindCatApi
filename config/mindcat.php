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

];