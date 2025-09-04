<?php

namespace SwagPaymentLunu;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;

class SwagPaymentLunu extends Plugin {
    public function install(InstallContext $context)
    {
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $options = [
            'name' => 'lunu_widget_payment',
            'description' => 'Lunu Widget',
            'action' => 'PaymentExample',
            'active' => 1,
            'position' => 0,
            'additionalDescription' =>
                '<img src="https://lunu.io/favicons/favicon.ico"/>'
                . '<div id="payment_desc">'
                . '  Crypto payments with Lunu'
                . '</div>'
        ];

        $installer->createOrUpdate($context->getPlugin(), $options);
    }


    public function uninstall(UninstallContext $context) {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }


    public function deactivate(DeactivateContext $context) {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }


    public function activate(ActivateContext $context) {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
    }


    private function setActiveFlag($payments, $active) {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }
}
