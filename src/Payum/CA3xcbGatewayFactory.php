<?php

declare(strict_types=1);

namespace Klehm\SyliusPayumCA3xcbPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Klehm\SyliusPayumCA3xcbPlugin\Payum\Action\CancelAction;
use Klehm\SyliusPayumCA3xcbPlugin\Payum\Action\ConvertPaymentAction;
use Klehm\SyliusPayumCA3xcbPlugin\Payum\Action\CaptureAction;
use Klehm\SyliusPayumCA3xcbPlugin\Payum\Action\NotifyAction;
use Klehm\SyliusPayumCA3xcbPlugin\Payum\Action\StatusAction;

class CA3xcbGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritdoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name'           => 'ca3xcb',
            'payum.factory_title'          => 'CA 3xCB',
            'payum.action.capture'         => new CaptureAction(),
            //'payum.action.authorize'       => new AuthorizeAction(),
            //'payum.action.refund'          => new RefundAction(),
            'payum.action.cancel'          => new CancelAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.status'          => new StatusAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'site'          => '',
                'rang'          => '',
                'identifiant'   => '',
                'hmac'          => '',
                'hash'          => 'SHA512',
                'retour'        => 'Mt:M;Ref:R;Auto:A;Appel:T;Abo:B;Reponse:E;Transaction:S;Pays:Y;Signature:K',
                'sandbox'       => true,
                'type_paiement' => '',
                'type_carte'    => '',
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['site', 'rang', 'identifiant', 'hmac'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
