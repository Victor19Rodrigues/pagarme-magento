<?php

use Behat\MinkExtension\Context\RawMinkContext;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class OrderViewContext extends RawMinkContext
{
    use PagarMe\Magento\Test\Helper\CustomerDataProvider;
    use PagarMe\Magento\Test\Helper\ProductDataProvider;
    use PagarMe\Magento\Test\Helper\PagarMeCheckoutSwitch;

    private $customer;

    private $session;

    private $grandTotal;

    private $pagarMeCheckout;

    const PAYMENT_METHOD_CREDIT_CARD_LABEL = 'Cartão de crédito';
    const PAYMENT_METHOD_BOLETO_LABEL = 'Boleto';

    /**
     * @BeforeScenario
     */
    public function setUp()
    {
        \Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        $config = Mage::getModel('core/config');
        $config->saveConfig(
            'payment/pagarme_settings/payment_methods',
            'credit_card,boleto'
        );

        $config->saveConfig(
            'payment/pagarme_settings/active',
            true
        );

        $config->saveConfig(
            'payment/pagarme_settings/interest_rate',
            5
        );

        $config->saveConfig(
            'payment/pagarme_settings/max_installments',
            12
        );

        $this->magentoUrl = getenv('MAGENTO_URL');
        $this->session = $this->getSession();
        $this->product = $this->getProduct();
        $this->product->save();

        $stock = $this->getProductStock();
        $stock->assignProduct($this->product);
        $stock->save();

        $this->enablePagarmeCheckout();

        $this->customer = $this->getCustomer();
        $this->customer->save();

        $this->customerAddress = $this->getCustomerAddress();
        $this->customerAddress->setCustomerId($this->customer->getId());
        $this->customerAddress->save();
    }
    
    /**
     * @When navigate to the Order page
     */
    public function andNavigateToTheOrderPage()
    {
        $session = $this->getSession();
        $page = $session->getPage();

        $popup = $page->find('css', '.message-popup-head a');

        if ($popup instanceof \Behat\Mink\Element\NodeElement) {
            $popup->click();
        }

        $page->find('named', array('link', 'Sales'))
            ->mouseOver();

        $page->find('named', array('link', 'Orders'))
            ->click();
    }

    /**
     * @When click on the last created Order
     */
    public function andClickOnTheLastCreatedOrder()
    {
        $session = $this->getSession();
        $page = $session->getPage();

        $page->find('css', '#sales_order_grid_table tbody tr td a')->click();
    }

    /**
     * @Then I see that the interest rate information for :paymentMethod is present
     */
    public function thenISeeThatTheInterestRateValueIsPresent($paymentMethod)
    {
        $session = $this->getSession();
        $page = $session->getPage();

        $element = $page->find('css', '#pagarme_order_info_payment_details');

        \PHPUnit_Framework_TestCase::assertInstanceOf(
            'Behat\Mink\Element\NodeElement',
            $element
        );

        $htmlContent = $element->getHtml();

        if ($paymentMethod === self::PAYMENT_METHOD_CREDIT_CARD_LABEL) {
            \PHPUnit_Framework_TestCase::assertContains(
                'Installments',
                $htmlContent
            );
        }

        \PHPUnit_Framework_TestCase::assertContains(
            $paymentMethod,
            $htmlContent
        );

        \PHPUnit_Framework_TestCase::assertContains(
            'Interest Fee',
            $htmlContent
        );

        \PHPUnit_Framework_TestCase::assertContains(
            'Transaction Id',
            $htmlContent
        );

        sleep(3);
    }

    /**
     * @AfterScenario
     */
    public function tearDown()
    {
        $this->customer->delete();
        $this->product->delete();
        $this->disablePagarmeCheckout();
    }
}