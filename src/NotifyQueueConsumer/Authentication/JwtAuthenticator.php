<?php

declare(strict_types=1);

namespace NotifyQueueConsumer\Authentication;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class JwtAuthenticator
{
    /** @var non-empty-string $jwtSecret */
    private string $jwtSecret;
    private string $apiUserEmail;

    /** @param non-empty-string $jwtSecret */
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
        $now = $now->setTime(intval($now->format('G')), intval($now->format('i')), intval($now->format('s')));
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->jwtSecret));

        $token = $config->builder()
            ->withClaim('session-data', $this->apiUserEmail)
            ->issuedAt($now)
            ->expiresAt($now->modify('+10 minutes'))
            ->getToken($config->signer(), $config->signingKey());

        return [
            'Authorization' => 'Bearer ' . $token->toString(),
            'Content-Type' => 'application/json'
        ];
    }
}
