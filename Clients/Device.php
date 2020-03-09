<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Clients\Helper;

/**
 * Client for handling device
 */
class Device extends Base
{
    private $_apiSecretKey;
    private $_appName;

    public function __construct($apiSecretKey, $appName)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_appName = Helper::getValidAppName($appName);
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
        $appVersion,
        $deviceId,
        $lastActive,
        $pushTokenType,
        $pushToken,
        $fcmToken,
        $appName = null
    )
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $result = $client->post("/push/register_device", [
            'account_id' => $accountId,
            'app_name' => $appName ? $appName : $this->_appName,
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

