<?php
namespace DigitalPenguin\MyCloudFulfillment\Modules;

use DigitalPenguin\MyCloudFulfillment\API\APIClient;
use DigitalPenguin\MyCloudFulfillment\Fields\MyCloudFulfillmentOrderField;
use modmore\Commerce\Admin\Configuration\About\ComposerPackages;
use modmore\Commerce\Admin\Sections\SimpleSection;
use modmore\Commerce\Admin\Widgets\Form\CheckboxField;
use modmore\Commerce\Admin\Widgets\Form\PasswordField;
use modmore\Commerce\Admin\Widgets\Form\SectionField;
use modmore\Commerce\Admin\Widgets\Form\TextField;
use modmore\Commerce\Events\Admin\PageEvent;
use modmore\Commerce\Events\OrderState;
use modmore\Commerce\Events\OrderStatus;
use modmore\Commerce\Modules\BaseModule;
use modmore\Commerce\Order\Field\Text;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class MyCloudFulfillment extends BaseModule {

    public function getName()
    {
        $this->adapter->loadLexicon('commerce_mycloudfulfillment:default');
        return $this->adapter->lexicon('commerce_mycloudfulfillment');
    }

    public function getAuthor()
    {
        return 'Murray Wood';
    }

    public function getDescription()
    {
        return $this->adapter->lexicon('commerce_mycloudfulfillment.description');
    }

    public function initialize(EventDispatcher $dispatcher)
    {
        // Load our lexicon
        $this->adapter->loadLexicon('commerce_mycloudfulfillment:default');

        // Add the xPDO package, so Commerce can detect the derivative classes
        $root = dirname(__DIR__, 2);
        $path = $root . '/model/';
        $this->adapter->loadPackage('commerce_mycloudfulfillment', $path);

        // Add template path to twig
        $root = dirname(__DIR__, 2);
        $this->commerce->view()->addTemplatesPath($root . '/templates/');

        // Events
        $dispatcher->addListener(\Commerce::EVENT_STATE_CART_TO_PROCESSING, [$this, 'checkStatusChange']);
    }

    /**
     * @param OrderStatus $orderStatus
     */
    public function checkStatusChange(OrderState $orderState) : void
    {
        $order = $orderState->getOrder();
        $shipments = $order->getShipments();
        if(empty($shipments)) return;

        // It's possible that different items will use different shipping methods, so group them into "fulfillmentOrders".
        $fulfillmentOrders = [];
        foreach($shipments as $shipment) {

            // Make sure shipping method is of \MyCloudFulfillmentShippingMethod type.
            $shippingMethod = $shipment->getShippingMethod();
            if($shippingMethod instanceof \MyCloudFulfillmentShippingMethod) {

                // Change shipment class_key to custom \MyCloudFulfillmentOrderShipment
                $shipment->set('class_key','MyCloudFulfillmentOrderShipment');
                $shipment->save();

                $deliveryModeId = $shippingMethod->getProperty('mcfshippingid');
                $items = $shipment->getItems();
                $fulfillmentOrders[$deliveryModeId]['id'] = $deliveryModeId;
                $fulfillmentOrders[$deliveryModeId]['shipment'] = $shipment;

                if(!empty($items)) {
                    foreach($items as $item) {
                        $fulfillmentOrders[$deliveryModeId]['items'][] = $item;
                    }
                }
            }
        }

        if(!empty($fulfillmentOrders)) {
            $this->sendFulfillmentRequest($order, $fulfillmentOrders);
        }
    }

    /**
     * @param \comOrder $order
     * @param array $fulfillmentOrders
     */
    public function sendFulfillmentRequest(\comOrder $order, array $fulfillmentOrders) : void
    {
        $apiKey = $this->getConfig('liveapikey');
        $secretKey = $this->getConfig('livesecretkey');

        // Determine if we should use test API credentials or not
        if(in_array($this->getConfig('usetestaccount'), [1, true, 'on']) && $this->commerce->isTestMode()) {
            $apiClient = new APIClient(true);
            $apiKey = $this->getConfig('testapikey');
            $secretKey = $this->getConfig('testsecretkey');
        } else {
            $apiClient = new APIClient();
        }

        // Authenticate with API and retrieve token
        $response = $apiClient->authenticate($apiKey,$secretKey);
        $data = $response->getData();
        $this->commerce->modx->log(MODX_LOG_LEVEL_DEBUG,print_r($data,true));

        // Get customer details
        $address = $order->getAddress('shipping');
        // Concatenate shipping address
        $shippingAddress = $address->get('address1') . ' ' . $address->get('address2') . ' ' . $address->get('city') .
        ' ' . $address->get('state') . ' ' . $address->get('country');

        // Prepare payload(s) for request
        foreach($fulfillmentOrders as $fulfillmentOrder) {
            $payload = [
                'delivery_mode_id'  =>  $fulfillmentOrder['id'],
                'status'            =>  'APPROVED',
                'name'              =>  $address->get('fullname'),
                'address'           =>  $shippingAddress,
                'postcode'          =>  $address->get('zip'),
                'phone_number'      =>  $address->get('phone'),
                'order_number'      =>  $order->get('id').'-'.$fulfillmentOrder['shipment']->get('id')
            ];

            // WARNING!
            // Quantity does not work as expected with this API.
            // Be sure to use price instead of item total for each item, or it will multiply again.
            $items = [];
            $totalPrice = 0;
            foreach($fulfillmentOrder['items'] as $item) {
                $totalPrice += $item->get('total');
                $items[] = [
                    'product_id'    => $item->get('sku'),
                    'quantity'      => $item->get('quantity'),
                    'price'         => round(($item->get('price') / 100 ),2)
                ];
            }
            $payload['total_price'] = round(($totalPrice / 100), 2);
            $payload['order_items'] = array_values($items);
            $this->commerce->modx->log(MODX_LOG_LEVEL_DEBUG, print_r($payload, true));

            $response = $apiClient->request('orders',$data['token'],$payload);
            $responseData = $response->getData();
            $this->commerce->modx->log(MODX_LOG_LEVEL_DEBUG,print_r($responseData,true));

            // Check result in the response and create the appropriate order fields
            if($responseData['success']) {
                $this->createOrderField(true, $order);
                $this->createShipmentFields($fulfillmentOrder, $responseData);
            }
        }
    }

    /**
     * @param bool $success
     * @param \comOrder $order
     */
    public function createOrderField(bool $success, \comOrder $order) {
        if($success) {
            $field = new MyCloudFulfillmentOrderField($this->commerce, 'order_field', 'https://system.mycloudfulfillment.com/mcl/delivery');
        } else {
            // Add a plain text field showing the customer is not subscribed.
            $field = new Text($this->commerce, 'order_field', $this->adapter->lexicon('commerce_mycloudfulfillment.order_field.value.not_sent'));
        }
        $order->setOrderField($field);
    }

    /**
     * @param $fulfillmentOrder
     * @param $responseData
     */
    public function createShipmentFields($fulfillmentOrder, $responseData) {
        $shipment = $fulfillmentOrder['shipment'];
        $shipment->setProperty('mc_number',$responseData['data']['attributes']['mc_number']);
        $shipment->setProperty('tracking_url',$responseData['data']['attributes']['bitly_url']);

        $shipment->save();
    }


    /**
     * @param \comModule $module
     * @return array
     */
    public function getModuleConfiguration(\comModule $module) : array
    {
        $fields = [];

        $fields[] = new SectionField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.use_test_account'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.use_test_account.description'),
        ]);
        // Checkbox to enable test account keys and endpoints
        $fields[] = new CheckboxField($this->commerce, [
            'name' => 'properties[usetestaccount]',
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.use_test_account'),
            'value' => $module->getProperty('usetestaccount', '')
        ]);

        // Checkbox to enable test account keys and endpoints
        $fields[] = new CheckboxField($this->commerce, [
            'name' => 'properties[usetestaccount]',
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.use_test_account'),
            'value' => $module->getProperty('usetestaccount', '')
        ]);

        // Test account fields will only appear if checkbox above is selected and saved.
        if(in_array($module->getProperty('usetestaccount'), [1, true, 'on'])) {
            $fields[] = new SectionField($this->commerce, [
                'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.test_mode'),
                'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.test_mode_desc'),
            ]);
            $fields[] = new TextField($this->commerce, [
                'name' => 'properties[testapikey]',
                'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key'),
                'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key_desc'),
                'value' => $module->getProperty('testapikey', '')
            ]);
            $fields[] = new PasswordField($this->commerce, [
                'name' => 'properties[testsecretkey]',
                'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key'),
                'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key_desc'),
                'value' => $module->getProperty('testsecretkey', '')
            ]);
        }

        // Live account fields are always visible
        $fields[] = new SectionField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.live_mode'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.live_mode_desc'),
        ]);
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[liveapikey]',
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.api_key_desc'),
            'value' => $module->getProperty('liveapikey', '')
        ]);
        $fields[] = new PasswordField($this->commerce, [
            'name' => 'properties[livesecretkey]',
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.secret_key_desc'),
            'value' => $module->getProperty('livesecretkey', '')
        ]);

        return $fields;
    }

    public function addLibrariesToAbout(PageEvent $event)
    {
        $lockFile = dirname(__DIR__, 2) . '/composer.lock';
        if (file_exists($lockFile)) {
            $section = new SimpleSection($this->commerce);
            $section->addWidget(new ComposerPackages($this->commerce, [
                'lockFile' => $lockFile,
                'heading' => $this->adapter->lexicon('commerce.about.open_source_libraries') . ' - ' . $this->adapter->lexicon('commerce_mycloudfulfillment'),
                'introduction' => '', // Could add information about how libraries are used, if you'd like
            ]));

            $about = $event->getPage();
            $about->addSection($section);
        }
    }
}
