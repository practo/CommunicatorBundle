<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for fetching device information
 */
class DeviceInformation extends Base
{
    public function __construct($apiSecretKey)
    {
        $this->_apiSecretKey = $apiSecretKey;
    }

    public function getDeviceInformation($accountId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['account_id'] = $accountId;
        $result = $client->get("/deviceinformation/get_device_information", $params);

        return $result;
    }
}