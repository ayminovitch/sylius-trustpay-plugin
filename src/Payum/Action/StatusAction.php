<?php
declare(strict_types=1);

namespace Ayminovitch\TrustpayPlugin\Payum\Action;


use Ayminovitch\TrustpayPlugin\TrustpaySDK\Client;
use Ayminovitch\TrustpayPlugin\Payum\SyliusApi;
use Lyra\Exceptions\LyraException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface
{
    /** @var SyliusApi */
    private $api;
    /** @var  Client */
    private $client;

    /**
     * StatusAction constructor.
     * @param SyliusApi $api
     */
    public function __construct(SyliusApi $api, Client $client)
    {
        $this->api = $api;
        $this->client=$client;

    }

    /**
     * @throws LyraException
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $details = $payment->getDetails();
        $this->client->setUsername($this->api->getIdBoutique());
        $this->client->setPassword($this->api->getApiKey());
        $this->client->setEndpoint('https://tpgw.trustpay.eu/');

        // if(!$this->client->checkHash($this->client->getSHA256Key())){
        //     $request->markCanceled();
        //     throw new LyraException("Invalid Hash FRAUD SPOTTED");
        // }
        $paymentResponse= json_decode($_POST['kr-answer']);
        $payment->setDetails((array) $paymentResponse);
//        $request->getModel()->setStorage((array) $paymentResponse);
        $statusPayment= $paymentResponse->orderStatus;
        dump($request);

        switch ($statusPayment) {
            case "PAID" : // transaction approuvée ou traitée avec succès
                $request->markCaptured();
                dump($request);
                break;
            case "RUNNING" : // contacter l’émetteur de carte
                $request->markPending();
                break;
            case "UNPAID" : // Annulation client.
                $request->markCanceled();
                break;
        }

    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getFirstModel() instanceof SyliusPaymentInterface;
    }


}
