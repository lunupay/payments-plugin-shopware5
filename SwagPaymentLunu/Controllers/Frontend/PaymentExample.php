<?php

use SwagPaymentLunu\Components\ExamplePayment\PaymentResponse;
use Shopware_Controllers_Frontend_Payment;


class Shopware_Controllers_Frontend_PaymentExample extends Shopware_Controllers_Frontend_Payment {
    const PAYMENTSTATUSPAID = 12;
    private string $appId;
    private string $apiSecret;
    private string $apiUrl;
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
        $this->apiUrl = 'https://' . ($is_sandbox_enabled ? 'api.sandbox' : 'api') . '.lunupay.com/legacy-api/v1/payments/';
        $this->auth_token = base64_encode($this->appId . ':' . $this->apiSecret);
        $this->widgetURL = 'https://widget' . ($is_sandbox_enabled ? '.sandbox' : '') . '.lunupay.com/?';
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
        try {
            $order = Shopware()->Modules()->Order();
            $orderNumber = $order->sGetOrderNumber();
            $currency = Shopware()->Shop()->getCurrency()->getCurrency();
            $user = $this->getUser();
            $billing = $user['billingaddress'];
            $email = $user['additional']['user']['email'];
            $description = 'Order #' . $orderNumber;
            $router = $this->Front()->Router();

            // Validate required data
            if (empty($orderNumber) || empty($email)) {
                throw new \Exception('Missing required order information');
            }

            $requestParams = array(
                'shop_order_id' => $orderNumber,
                'email' => filter_var($email, FILTER_SANITIZE_EMAIL),
                'amount' => $this->getAmount(),
                'client_currency' => $currency,
                'description' => $description,
                'expires' => date("c", time() + 3600)
            );

            $data = $this->lunuRequest("create", $requestParams, $this->getHeaders($orderNumber));
            
            if (!isset($data['response']['id'])) {
                throw new \Exception('Invalid response from payment provider');
            }
            
            $response = $data['response'];

            Shopware()->Session()->orderNumber = $orderNumber;

            $redirectUrl = ($this->widgetURL .
                http_build_query(array(
                    'order_id' => $response['id'],
                    'success' => $router->assemble(['action' => 'return', 'forceSecure' => true, 'orderID' => $response['id']]),
                    'cancel' => $router->assemble(['action' => 'cancel', 'forceSecure' => true])
                )));
            $this->redirect($redirectUrl);
        } catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->error('Lunu Payment Error: ' . $e->getMessage());
            $this->forward('cancel');
        }
    }


    public function returnAction() {
        try {
            $request = $this->Request();
            $orderId = $request->getParam('orderID');
            $orderNumber = Shopware()->Session()->orderNumber;
            
            if (empty($orderId) || empty($orderNumber)) {
                throw new \Exception('Missing order information');
            }
            
            $token = $this->createPaymentToken($this->getAmount(), $this->getUserID());

            $data = $this->lunuRequest("get/" . $orderId, null, $this->getHeaders($orderNumber));
            
            if (!isset($data['response'])) {
                throw new \Exception('Invalid response from payment provider');
            }
            
            $response = $data['response'];

            if($response['status'] === 'paid' && $response['shop_order_id'] === $orderNumber) {
                $this->saveOrder(
                    $orderId,
                    $token,
                    self::PAYMENTSTATUSPAID
                );
                $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
            } else {
                Shopware()->Container()->get('pluginlogger')->warning(
                    'Lunu Payment not completed',
                    [
                        'status' => $response['status'] ?? 'unknown',
                        'order_id' => $orderId
                    ]
                );
                $this->forward('cancel');
            }
        } catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->error('Lunu Payment Return Error: ' . $e->getMessage());
            $this->forward('cancel');
        }
    }


    public function cancelAction() {
        return;
    }


    private function createPaymentToken($amount, $customerId) {
        $data = implode('|', [$amount, $customerId, $this->apiSecret]);
        return hash_hmac('sha256', $data, $this->apiSecret);
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
            $error = json_decode($responseBody, true);
            $errorMessage = isset($error['message']) ? $error['message'] : 'Payment provider error';
            
            Shopware()->Container()->get('pluginlogger')->error(
                'Lunu API Error',
                [
                    'method' => $method,
                    'status_code' => $responseHttpCode,
                    'response' => $responseBody
                ]
            );
            
            throw new \Exception('Payment error: ' . $errorMessage);
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
