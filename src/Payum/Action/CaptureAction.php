<?php
declare(strict_types=1);


namespace Ayminovitch\TrustpayPlugin\Payum\Action;

use Ayminovitch\TrustpayPlugin\Payum\SyliusApi;
use Ayminovitch\TrustpayPlugin\TrustpaySDK\Client;
use GuzzleHttp\Exception\RequestException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpRedirect;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Ayminovitch\TrustpayPlugin\Payum\Core\Request\CaptureRequest;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class CaptureAction implements ActionInterface, ApiAwareInterface
{
    /** @var Client */
    public $client;
    /** @var SyliusApi */
    private $api;
   /** @var Environment */
    private $twig;

    public function __construct(Client $client,Environment $twig)
    {
        $this->client = $client;
        $this->twig = $twig;

    }

    public function execute($request): void
    {

        RequestNotSupportedException::assertSupports($this, $request);
        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();
        $customerEmail=$payment->getOrder()->getCustomer()->getEmail();
        $orderId= $payment->getOrder()->getId();
//        testpublickey_mETb5YxL8BWUi0f4ITfqQH4tbzkA0kSTA6Ypy2P1ejsSm

        $endpoint = 'https://test-tpgw.trustpay.eu/';
        // $endpoint = 'https://webhook.site/';

        $this->client->setApiKey($this->api->getApiKey());
        $this->client->setEndpoint($endpoint);
        $store = 
        [
            "amount" => $payment->getAmount(),
            "currency" => $payment->getCurrencyCode(),
            "initApplePay" => "true",
            "initGooglePay" => "true",
        ];
            
        $response = $this->client->post("api/v1/intent", $store);
 
        /* I check if there are some errors */
        if ($response['status'] != 0) {
            /* an error occurs, I throw an exception */
            $error = $response['description'];
            throw new Exception("error " . $error['errorCode'] . ": " . $error['errorMessage'] );
        }

        /* everything is fine, I extract the formToken */


        $formToken = $response["instanceId"];
        $secrets = $response["secrets"];
        $payment->setDetails(['status' => 200 ]);
        $request->setDataForm([
                'formToken'=> $formToken,
                'secrets' => $secrets,
                'publicKey'=> $this->client->getPublicKey(),
                'clientEndpoint'=>$this->client->getClientEndpoint(),
                'lyraClient'=>$this->client,
                'order' => $payment->getOrder()
        ]);

        // dd($request->getDataForm());

    }

    public function supports($request): bool
    {
        return
            $request instanceof CaptureRequest &&
            $request->getModel() instanceof SyliusPaymentInterface;
    }

    public function setApi($api): void
    {
        if (!$api instanceof SyliusApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . SyliusApi::class);
        }

        $this->api = $api;
    }
}
