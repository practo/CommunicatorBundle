<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Clients\Helper;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for taking and checking WhatsApp consent
 */
class WhatsAppConsent extends Base
{
    private $_apiSecretKey;
    private $_service;

    public function __construct($apiSecretKey, $service)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_service = Helper::getValidAppName($service);
    }

    /**
     * Send WhatsApp Consent
     *
     * @param string  $mobile
     * @param string  $accountId
     * @param boolean $consentGiven
     * @param array   $serviceHeaders
     *
     * @return array Consent Approval response
     */
    public function updateWhatsAppConsent($mobile, $accountId, $consentGiven, $serviceHeaders)
    {
        $this->validateServiceHeaders($serviceHeaders);
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [
            'mobile'        => $mobile,
            'account_id'    => $accountId,
            'consent_given' => $consentGiven
        ];
        $result = $client->post("/whatsapp/consent", $params, $serviceHeaders);

        return $result;
    }

    /**
     * Fetch WhatsApp Consent
     *
     * @param string $mobile
     * @param string $accountId
     * @param array  $serviceHeaders
     *
     * @return array Consent status response
     */
    public function getWhatsAppConsent($mobile, $accountId, $serviceHeaders)
    {
        $this->validateServiceHeaders($serviceHeaders);
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [
            'mobile'     => $mobile,
            'account_id' => $accountId
        ];

        return $client->get('/whatsapp/consent', $params, $serviceHeaders);
    }

    private function validateServiceHeaders($serviceHeaders)
    {
        $isValidHeader = false;
        if (isset($serviceHeaders['Service-Nonce'])
            && isset($serviceHeaders['User-Agent'])) {
            $isValidHeader = true;
        } else if (isset($serviceHeaders['token'])) {
            $isValidHeader = true;
        }
        $errorMessage = "Missing Headers: Service-Nonce with User-Agent OR token  must be present";
        if (!$isValidHeader) {
            throw new \Exception($errorMessage);
        }
    }
}
