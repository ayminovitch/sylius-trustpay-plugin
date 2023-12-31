<?php

declare(strict_types=1);

namespace Ayminovitch\TrustpayPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Ayminovitch\TrustpayPlugin\Payum\SyliusApi;
use Ayminovitch\TrustpayPlugin\Payum\Action\StatusAction;
use Ayminovitch\TrustpayPlugin\TrustpaySDK\Client;

final class SyliusPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'sylius_payment',
            'payum.factory_title' => 'Trustpay Payment',
            'payum.action.status' => new StatusAction(new SyliusApi($config['api_key']), new Client()),
        ]);
        $config['payum.api'] = function (ArrayObject $config) {
            return new SyliusApi($config['api_key']);
        };
    }
}
