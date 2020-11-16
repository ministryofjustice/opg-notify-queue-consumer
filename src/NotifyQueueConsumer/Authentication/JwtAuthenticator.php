<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Authentication;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

class JwtAuthenticator
{
    private string $jwtSecret;
    private string $sessionData;

    public function __construct(string $jwtSecret, string $sessionData)
    {
        $this->jwtSecret = $jwtSecret;
        $this->sessionData = $sessionData;
    }

    /**
     * Generates the JWT token expected by the Sirius API.
     *
     * @return array<string>
     */
    public function createToken()
    {
        $token = (new Builder())
            ->withClaim('session-data', $this->sessionData)
            ->issuedAt(time())
            ->expiresAt(time() + 600)
            ->getToken(new Sha256(), new Key($this->jwtSecret));

        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
    }
}
