<?php

namespace App\Services\Crypto;

use Illuminate\Support\Facades\Storage;

class KeyManager
{
    protected string $privatePath;
    protected string $publicPath;

    public function __construct()
    {
        $this->privatePath = storage_path('app/keys/private.pem');
        $this->publicPath = storage_path('app/keys/public.pem');
    }

    /**
     * Ensure a keypair exists; generate on-demand if missing.
     */
    public function ensureKeypair(): void
    {
        if (file_exists($this->privatePath) && file_exists($this->publicPath)) {
            return;
        }

        // Create directory
        @mkdir(dirname($this->privatePath), 0750, true);

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);
        $pubKeyDetails = openssl_pkey_get_details($res);
        $pubKey = $pubKeyDetails['key'];

        file_put_contents($this->privatePath, $privKey);
        file_put_contents($this->publicPath, $pubKey);
        @chmod($this->privatePath, 0600);
        @chmod($this->publicPath, 0644);
    }

    public function getPublicKey(): string
    {
        $this->ensureKeypair();
        return file_get_contents($this->publicPath);
    }

    /**
     * Decrypt a base64-encoded RSA-OAEP ciphertext (as produced by client).
     * Returns decrypted plaintext string on success or null on failure.
     */
    public function decrypt(string $base64Cipher): ?string
    {
        $this->ensureKeypair();

        $cipher = base64_decode($base64Cipher);
        if ($cipher === false) {
            return null;
        }

        $private = openssl_pkey_get_private('file://' . $this->privatePath);
        if ($private === false) {
            return null;
        }

        $decrypted = '';
        $ok = openssl_private_decrypt($cipher, $decrypted, $private, OPENSSL_PKCS1_OAEP_PADDING);
        openssl_free_key($private);

        if (! $ok) {
            return null;
        }

        return $decrypted;
    }
}
