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
     *
     * @return array Consent Approval response
     */
    public function updateWhatsAppConsent($mobile, $accountId, $consentGiven)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [
            'mobile'        => $mobile,
            'account_id'    => $accountId,
            'consent_given' => $consentGiven
        ];
        $result = $client->post("/whatsapp/consent", $params);

        return $result;
    }

    /**
     * Fetch WhatsApp Consent
     *
     * @param string $mobile
     * @param string $accountId
     *
     * @return array Consent status response
     */
    public function getWhatsAppConsent($mobile, $accountId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [
            'mobile'     => $mobile,
            'account_id' => $accountId
        ];

        return $client->get('/whatsapp/consent', $params);
    }
}
