<?php

use SwagPaymentLunu\Components\ExamplePayment\PaymentResponse;
use Shopware_Controllers_Frontend_Payment;


class Shopware_Controllers_Frontend_PaymentExample extends Shopware_Controllers_Frontend_Payment {
    const PAYMENTSTATUSPAID = 12;
    private string $appId;
    private string $apiSecret;
    private string $apiUrl;
    private string $widgetVersion;
    private string $auth_token;
    private string $widgetURL;

    public function preDispatch() {
        /** @var \Shopware\Components\Plugin $plugin */
        $plugin = $this->get('kernel')->getPlugins()['SwagPaymentLunu'];
        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views/');

        $config = $this->container->get('shopware.plugin.config_reader')->getByPluginName('SwagPaymentLunu');

        if (empty($config['appId']) || empty($config['apiSecret'])) {
            throw new \Exception('App ID and API Secret must be configured in the plugin settings.');
        }

        $is_sandbox_enabled = $config['isSandboxEnabled'];
        $this->appId = $config['appId'];
        $this->apiSecret = $config['apiSecret'];
        $this->widgetVersion = $is_sandbox_enabled ? 'testing' : 'alpha';
        $this->apiUrl = 'https://' . ($is_sandbox_enabled ? 'api.testing' : 'api') . '.lunu.io/api/v1/payments/';
        $this->auth_token = base64_encode($this->appId . ':' . $this->apiSecret);
        $this->widgetURL = 'https://widget' . ($is_sandbox_enabled ? '.testing' : '') . '.lunu.io/#/?';
    }


    public function indexAction() {
        switch ($this->getPaymentShortName()) {
            case 'lunu_widget_payment':
                return $this->redirect(['action' => 'direct', 'forceSecure' => true]);
            default:
                return $this->redirect(['controller' => 'checkout']);
        }
    }


    public function directAction() {
        $order = Shopware()->Modules()->Order();
        $orderNumber = $order->sGetOrderNumber();
        $currency = Shopware()->Shop()->getCurrency()->getCurrency();
        $user = $this->getUser();
        $billing = $user['billingaddress'];
        $email = $user['additional']['user']['email'];
        $description = 'Order #' . $orderNumber;
        $headers = $this->getHeaders($orderNumber);
        $router = $this->Front()->Router();

        $requestParams = array(
            'shop_order_id' => $orderNumber,
            'email' => $email,
            'amount' => $this->getAmount(),
            'client_currency' => $currency,
            'description' => $description,
            'expires' => date("c", time() + 3600)
        );

        $data = $this->lunuRequest("create", $requestParams, $this->getHeaders($order_id));
        $response = $data['response'];
        $confirmation_token = $response['confirmation_token'];

        Shopware()->Session()->orderNumber = $orderNumber;

        $redirectUrl = ($this->widgetURL .
            http_build_query(array(
                'action' => 'select',
                'token' => $confirmation_token,
                'success' => $router->assemble(['action' => 'return', 'forceSecure' => true, 'orderID' => $response['id']]),
                'cancel' => $router->assemble(['action' => 'cancel', 'forceSecure' => true])
            )));
        $this->redirect($redirectUrl);
    }


    public function returnAction() {
        $request = $this->Request();
        $orderId = $request->getParam('orderID');
        $orderNumber = Shopware()->Session()->orderNumber;
        $token = $this->createPaymentToken($this->getAmount(), $this->getUserID());

        $data = $this->lunuRequest("get/" . $orderId, null, $this->getHeaders($orderNumber));
        $response = $data['response'];

        if($response['status'] === 'paid' && $response['shop_order_id'] === $orderNumber) {
            $this->saveOrder(
                $orderId,
                $token,
                self::PAYMENTSTATUSPAID
            );
            $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
        } else {
            $this->forward('cancel');
        }
    }


    public function cancelAction() {
        return;
    }


    private function createPaymentToken($amount, $customerId) {
        return md5(implode('|', [$amount, $customerId]));
    }


    protected function getProviderUrl() {
        return $this->widgetURL;
    }

    private function lunuRequest($method, $data, $headers) {
        $ch = curl_init($this->apiUrl . $method);
        if(!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $responseBody = curl_exec($ch);
        $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($responseHttpCode !== 200) {

        }
        return json_decode($responseBody, true);
    }

    private function getHeaders($order_id) {
        return array(
            'Authorization: Basic ' . $this->auth_token,
            'Idempotence-Key: ' . 'sw6_' . time() . '_' . $order_id,
            'Content-Type: application/json'
        );
    }

    private function getUserID() {
        $user = $this->getUser();
        return $user['billingaddress']['customer']['id'];
    }
}
