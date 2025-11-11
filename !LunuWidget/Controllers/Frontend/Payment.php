<?php


use LunuWidget\Components\Payment\PaymentResponse;
use LunuWidget\Components\Payment\PaymentService;

class Shopware_Controllers_Frontend_PaymentExample extends Shopware_Controllers_Frontend_Payment
{
    const PAYMENTSTATUSPAID = 12;

    public function preDispatch()
    {
        /** @var \Shopware\Components\Plugin $plugin */
        $plugin = $this->get('kernel')->getPlugins()['LunuWidget'];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views/');
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */
    public function indexAction()
    {
        /**
         * Check if one of the payment methods is selected. Else return to default controller.
         */
        switch ($this->getPaymentShortName()) {
            case 'lunu_widget_payment':
                return $this->redirect(['action' => 'gateway', 'forceSecure' => true]);
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }

    /**
     * Gateway action method.
     *
     * Collects the payment information and transmit it to the payment provider.
     */
    public function gatewayAction()
    {
        try {
            $providerUrl = $this->getProviderUrl();
            $this->View()->assign('gatewayUrl', $providerUrl . $this->getUrlParameters());
        } catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->error('Lunu Widget Gateway Error: ' . $e->getMessage());
            $this->forward('cancel');
        }
    }

    /**
     * Direct action method.
     *
     * Collects the payment information and transmits it to the payment provider.
     */
    public function directAction()
    {
        try {
            $providerUrl = $this->getProviderUrl();
            $this->redirect($providerUrl . $this->getUrlParameters());
        } catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->error('Lunu Widget Direct Error: ' . $e->getMessage());
            $this->forward('cancel');
        }
    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {
        try {
            /** @var PaymentService $service */
            $service = $this->container->get('lunu_widget.payment_service');
            $user = $this->getUser();
            $billing = $user['billingaddress'];
            /** @var PaymentResponse $response */
            $response = $service->createPaymentResponse($this->Request());
            
            if (empty($response->transactionId) || empty($response->token)) {
                throw new \Exception('Invalid payment response');
            }
            
            $token = $service->createPaymentToken($this->getAmount(), $billing['customernumber']);

            if (!$service->isValidToken($response, $token)) {
                Shopware()->Container()->get('pluginlogger')->warning('Lunu Widget: Invalid token received');
                $this->forward('cancel');
                return;
            }

            switch ($response->status) {
                case 'accepted':
                    $this->saveOrder(
                        $response->transactionId,
                        $response->token,
                        self::PAYMENTSTATUSPAID
                    );
                    $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                    break;
                default:
                    Shopware()->Container()->get('pluginlogger')->warning(
                        'Lunu Widget: Payment not accepted',
                        ['status' => $response->status]
                    );
                    $this->forward('cancel');
                    break;
            }
        } catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->error('Lunu Widget Return Error: ' . $e->getMessage());
            $this->forward('cancel');
        }
    }

    /**
     * Cancel action method
     */
    public function cancelAction()
    {
    }

    /**
     * Creates the url parameters
     */
    private function getUrlParameters()
    {
        /** @var PaymentService $service */
        $service = $this->container->get('lunu_widget.payment_service');
        $router = $this->Front()->Router();
        $user = $this->getUser();
        $billing = $user['billingaddress'];

        $parameter = [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrencyShortName(),
            'firstName' => $billing['firstname'],
            'lastName' => $billing['lastname'],
            'returnUrl' => $router->assemble(['action' => 'return', 'forceSecure' => true]),
            'cancelUrl' => $router->assemble(['action' => 'cancel', 'forceSecure' => true]),
            'token' => $service->createPaymentToken($this->getAmount(), $billing['customernumber'])
        ];

        return '?' . http_build_query($parameter);
    }

    /**
     * Returns the URL of the payment provider. This has to be replaced with the real payment provider URL
     *
     * @return string
     */
    protected function getProviderUrl()
    {
        return $this->Front()->Router()->assemble(['controller' => 'DemoPaymentProvider', 'action' => 'pay']);
    }
}
