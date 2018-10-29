<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Clients\Helper;
use Practo\CommunicatorBundle\Clients\CommunicatorClientV2;

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
        $client = new CommunicatorClientV2($this->getCommunicatorUri(),
            $this->_apiSecretKey,
            $this->_service
        );
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
        $client = new CommunicatorClientV2($this->getCommunicatorUri(),
            $this->_apiSecretKey,
            $this->_service
        );
        $params = [
            'mobile'     => $mobile,
            'account_id' => $accountId
        ];

        return $client->get('/whatsapp/consent', $params, $serviceHeaders);
    }
}
