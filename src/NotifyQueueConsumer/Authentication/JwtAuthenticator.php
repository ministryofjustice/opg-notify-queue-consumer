<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Authentication;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class JwtAuthenticator
{
    private string $jwtSecret;
    private string $apiUserEmail;

    public function __construct(string $jwtSecret, string $apiUserEmail)
    {
        $this->jwtSecret = $jwtSecret;
        $this->apiUserEmail = $apiUserEmail;
    }

    /**
     * Generates the JWT token expected by the Sirius API.
     *
     * @return array<string>
     */
    public function createToken()
    {
        $now = new DateTimeImmutable();
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->jwtSecret));

        $token = $config->builder()
            ->withClaim('session-data', $this->apiUserEmail)
            ->issuedAt($now->modify('- 1 minutes'))
            ->expiresAt($now->modify('+10 minutes'))
            ->getToken($config->signer(), $config->signingKey());

        return [
            'Authorization' => 'Bearer ' . $token->toString(),
            'Content-Type' => 'application/json'
        ];
    }
}
