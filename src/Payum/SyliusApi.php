<?php

declare(strict_types=1);

namespace Ayminovitch\TrustpayPlugin\Payum;

final class SyliusApi
{
    /** @var string */
    private $apiKey;

    /**
     * SyliusApi constructor.
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

}
