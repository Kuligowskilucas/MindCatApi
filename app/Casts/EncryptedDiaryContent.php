<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Cast de criptografia do conteúdo do diário com CHAVE DEDICADA.
 *
 * Diferente do cast nativo 'encrypted' (que usa a APP_KEY via Crypt), este monta
 * um Encrypter próprio a partir de config('mindcat.diary.*'), desacoplando o
 * diário da APP_KEY (que também assina cookie/sessão). Ver config/mindcat.php.
 *
 * Leitura tenta, nesta ordem: chave atual -> previous_keys (rotações dedicadas)
 * -> APP_KEY (se a ponte de cutover 'bridge_app_key' estiver ligada).
 * Escrita usa SEMPRE a chave atual.
 *
 * Usa encryptString/decryptString (serialize=false) para casar exatamente com o
 * formato do cast nativo 'encrypted' — é isso que permite ler o legado via bridge.
 */
class EncryptedDiaryContent implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        return static::encrypter()->decryptString($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        return [$key => static::encrypter()->encryptString((string) $value)];
    }

    /**
     * Monta o Encrypter dedicado a partir da config, SEM memoização: lê a config
     * viva a cada operação. É barato (poucas entradas de diário por leitura) e deixa
     * os testes de rotação triviais (troca config + nova operação = novas chaves).
     */
    protected static function encrypter(): Encrypter
    {
        $cipher  = (string) config('mindcat.diary.cipher', 'AES-256-CBC');
        $current = static::parseKey(config('mindcat.diary.key'));

        if ($current === null) {
            throw new RuntimeException(
                'MINDCAT_DIARY_KEY não configurada. Gere com: php artisan mindcat:diary-key'
            );
        }

        $encrypter = new Encrypter($current, $cipher);

        $previous = [];

        foreach ((array) config('mindcat.diary.previous_keys', []) as $old) {
            if ($parsed = static::parseKey($old)) {
                $previous[] = $parsed;
            }
        }

        if (config('mindcat.diary.bridge_app_key')) {
            if ($appKey = static::parseKey(config('app.key'))) {
                $previous[] = $appKey;
            }
        }

        if ($previous !== []) {
            $encrypter->previousKeys($previous);
        }

        return $encrypter;
    }

    /**
     * Decodifica uma chave "base64:..." (padrão da APP_KEY) ou aceita bytes crus.
     * Retorna null se vazia/ausente.
     */
    protected static function parseKey(mixed $key): ?string
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        return str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7))
            : $key;
    }
}