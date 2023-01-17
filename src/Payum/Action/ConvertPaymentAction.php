<?php

declare(strict_types=1);

namespace Klehm\SyliusPayumCA3xcbPlugin\Payum\Action;

use Payum\Core\ApiAwareTrait;
use Klehm\SyliusPayumCA3xcbPlugin\Payum\PayboxParams;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Convert;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Sylius\Component\Core\Model\PaymentInterface;

class ConvertPaymentAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    private PayboxParams $payboxParams;

    public function __construct(PayboxParams $payboxParams)
    {
        $this->payboxParams = $payboxParams;
    }

    /**
     * {@inheritdoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        /** @var PaymentInterface $payment */
        $payment = $request->getSource();
        $order = $payment->getOrder();
        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        //$type = "standard";

        $options = $this->api->getOptions();
        $details = $this->computeThreetimePayments($payment->getAmount(), $details);

        $details[PayboxParams::PBX_DEVISE] = $this->payboxParams->convertCurrencyToCurrencyCode($payment->getCurrencyCode());
        $details[PayboxParams::PBX_CMD] = $order->getNumber();
        $details[PayboxParams::PBX_PORTEUR] = $order->getCustomer()->getEmail();
        $details[PayboxParams::PBX_BILLING] = $this->payboxParams->setBilling($order);
        $details[PayboxParams::PBX_SHOPPINGCART] = $this->payboxParams->setShoppingCart($order->countItems());
        $token = $request->getToken();
        $details[PayboxParams::PBX_EFFECTUE] = $token->getTargetUrl();
        $details[PayboxParams::PBX_ANNULE] = $token->getTargetUrl();
        $details[PayboxParams::PBX_REFUSE] = $token->getTargetUrl();
        $details[PayboxParams::PBX_ATTENTE] = $token->getTargetUrl();
        //just CB Cards
        $details[PayboxParams::PBX_TYPEPAIEMENT] = 'CARTE';
        $details[PayboxParams::PBX_TYPECARTE] = 'CB';

        // Prevent duplicated payment error
        if (strpos($token->getGatewayName(), 'sandbox') !== false) {
            $details[PayboxParams::PBX_CMD] = sprintf('%s-%d', $details[PayboxParams::PBX_CMD], time());
        }

        if (false == isset($details[PayboxParams::PBX_REPONDRE_A]) && $this->tokenFactory) {
            $notifyToken = $this->tokenFactory->createNotifyToken($token->getGatewayName(), $payment);
            $details[PayboxParams::PBX_REPONDRE_A] = $notifyToken->getTargetUrl();
        }

        $request->setResult((array) $details);
    }

    public function computeThreetimePayments($totalAmount, $details)
    {
        // Compute each payment amount
        $iterations = 3;
        $step = $totalAmount / $iterations;
        $nthStep = floor($step);
        $firstStep = round($totalAmount - ($nthStep * ($iterations - 1)));

        $details['PBX_TOTAL'] = sprintf('%03d', $firstStep);
        $details['PBX_2MONT1'] = sprintf('%03d', $nthStep);
        $details['PBX_2MONT2'] = sprintf('%03d', $nthStep);

        // Payment dates
        $now = new \DateTime();
        $now->modify('1 month');
        $details['PBX_DATE1'] = $now->format('d/m/Y');
        $now->modify('1 month');
        $details['PBX_DATE2'] = $now->format('d/m/Y');


        // Force validity date of card
        $details['PBX_DATEVALMAX'] = $now->format('ym');
        return $details;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
            ;
    }
}
