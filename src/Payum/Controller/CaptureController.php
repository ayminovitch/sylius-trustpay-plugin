<?php


namespace Ayminovitch\TrustpayPlugin\Payum\Controller;


use Ayminovitch\TrustpayPlugin\TrustpaySDK\Client;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Core\Reply\HttpPostRedirect;
use Ayminovitch\TrustpayPlugin\Payum\Core\Request\CaptureRequest;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CaptureController extends PayumController
{
    public function doSessionTokenAction(Request $request)
    {
        if (false === $request->hasSession()) {
            throw new HttpException(400, 'This controller requires session to be started.');
        }

        if (null === $hash = $request->getSession()->get('payum_token')) {
            throw new HttpException(400, 'This controller requires token hash to be stored in the session.');
        }

        $request->getSession()->remove('payum_token');

        $redirectUrl = $this->generateUrl('payum_capture_do', array_replace(
            $request->query->all(),
            array(
                'payum_token' => $hash,
            )
        ));

        if ($request->isMethod('POST')) {
            throw new HttpPostRedirect($redirectUrl, $request->request->all());
        }

        return $this->redirect($redirectUrl);
    }

    public function doAction(Request $request)
    {
        $token = $this->getPayum()->getHttpRequestVerifier()->verify($request);

        $gateway = $this->getPayum()->getGateway($token->getGatewayName());
        $captureRequest= new CaptureRequest($token);

        $gateway->execute($captureRequest);
        // if ($token->getGatewayName() === "trustpay") {

            return $this->render('Trustpay/cardFormPayment.twig', ['formData' => $captureRequest->getDataForm(), 'redirectUrl' => $token->getAfterUrl()]);

        // }
        // $this->getPayum()->getHttpRequestVerifier()->invalidate($token);

        // return $this->redirect($token->getAfterUrl());
    }
    public function afterCaptureAction(Request $request): Response
    {
        dump($request);die();

        $configuration = $this->requestConfigurationFactory->create($this->orderMetadata, $request);

        $token = $this->getHttpRequestVerifier()->verify($request);

        /** @var Generic&GetStatusInterface $status */
        $status = $this->getStatusRequestFactory->createNewWithModel($token);
        
        $this->payum->getGateway($token->getGatewayName())->execute($status);

        $resolveNextRoute = $this->resolveNextRouteRequestFactory->createNewWithModel($status->getFirstModel());

        $this->payum->getGateway($token->getGatewayName())->execute($resolveNextRoute);

        $this->getHttpRequestVerifier()->invalidate($token);

        if (PaymentInterface::STATE_NEW !== $status->getValue()) {
            /** @var FlashBagInterface $flashBag */
            $flashBag = $request->getSession()->getBag('flashes');
            $flashBag->add('info', sprintf('sylius.payment.%s', $status->getValue()));
        }

        return new RedirectResponse($this->router->generate($resolveNextRoute->getRouteName(), $resolveNextRoute->getRouteParameters()));
    }
}
