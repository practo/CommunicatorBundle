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
        string $accountId,
        string $deviceId,
        string $lastActive,
        string $pushTokenType,
        string $pushToken,
        string $fcmToken = null,
        string $appVersion = null,
        string $appName = null,
        string $appVersionCode = null
    ) {
        $this->validateRegisterDevicePayload(
            $pushToken,
            $fcmToken,
            $appVersion,
            $appName,
            $appVersionCode
        );

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

    private function validateRegisterDevicePayload(
        string $pushToken = null,
        string $fcmToken = null,
        string $appVersion = null,
        string $appName = null,
        string $appVersionCode = null
    ) {
        $errors = [];
        if (empty($appName)) {
            array_push(
                $errors,
                "App Name is required"
            );
        }
        if (empty($pushToken) && empty($fcmToken)) {
            array_push($errors,
                "fcm_token and push_token is missing in payload"
            );  
        }
        if (empty($appVersionCode) && empty($appVersion)) {
            array_push($errors, 'Either of appVersion or appVersionCode is required');
        }

        if(!empty($errors)) {
            $errorString = "";
            foreach ($errors as $error) {
                $errorString .= $error;
            }
            throw new PushValidationException($errorString);
        }
    }
}
