<?php
namespace DigitalPenguin\MyCloudFulfillment\Webhook;

use modmore\Commerce\Webhook\Response;

class Handler {
    /**
     * @var \modmore\Commerce\Adapter\AdapterInterface
     */
    private $adapter;
    /**
     * @var \Commerce
     */
    private $commerce;
    /**
     * @var bool
     */
    private $logging;

    public function __construct(\Commerce $commerce, $suppressLogging = false)
    {
        $this->commerce = $commerce;
        $this->adapter = $this->commerce->adapter;
        $this->logging = !$suppressLogging;
    }

    public function handle(array $rawData): Response
    {
        $this->commerce->modx->log(MODX_LOG_LEVEL_INFO,print_r($rawData,true));
        $acceptedStatus = [
            'PACKED',
            'INPROGRESS',
            'SHIPPED',
            'DELIVERED'
        ];
        if(!in_array($rawData['status'],$acceptedStatus)) return $this->failureResponse();

        $data = [];
        if($rawData['mc_number']) {
            $data['mc_number'] = filter_var($rawData['mc_number'],FILTER_SANITIZE_STRING);
            $data['order_number'] = filter_var($rawData['order_number'],FILTER_SANITIZE_STRING);
            $data['status'] = filter_var($rawData['status'],FILTER_SANITIZE_STRING);

            // Prepare order number (ordernumber-shipmentnumber)
            $orderNumArray = explode('-',$data['order_number']);
            $orderNumber = $orderNumArray[0];
            $shipmentNumber = $orderNumArray[1];

            // Load order
            $order = $this->adapter->getObject('comOrder',[
                'id'    =>  $orderNumber
            ]);
            if(!$order instanceof \comOrder) return $this->failureResponse();

            $state = $order->getState();
            if(!in_array($state,['processing','complete'])) return $this->failureResponse();

            $status = $order->getStatus();
            $availableChanges = $status->getAvailableChanges();

            // Check available changes and if the status change matches, process it.
            foreach($availableChanges as $availableChange) {
                $targetStatus = $availableChange->getTargetStatus();
                if(strtolower($targetStatus->get('name')) === strtolower($data['status'])) {
                    // Move order to the target status
                    $availableChange->processChange($order);
                }
            }

            return $this->successResponse();
        } else {
            return $this->failureResponse();
        }
    }

    public function successResponse() {
        http_response_code(200);
        return new Response(
            200,
            '<h1>OK</h1><p>Notification received.</p>',
            'success_generic'
        );
    }

    public function failureResponse() {
        http_response_code(400);
        return new Response(
            400,
            '<h1>Error</h1><p>The webhook request is invalid.</p>',
            'webhook_exception'
        );
    }
}