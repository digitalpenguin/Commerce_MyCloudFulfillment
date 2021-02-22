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

    public function handle(array $data): Response
    {
        if($data[1] === true) {
            http_response_code(200);
            return new Response(
                200,
                '<h1>OK</h1><p>Notification received.</p>',
                'success_generic'
            );
        } else {
            http_response_code(400);
            return new Response(
                400,
                '<h1>Error</h1><p>The webhook request is invalid.</p>',
                'webhook_exception'
            );
        }
    }
}