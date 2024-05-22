<?php

namespace Survos\GoogleSheetsBundle\Service;

use Google_Client;
use Google_Service_Sheets;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * GoogleApiClientService Class
 *
 * @package Survos\GoogleSheetsBundle\Service
 */
class GoogleApiClientService
{

    /**
     * Initiate the service
     */
    public function __construct(private readonly string $applicationName = '', private string $credentials = '', private readonly string $clientSecret = '')
    {
    }

    /**
     * Get the new google api client
     *
     * @param string $type
     * @return Google_Client
     */
    public function getClient($type = 'offline'): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName($this->applicationName);
        $client->setAuthConfig(json_validate($this->clientSecret) ? json_decode($this->clientSecret, true) : $this->clientSecret);
        $client->setAccessType($type);
        return $client;
    }

    /**
     * Validate and set access token
     *
     * @param Google_Client $client
     * @return Google_Client
     */
    public function setClientVerification(Google_Client $client): Google_Client
    {
        $credentialsPath = $this->credentials;
        $accessToken = $this->getAccessToken($credentialsPath);
        dd($accessToken);
        $client->setAccessToken($accessToken);
        return $this->ValidateAccessToken($client, $credentialsPath);
    }

    /**
     * Get access token
     *
     * @param string $credentialsPath
     * @return array
     */
    public function getAccessToken(?string $credentialsPath = ''): array
    {
        assert($credentialsPath, "missing credentialsPath");
        if (!file_exists($credentialsPath)) {
            throw new FileNotFoundException('Access Token does not exists path ' . $credentialsPath);
        }
        return json_decode(file_get_contents($credentialsPath), true);
    }

    /**
     * Validate access token
     *
     * @param Google_Client $client
     * @param string $credentialsPath
     * @return Google_Client
     */
    public function ValidateAccessToken(Google_Client $client, $credentialsPath = ''): Google_Client
    {
        if ($client->isAccessTokenExpired() && !empty($credentialsPath)) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /**
     * Create the new access token
     * Need to be run on command line manually.
     *
     * @param Google_Client $client
     * @return string
     */
    public function createNewAccessToken(Google_Client $client): bool|string
    {
        $credentialsPath = $this->credentials;
        $authCode = $this->getVerificationCode($client);
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        try {
            return $this->saveAccessToken($credentialsPath, $accessToken);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Save the new access token
     *
     * @param string $credentialsPath
     * @param string $accessToken
     * @return boolean
     */
    public function saveAccessToken($credentialsPath = '', $accessToken = ''): bool
    {
        if (!empty($credentialsPath)) {
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            return true;
        }
        return false;
    }

    /**
     * Get the verification code from command line
     *
     * @param Google_Client $client
     * @return string
     */
    public function getVerificationCode(Google_Client $client): string
    {
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        return trim(fgets(STDIN));
    }

    /**
     * Create the new google sheet api access token
     *
     * @return boolean
     */
    public function createNewSheetApiAccessToken(): bool|string
    {
        $client = $this->getClient('offline');
        $client->setScopes(implode(' ', [Google_Service_Sheets::DRIVE]));
        return $this->createNewAccessToken($client);
    }
}
