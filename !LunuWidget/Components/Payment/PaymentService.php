<?php

namespace LunuWidget\Components\Payment;

class PaymentService
{
    /**
     * @param $request \Enlight_Controller_Request_Request
     * @return PaymentResponse
     */
    public function createPaymentResponse(\Enlight_Controller_Request_Request $request)
    {
        $response = new PaymentResponse();
        $response->transactionId = $request->getParam('transactionId', null);
        $response->status = $request->getParam('status', null);
        $response->token = $request->getParam('token', null);

        return $response;
    }

    /**
     * @param PaymentResponse $response
     * @param string $token
     * @return bool
     */
    public function isValidToken(PaymentResponse $response, $token)
    {
        return hash_equals($token, $response->token);
    }

    /**
     * @param float $amount
     * @param int $customerId
     * @param string $secret
     * @return string
     */
    public function createPaymentToken($amount, $customerId, $secret = '')
    {
        $data = implode('|', [$amount, $customerId, $secret]);
        return hash_hmac('sha256', $data, $secret ?: 'default_secret_key');
    }
}
