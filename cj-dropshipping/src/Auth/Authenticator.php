<?php

namespace Lunx\CjDropshipping\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Dotenv\Dotenv;
use Exception;

class Authenticator
{
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
        $this->apiUrl = getenv('CJ_API_BASE_URL');
        $this->email = $email;
        $this->password = $password;
        
        // Load .env if available for environment variables
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }

        $this->accessToken = getenv('CJ_ACCESS_TOKEN');
        $this->refreshToken = getenv('CJ_REFRESH_TOKEN');
        $this->accessTokenExpiryDate = getenv('CJ_ACCESS_TOKEN_EXPIRY');
        $this->refreshTokenExpiryDate = getenv('CJ_REFRESH_TOKEN_EXPIRY');
    }

    // Get access token
    public function getAccessToken()
    {
        // Check if the access token is expired before using it
        if ($this->isTokenExpired($this->accessTokenExpiryDate)) {
            $this->refreshAccessToken();
        }

        return $this->accessToken;
    }

    // Refresh access token
    private function refreshAccessToken()
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/authentication/refreshAccessToken", [
                'json' => [
                    'refreshToken' => $this->refreshToken,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                $this->accessToken = $data['data']['accessToken'];
                $this->accessTokenExpiryDate = $data['data']['accessTokenExpiryDate'];
                $this->refreshToken = $data['data']['refreshToken'];
                $this->refreshTokenExpiryDate = $data['data']['refreshTokenExpiryDate'];

                // Update environment variables
                $this->updateEnv('CJ_ACCESS_TOKEN', $this->accessToken);
                $this->updateEnv('CJ_ACCESS_TOKEN_EXPIRY', $this->accessTokenExpiryDate);
                $this->updateEnv('CJ_REFRESH_TOKEN', $this->refreshToken);
                $this->updateEnv('CJ_REFRESH_TOKEN_EXPIRY', $this->refreshTokenExpiryDate);

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
        $currentTime = new \DateTime();
        $expiryTime = new \DateTime($expiryDate);

        return $currentTime >= $expiryTime;
    }

    // Log out (invalidate tokens)
    public function logout()
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/authentication/logout", [
                'headers' => [
                    'CJ-Access-Token' => $this->accessToken,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                echo "Successfully logged out.";
                // Clear tokens from environment
                $this->clearEnvTokens();
            } else {
                throw new Exception("Logout failed: " . $data['message']);
            }
        } catch (RequestException $e) {
            $this->handleError($e);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    // Log in to get initial tokens
    public function login()
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/authentication/getAccessToken", [
                'json' => [
                    'email' => $this->email,
                    'password' => $this->password,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['result']) {
                $this->accessToken = $data['data']['accessToken'];
                $this->accessTokenExpiryDate = $data['data']['accessTokenExpiryDate'];
                $this->refreshToken = $data['data']['refreshToken'];
                $this->refreshTokenExpiryDate = $data['data']['refreshTokenExpiryDate'];

                // Store tokens securely in environment variables
                $this->updateEnv('CJ_ACCESS_TOKEN', $this->accessToken);
                $this->updateEnv('CJ_ACCESS_TOKEN_EXPIRY', $this->accessTokenExpiryDate);
                $this->updateEnv('CJ_REFRESH_TOKEN', $this->refreshToken);
                $this->updateEnv('CJ_REFRESH_TOKEN_EXPIRY', $this->refreshTokenExpiryDate);

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
        // Log the error details for debugging (you can implement your own logging system)
        error_log($exception->getMessage());
        echo "An error occurred: " . $exception->getMessage();
    }

    // Update the .env file or environment variable with the new token values
    private function updateEnv($key, $value)
    {
        if ($value) {
            $envFile = __DIR__ . '/../.env';

            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);

                // Check if the key exists, then update it, else add a new line
                if (preg_match("/^$key=/m", $envContent)) {
                    $envContent = preg_replace("/^$key=.*/m", "$key=$value", $envContent);
                } else {
                    $envContent .= "\n$key=$value";
                }

                file_put_contents($envFile, $envContent);
            }
        }
    }

    // Clear tokens from environment
    private function clearEnvTokens()
    {
        $this->updateEnv('CJ_ACCESS_TOKEN', '');
        $this->updateEnv('CJ_REFRESH_TOKEN', '');
        $this->updateEnv('CJ_ACCESS_TOKEN_EXPIRY', '');
        $this->updateEnv('CJ_REFRESH_TOKEN_EXPIRY', '');
    }
}
