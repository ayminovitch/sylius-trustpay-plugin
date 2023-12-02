<?php

declare(strict_types=1);

namespace Ayminovitch\TrustpayPlugin\Payum;

final class SyliusApi
{
    /** @var string */
    private $apiKey;
    /** @var string */
    private $idBoutique;

    /**
     * SyliusApi constructor.
     * @param string $apiKey
     * @param string $idBoutique
     */
    public function __construct(string $apiKey, string $idBoutique)
    {
        $this->apiKey = $apiKey;
        $this->idBoutique = $idBoutique;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getIdBoutique(): string
    {
        return $this->idBoutique;
    }

}
