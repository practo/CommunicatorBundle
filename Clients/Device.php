<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for handling device
 */
class Device extends Base
{
    private $_apiSecretKey;

    public function __construct($apiSecretKey)
    {
        $this->_apiSecretKey = $apiSecretKey;
    }

    /*
     * @param string $accountId      - Practo account id of the user
     * @param string $appName        - App name ex: fabric-droid, consult-ui
     * @param string $appVersion     - App version ex: 1.0.0
     * @param string $deviceId       - Unique fingerprint of the user's device
     * @param string $lastActive     - Of format 1970-01-01 00:00:00
     * @param string $pushTokenType  - Push token type
     * @param string $pushToken      - Push token type's value
     * @param string $fcmToken       - User's fcm token
     */
    public function registerDevice(
        $accountId,
        $appName,
        $appVersion,
        $deviceId,
        $lastActive,
        $pushTokenType,
        $pushToken,
        $fcmToken
    )
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $result = $client->post("/push/register_device", [
            'account_id' => $accountId,
            'app_name' => $appName,
            'app_version' => $appVersion,
            'device_id' => $deviceId,
            'last_active' => $lastActive,
            'push_token_type' => $pushTokenType,
            'push_token' => $pushToken,
            'fcm_token' => $fcmToken,
        ]);

        return $result;
    }
}

