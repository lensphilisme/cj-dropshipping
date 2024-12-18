<?php

namespace Lunx\CjDropshipping\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Dotenv\Dotenv;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Authenticator
{
    private $logger;
    private $client;
    private $apiUrl;
    private $email;
    private $password;
    private $accessToken;
    private $refreshToken;
    private $accessTokenExpiryDate;
    private $refreshTokenExpiryDate;

    public function __construct($email, $password)
{
    $this->client = new Client();

    // Initialize Logger
    $logPath = dirname(__DIR__, 5)  . '/storage/logs/laravel.log';
    $this->logger = new Logger('cjdropshipping');
    $this->logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

    // Load .env
    $dotenvPath = dirname(__DIR__, 5) . '/.env';
    if (file_exists($dotenvPath)) {
        $dotenv = Dotenv::createImmutable(dirname($dotenvPath));
        $dotenv->load();
        $this->log("Environment variables loaded successfully.");
    } else {
        $this->log("Warning: .env file not found at: {$dotenvPath}");
    }

    // Set API base URL
    $this->apiUrl = (isset($_ENV['CJ_API_BASE_URL']) ? $_ENV['CJ_API_BASE_URL'] : throw new Exception('CJ_API_BASE_URL is not set in the environment variables.')) ?? null;
    if (empty($this->apiUrl)) {
        $this->log("Error: API URL is not set.");
        throw new Exception("API URL is not set. Ensure CJ_API_BASE_URL is correctly configured.");
    }

    $this->log("Using API URL: {$this->apiUrl}");

    $this->email = $email;
    $this->password = $password;
    $this->accessToken = (isset($_ENV['CJ_ACCESS_TOKEN']) ? $_ENV['CJ_ACCESS_TOKEN'] : throw new Exception('CJ_ACCESS_TOKEN is not set in the environment variables.')) ?? null;
    $this->refreshToken = (isset($_ENV['CJ_REFRESH_TOKEN']) ? $_ENV['CJ_REFRESH_TOKEN'] : throw new Exception('CJ_REFRESH_TOKEN is not set in the environment variables.')) ?? null;
    $this->accessTokenExpiryDate = (isset($_ENV['CJ_ACCESS_TOKEN_EXPIRY']) ? $_ENV['CJ_ACCESS_TOKEN_EXPIRY'] : throw new Exception('CJ_ACCESS_TOKEN_EXPIRY is not set in the environment variables.')) ?? null;
    $this->refreshTokenExpiryDate = (isset($_ENV['CJ_REFRESH_TOKEN_EXPIRY']) ? $_ENV['CJ_REFRESH_TOKEN_EXPIRY'] : throw new Exception('CJ_REFRESH_TOKEN_EXPIRY is not set in the environment variables.')) ?? null;

    $this->log("Authenticator initialized.");
}


    // Get environment variable or fallback to default
    private function getEnvOrDefault($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }

    // Get access token
    public function getAccessToken()
    {
        if (!$this->accessToken || $this->isTokenExpired($this->accessTokenExpiryDate)) {
            $this->log("Access token missing or expired. Refreshing or fetching new token...");
            $this->refreshAccessToken();
        }

        return $this->accessToken;
    }

    // Refresh access token
    private function refreshAccessToken()
    {
        $url = "{$this->apiUrl}/authentication/refreshAccessToken";

        try {
            $response = $this->client->post($url, [
                'json' => [
                    'refreshToken' => $this->refreshToken,
                ],
                'verify' => false, // Bypass SSL verification
            ]);

            $data = json_decode($response->getBody(), true);
            $this->logResponse($url, $data);

            if ($data['result']) {
                $this->updateTokens($data['data']);
            } else {
                throw new Exception("Failed to refresh access token: " . $data['message']);
            }
        } catch (RequestException $e) {
            $this->handleError($e);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    // Check if the access token is expired
    private function isTokenExpired($expiryDate)
    {
        if (!$expiryDate) return true;

        $currentTime = new \DateTime();
        $expiryTime = new \DateTime($expiryDate);

        return $currentTime >= $expiryTime;
    }

    // Update tokens and store them
    private function updateTokens($data)
    {
        $this->accessToken = $data['accessToken'];
        $this->accessTokenExpiryDate = $data['accessTokenExpiryDate'];
        $this->refreshToken = $data['refreshToken'];
        $this->refreshTokenExpiryDate = $data['refreshTokenExpiryDate'];

        $this->updateEnv('CJ_ACCESS_TOKEN', $this->accessToken);
        $this->updateEnv('CJ_ACCESS_TOKEN_EXPIRY', $this->accessTokenExpiryDate);
        $this->updateEnv('CJ_REFRESH_TOKEN', $this->refreshToken);
        $this->updateEnv('CJ_REFRESH_TOKEN_EXPIRY', $this->refreshTokenExpiryDate);

        $this->log("Tokens updated successfully.");
    }

    // Log in to get initial tokens
    public function login()
    {
        $url = "{$this->apiUrl}/authentication/getAccessToken";

        try {
            $response = $this->client->post($url, [
                'json' => [
                    'email' => $this->email,
                    'password' => $this->password,
                ],
                'verify' => false, // Bypass SSL verification
            ]);

            $data = json_decode($response->getBody(), true);
            $this->logResponse($url, $data);

            if ($data['result']) {
                $this->updateTokens($data['data']);
                return $this->accessToken;
            } else {
                throw new Exception("Login failed: " . $data['message']);
            }
        } catch (RequestException $e) {
            $this->handleError($e);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    // Handle errors (network issues, API errors, etc.)
    private function handleError($exception)
    {
        $this->logger->error("Error: " . $exception->getMessage());
        echo "An error occurred: " . $exception->getMessage() . PHP_EOL;
    }

    // Log API responses for debugging
    private function logResponse($url, $response)
    {
        $this->logger->info("Response from {$url}: " . json_encode($response, JSON_PRETTY_PRINT));
    }

    // Update the .env file or environment variable with the new token values
    private function updateEnv($key, $value)
    {
        if ($value) {
            $envFile = __DIR__ . '/../.env';

            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);

                if (preg_match("/^$key=/m", $envContent)) {
                    $envContent = preg_replace("/^$key=.*/m", "$key=$value", $envContent);
                } else {
                    $envContent .= "\n$key=$value";
                }

                file_put_contents($envFile, $envContent);
            }
        }
    }

    // Log debugging information
    private function log($message)
{
    $this->logger->info($message);
    error_log("[Authenticator] " . $message); // Logs to PHP error log as a fallback
}

}
