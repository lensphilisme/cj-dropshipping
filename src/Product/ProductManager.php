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
    private $apiUrl;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;

        // Ensure CJ_API_BASE_URL is set
        $this->apiUrl = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.'));
        if (empty($this->apiUrl)) {
            throw new Exception("API URL is not set. Ensure CJ_API_BASE_URL is correctly configured.");
        }
    }

    /**
     * Get all categories.
     * @return array
     * @throws Exception
     */
    public function getCategories()
    {
        $url = rtrim($this->apiUrl, '/') . '/product/getCategory';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
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
    // Validate and sanitize input parameters
    $params = array_filter($params, function ($value) {
        return $value !== null && $value !== '';
    });

    // Validate required search criteria (e.g., at least one identifier should be provided)
    if (empty($params['productName']) && empty($params['productSku']) && empty($params['categoryId'])) {
        throw new Exception("At least one search criterion (productName, productSku, categoryId) must be provided.");
    }

    // Ensure default values for pagination
    $params['pageNum'] = $params['pageNum'] ?? 1;
    $params['pageSize'] = $params['pageSize'] ?? 10;

    // Build the API endpoint URL
    $url = rtrim($this->apiUrl, '/') . '/product/list';
    $accessToken = $this->authenticator->getAccessToken();

    try {
        // Send GET request to the API
        $response = $this->client->get($url, [
            'headers' => [
                'CJ-Access-Token' => $accessToken,
            ],
            'query' => $params,
            'verify' => false, // Skip SSL if necessary
        ]);

        // Parse the API response
        $data = json_decode($response->getBody(), true);

        // Check the result status and return data if successful
        if ($data['result']) {
            return $data['data'];
        } else {
            throw new Exception("Error fetching product list: " . $data['message']);
        }
    } catch (RequestException $e) {
        $this->handleError($e);
    } catch (Exception $e) {
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

        $baseUrl = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.'));
if (!$baseUrl) {
    throw new Exception("CJ_API_BASE_URL is not set or not loaded.");
}

        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/product/query';
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
