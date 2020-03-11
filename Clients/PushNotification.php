<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Clients\Helper;
use Practo\CommunicatorBundle\Exceptions\PushValidationException;

/**
 * Clients for handling PushNotification
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
     * Registers a user's device for push notifications
     *
     * @param string $deviceId       - Unique fingerprint of the user's device
     * @param string $lastActive     - Of format 1970-01-01 00:00:00
     * @param string $pushTokenType  - Push token type
     * @param string $pushToken      - Push token type's value
     * @param string $fcmToken       - User's fcm token
     * @param string $appVersion     - App version ex: 1.0.0
     * @param string $accountId      - Practo account id of the user
     * @param string $appName        - App name ex: fabric-droid, consult-ui
     * @param string $appVersionCode - App version code
     */
    public function postRegisterDevice(
        string $deviceId,
        string $lastActive,
        string $pushTokenType,
        string $pushToken,
        string $fcmToken = null,
        string $appVersion = null,
        string $accountId = null,
        string $appName = null,
        string $appVersionCode = null
    ) {
        $this->validate(
            $pushToken,
            $fcmToken,
            $appVersion,
            $appName,
            $appVersionCode
        );

        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $result = $client->post("/push/register_device", [
            'payload' => json_encode([
                'account_id' => $accountId,
                'app_name' => $appName ? $appName : $this->_appName,
                'app_version' => $appVersion,
                'app_version_code' => $appVersionCode,
                'device_id' => $deviceId,
                'last_active' => $lastActive,
                'push_token_type' => $pushTokenType,
                'push_token' => $pushToken,
                'fcm_token' => $fcmToken,
            ]),
        ], false);

        return $result;
    }

    /*
     * Used to update device information, example usescases involve:
     * 1. Update a push token for a deviceId in case it's refreshed on client
     *
     * @param string $deviceId       - Unique fingerprint of the user's device
     * @param string $lastActive     - Of format 1970-01-01 00:00:00
     * @param string $pushTokenType  - Push token type
     * @param string $pushToken      - Push token type's value
     * @param string $fcmToken       - User's fcm token
     * @param string $appVersion     - App version ex: 1.0.0
     * @param string $appName        - App name ex: fabric-droid, consult-ui
     * @param string $appVersionCode - App version code
     */
    public function patchDeviceInformation(
        string $authKey,
        string $deviceId,
        string $lastActive,
        string $pushTokenType,
        string $pushToken,
        string $fcmToken = null,
        string $appVersion = null,
        string $appName = null,
        string $appVersionCode = null
    ) {
        $this->validate(
            $pushToken,
            $fcmToken,
            $appVersion,
            $appName,
            $appVersionCode
        );

        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);

        $authHeaders = $client->getAuthHeaders($authKey);
        $authHeaders['X-COMM-AUTH-TOKEN'] = $authHeaders['X-AUTH-TOKEN'];

        $result = $client->patch("/push/device_information", [
            'payload' => json_encode([
                'app_name' => $appName ? $appName : $this->_appName,
                'app_version' => $appVersion,
                'app_version_code' => $appVersionCode,
                'device_id' => $deviceId,
                'last_active' => $lastActive,
                'push_token_type' => $pushTokenType,
                'push_token' => $pushToken,
                'fcm_token' => $fcmToken,
            ]),
        ],
        $authHeaders
    );

        return $result;
    }

    /*
     * Updates push token related user information, example usecases involve:
     * 1. Linking the token with an account (in cases where an account_id was not passed in registerDevice)
     * 2. Delinking token for the user in cases like logout
     *
     * @param string $accountId      - Practo account id of the user
     * @param string $deviceId       - Unique fingerprint of the user's device
     * @param string $lastActive     - Of format 1970-01-01 00:00:00
     * @param string $pushTokenType  - Push token type
     * @param string $pushToken      - Push token type's value
     * @param string $fcmToken       - User's fcm token
     * @param string $appVersion     - App version ex: 1.0.0
     * @param string $appName        - App name ex: fabric-droid, consult-ui
     * @param bool   $removeUser     - To delink the user account for ex: post logout
     * @param string $appVersionCode - App version code
     */
    public function patchUserInformation(
        string $accountId,
        string $deviceId,
        string $lastActive,
        string $pushTokenType,
        string $pushToken,
        string $fcmToken = null,
        string $appVersion = null,
        string $appName = null,
        string $removeUser = null,
        string $appVersionCode = null
    ) {
        $this->validate(
            $pushToken,
            $fcmToken,
            $appVersion,
            $appName,
            $appVersionCode
        );

        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $result = $client->patch("/push/user_information", [
            'payload' => json_encode([
                'account_id' => $accountId,
                'app_name' => $appName ? $appName : $this->_appName,
                'app_version' => $appVersion,
                'app_version_code' => $appVersionCode,
                'device_id' => $deviceId,
                'last_active' => $lastActive,
                'push_token_type' => $pushTokenType,
                'push_token' => $pushToken,
                'fcm_token' => $fcmToken,
                'remove_user' => $removeUser,
            ]),
        ]);

        return $result;
    }

    private function validate(
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
                $errorString .= $error."\n";
            }
            throw new PushValidationException($errorString);
        }
    }
}
