<?php
namespace DigitalPenguin\MyCloudFulfillment\API;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class APIClient {

    /** @var Client */
    private $client;
    private $baseUri;

    /**
     * APIClient constructor.
     * @param bool $testMode
     */
    public function __construct(bool $testMode = false) {
        $this->baseUri = $testMode === false ? 'https://api.mycloudfulfillment.com/api/v1/' : 'https://testaws-api.mycloudfulfillment.com/api/v1/';
        $this->client = new Client([
            'headers' => [
                'Content-Type'  => 'application/json',
            ],
            'base_uri'  =>  $this->baseUri
        ]);
    }

    /**
     * @param string $apiKey
     * @param string $secretKey
     * @return Response
     */
    public function authenticate(string $apiKey, string $secretKey) {
        try {
            $response = $this->client->request('POST', 'gettoken',[
                'query' => [
                    'apikey' => $apiKey,
                    'secretkey' => $secretKey
                ]
            ]);
            return Response::from($response);
        } catch (GuzzleException $e) {
            $errorResponse = new Response(false, 0);
            $errorResponse->addError(get_class($e), $e->getMessage());
            return $errorResponse;
        }
    }

    /**
     * Creates an API request and actions it
     * @param string $resource
     * @param string $authToken
     * @param array $data
     * @param string $method
     * @return Response
     */
    public function request(string $resource, string $authToken, array $data, string $method = 'POST'): Response
    {
        try {
            $response = $this->client->request($method, $resource, [
                'headers' => [
                    // Required header format for authToken. "Authorization: Bearer [token]"
                    'Authorization' => 'Bearer ' . $authToken
                ],
                'json' => $data,
            ]);
            return Response::from($response);
        } catch (GuzzleException $e) {
            $errorResponse = new Response(false, 0);
            $errorResponse->addError(get_class($e), $e->getMessage());
            return $errorResponse;
        }
    }
}