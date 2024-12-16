<?php

namespace Lunx\CjDropshipping\Inventory;

use GuzzleHttp\Client;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class InventoryManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    /**
     * Inventory Inquiry by Variant ID (GET)
     * @param string $vid
     * @return array
     * @throws Exception
     */
    public function getInventoryByVariantId(string $vid)
    {
        $url = getenv('CJ_API_BASE_URL') . '/product/stock/queryByVid';
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
                throw new Exception("Error fetching inventory: " . $data['message']);
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
