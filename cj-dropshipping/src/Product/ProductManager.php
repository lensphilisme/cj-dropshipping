<?php

namespace Lunx\CjDropshipping\Product;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class ProductManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    /**
     * Get all categories.
     * @return array
     * @throws Exception
     */
    public function getCategories()
    {
        $url = getenv('CJ_API_BASE_URL') . '/product/getCategory';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching categories: " . $data['message']);
            }
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    /**
     * Get product list.
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getProductList(array $params = [])

  
 {
    if (isset($params['minPrice'], $params['maxPrice']) && $params['minPrice'] > $params['maxPrice']) {
        throw new Exception("Minimum price cannot be greater than maximum price.");
    }
        $url = getenv('CJ_API_BASE_URL') . '/product/list';
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
                throw new Exception("Error fetching product list: " . $data['message']);
            }
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    /**
     * Get product details by product ID or SKU.
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getProductDetails(array $params)
    {
        $url = getenv('CJ_API_BASE_URL') . '/product/query';
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
                throw new Exception("Error fetching product details: " . $data['message']);
            }
        } catch (RequestException $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle request errors.
     * @param Exception $exception
     */
    private function handleError($exception)
    {
        error_log($exception->getMessage());
        echo "An error occurred: " . $exception->getMessage();
    }
}
