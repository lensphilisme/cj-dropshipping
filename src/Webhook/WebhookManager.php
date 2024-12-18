<?php

namespace Lunx\CjDropshipping\Webhook;

use GuzzleHttp\Client;
use Lunx\CjDropshipping\Auth\Authenticator;
use Exception;

class WebhookManager
{
    private $client;
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->client = new Client();
        $this->authenticator = $authenticator;
    }

    /**
     * Set Webhook Message Setting (POST)
     * @param array $settings
     * @return bool
     * @throws Exception
     */
    public function setMessageSetting(array $settings)
    {
        $url = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) . '/webhook/set';
        $accessToken = $this->authenticator->getAccessToken();

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'CJ-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $settings,
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                return true;
            } else {
                throw new Exception("Error setting webhook: " . $data['message']);
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
