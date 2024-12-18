<?php

namespace Lunx\CjDropshipping\Variant;

use GuzzleHttp\Client;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class VariantManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    /**
     * Inquiry of All Variants (GET)
     * @param array $params ['pid' => 'product_id', 'productSku' => 'sku', 'variantSku' => 'variant_sku']
     * @return array
     * @throws Exception
     */
    public function getAllVariants(array $params)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/product/variant/query';
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
                throw new Exception("Error fetching variants: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Variant ID Inquiry (GET)
     * @param string $vid
     * @return array
     * @throws Exception
     */
    public function getVariantById(string $vid)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/product/variant/queryByVid';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
                'query' => ['vid' => $vid],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching variant by ID: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Inquiry Reviews (GET) (Deprecated as of June 1, 2024)
     * @param array $params ['pid' => 'product_id', 'score' => 'score', 'pageNum' => 1, 'pageSize' => 20]
     * @return array
     * @throws Exception
     */
    public function getProductReviews(array $params)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/product/comments';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['success']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching product reviews: " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Inquiry Reviews (GET) (New API)
     * @param array $params ['pid' => 'product_id', 'score' => 'score', 'pageNum' => 1, 'pageSize' => 20]
     * @return array
     * @throws Exception
     */
    public function getProductReviewsNew(array $params)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/product/productComments';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                ],
                'query' => $params,
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['success']) {
                return $data['data'];
            } else {
                throw new Exception("Error fetching product reviews (new): " . $data['message']);
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle errors.
     * @param Exception $exception
     */
    private function handleError($exception)
    {
        error_log($exception->getMessage());
        echo "An error occurred: " . $exception->getMessage();
    }
}
