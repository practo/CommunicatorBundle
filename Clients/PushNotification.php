<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Clients\Helper;
use Practo\CommunicatorBundle\Exceptions\PushValidationException;

/**
 * Client for handling device
 */
class PushNotification extends Base
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
     * @param string $deviceId       - Unique fingerprint of the user's device
     * @param string $lastActive     - Of format 1970-01-01 00:00:00
     * @param string $pushTokenType  - Push token type
     * @param string $pushToken      - Push token type's value
     * @param string $fcmToken       - User's fcm token
     * @param string $appVersion     - App version ex: 1.0.0
     * @param string $appName        - App name ex: fabric-droid, consult-ui
     * @param string $appVersionCode - App version code
     */
    public function registerDevice(
        int $accountId,
        string $deviceId,
        string $lastActive,
        string $pushTokenType,
        string $pushToken,
        string $fcmToken,
        string $appVersion = null,
        string $appName = null,
        string $appVersionCode = null
    )
    {
        if (!$appVersionCode && !$appVersion) {
            throw new PushValidationException('Either of appVersion or appVersionCode is required');
        }

        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $result = $client->post("/push/register_device", [
            'account_id' => $accountId,
            'app_name' => $appName ? $appName : $this->_appName,
            'app_version' => $appVersion,
            'app_version_code' => $appVersionCode,
            'device_id' => $deviceId,
            'last_active' => $lastActive,
            'push_token_type' => $pushTokenType,
            'push_token' => $pushToken,
            'fcm_token' => $fcmToken,
        ]);

        return $result;
    }
}
