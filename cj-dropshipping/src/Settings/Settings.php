<?php

namespace Lunx\CjDropshipping\Settings;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class SettingsManager
{
    private $client;
    private $apiUrl;
    private $accessToken;

    public function __construct($accessToken)
    {
        $this->client = new Client();
        $this->apiUrl = getenv('CJ_API_BASE_URL');
        $this->accessToken = $accessToken;
    }

    // Get settings from the API
    public function getSettings()
    {
        try {
            $response = $this->client->get("{$this->apiUrl}/setting/get", [
                'headers' => [
                    'CJ-Access-Token' => $this->accessToken,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                // Successfully retrieved settings, parse them
                return $this->parseSettings($data['data']);
            } else {
                throw new Exception("Failed to retrieve settings: " . $data['message']);
            }
        } catch (RequestException $e) {
            $this->handleError($e);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    // Parse the settings data to a more useful structure
    private function parseSettings($data)
    {
        return [
            'account' => [
                'openId' => $data['openId'],
                'openName' => $data['openName'],
                'openEmail' => $data['openEmail'],
                'isSandbox' => $data['isSandbox'],
            ],
            'settings' => [
                'quotaLimits' => $data['setting']['quotaLimits'],
                'qpsLimit' => $data['setting']['qpsLimit'],
            ],
            'callback' => [
                'productType' => $data['callback']['productType'],
                'productCallbackUrls' => $data['callback']['productCallbackUrls'],
            ],
            'root' => $data['root'],
        ];
    }

    // Handle errors and provide a meaningful message
    private function handleError($exception)
    {
        // Log the error details for debugging (you can implement your own logging system)
        error_log($exception->getMessage());
        echo "An error occurred: " . $exception->getMessage();
    }
}
