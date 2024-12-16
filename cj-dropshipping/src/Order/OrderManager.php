<?php

namespace Lunx\CjDropshipping\Order;

use GuzzleHttp\Client;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class OrderManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    public function createOrderV2(array $data)
    {
        $url = getenv('CJ_API_BASE_URL') . '/shopping/order/createOrderV2';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error creating order: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    public function listOrders(array $params = [])
    {
        $url = getenv('CJ_API_BASE_URL') . '/shopping/order/list';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching orders: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    public function getOrderDetails(string $orderId)
    {
        $url = getenv('CJ_API_BASE_URL') . '/shopping/order/getOrderDetail';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
                'query' => ['orderId' => $orderId],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching order details: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    public function deleteOrder(string $orderId)
    {
        $url = getenv('CJ_API_BASE_URL') . '/shopping/order/deleteOrder';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->delete($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
                'query' => ['orderId' => $orderId],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error deleting order: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    public function confirmOrder(string $orderId)
    {
        $url = getenv('CJ_API_BASE_URL') . '/shopping/order/confirmOrder';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->patch($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['orderId' => $orderId],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error confirming order: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function handleError($exception)
    {
        error_log($exception->getMessage());
        echo "An error occurred: " . $exception->getMessage();
    }
}
