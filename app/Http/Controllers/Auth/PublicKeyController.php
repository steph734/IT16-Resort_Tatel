<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Crypto\KeyManager;
use Illuminate\Http\JsonResponse;

class PublicKeyController extends Controller
{
    protected KeyManager $keys;

    public function __construct(KeyManager $keys)
    {
        $this->keys = $keys;
    }

    /**
     * Return the public key PEM. The client uses this to encrypt the password.
     */
    public function show(): JsonResponse
    {
        $public = $this->keys->getPublicKey();
        return response()->json(['public_key' => $public]);
    }
}
