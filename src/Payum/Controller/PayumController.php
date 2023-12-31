<?php


namespace Ayminovitch\TrustpayPlugin\Payum\Controller;


use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Payum;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\HttpRequestVerifierInterface;
use Payum\Core\Security\TokenInterface;
use Sylius\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use Sylius\Bundle\PayumBundle\Factory\ResolveNextRouteFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class PayumController
{
    /** @var Payum */
    private $payum;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var MetadataInterface */
    private $orderMetadata;

    /** @var RequestConfigurationFactoryInterface */
    private $requestConfigurationFactory;

    /** @var ViewHandlerInterface */
    private $viewHandler;

    /** @var RouterInterface */
    private $router;

    /** @var GetStatusFactoryInterface */
    private $getStatusRequestFactory;

    /** @var ResolveNextRouteFactoryInterface */
    private $resolveNextRouteRequestFactory;

    public function __construct(
        Payum $payum,
        OrderRepositoryInterface $orderRepository,
        MetadataInterface $orderMetadata,
        RequestConfigurationFactoryInterface $requestConfigurationFactory,
        ViewHandlerInterface $viewHandler,
        RouterInterface $router,
        GetStatusFactoryInterface $getStatusFactory,
        ResolveNextRouteFactoryInterface $resolveNextRouteFactory
    ) {
        $this->payum = $payum;
        $this->orderRepository = $orderRepository;
        $this->orderMetadata = $orderMetadata;
        $this->requestConfigurationFactory = $requestConfigurationFactory;
        $this->viewHandler = $viewHandler;
        $this->router = $router;
        $this->getStatusRequestFactory = $getStatusFactory;
        $this->resolveNextRouteRequestFactory = $resolveNextRouteFactory;
    }

    public function prepareCaptureAction(Request $request, $tokenValue): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->orderMetadata, $request);

        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findOneByTokenValue($tokenValue);

        if (null === $order) {
            throw new NotFoundHttpException(sprintf('Order with token "%s" does not exist.', $tokenValue));
        }

        $request->getSession()->set('sylius_order_id', $order->getId());
        $payment = $order->getLastPayment(PaymentInterface::STATE_NEW);

        if (null === $payment) {
            $url = $this->router->generate('sylius_shop_order_thank_you');

            return new RedirectResponse($url);
        }

        $token = $this->provideTokenBasedOnPayment($payment, $configuration->getParameters()->get('redirect'));

        return new RedirectResponse($token->getTargetUrl());
    }

    public function afterCaptureAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->orderMetadata, $request);

        $token = $this->getHttpRequestVerifier()->verify($request);

        /** @var Generic&GetStatusInterface $status */
        $status = $this->getStatusRequestFactory->createNewWithModel($token);
        $this->payum->getGateway($token->getGatewayName())->execute($status);
        $paymentResponse= json_decode($_POST['kr-answer']);
        $status->getFirstModel()->setDetails((array) $paymentResponse);
        dump(json_decode($_POST['kr-answer']));
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

    private function getTokenFactory(): GenericTokenFactoryInterface
    {
        return $this->payum->getTokenFactory();
    }

    private function getHttpRequestVerifier(): HttpRequestVerifierInterface
    {
        return $this->payum->getHttpRequestVerifier();
    }

    private function provideTokenBasedOnPayment(PaymentInterface $payment, array $redirectOptions): TokenInterface
    {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();

        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        if (isset($gatewayConfig->getConfig()['use_authorize']) && true === (bool) $gatewayConfig->getConfig()['use_authorize']) {
            $token = $this->getTokenFactory()->createAuthorizeToken(
                $gatewayConfig->getGatewayName(),
                $payment,
                $redirectOptions['route']
                ?? null,
                $redirectOptions['parameters']
                ?? []
            );
        } else {
            $token = $this->getTokenFactory()->createCaptureToken(
                $gatewayConfig->getGatewayName(),
                $payment,
                $redirectOptions['route']
                ?? null,
                $redirectOptions['parameters']
                ?? []
            );
        }

        return $token;
    }
}