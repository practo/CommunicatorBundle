<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for sending messages via WhatsApp
 */
class WhatsappGenericTransactional extends Base
{
    private $_apiSecretKey;
    private $_service;
    private $_queue;

    public function __construct($queue, $apiSecretKey, $service)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_service = Helper::getValidAppName($service);
        $this->_queue = $queue;
    }

    /**
     * Send WhatsApp message
     *
     * @param string $mobile
     * @param string $whatsapp_message_type
     * @param string $message_type
     * @param string $reference_id
     * @param string $reference_type
     * @param string $message_text
     * @param string $media_s3_path
     * @param string $preview_first_url
     * @param string $hsm_template
     * @param array  $hsm_data
     * @param string $expires_at
     *
     * @return string Transaction ID
     */
    public function sendGenericTransactionalWhatsapp(
        $mobile,
        $whatsapp_message_type,
        $message_type,
        $reference_id,
        $reference_type,
        $message_text = null,
        $media_s3_path = null,
        $preview_first_url = null,
        $hsm_template = null,
        $hsm_data = null,
        $expires_at = null)
    {
        $job = new \praccomm\WhatsappGenericTransactional();

        $job->service = $this->_service;
        $job->app_name = $this->_service;
        $job->mobile = $mobile;
        $job->whatsapp_message_type = $whatsapp_message_type;
        $job->message_type = $message_type;
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->message_text = $message_text;
        $job->media_s3_path = $media_s3_path;
        $job->preview_first_url = $preview_first_url;
        $job->hsm_hash = $hsm_template ? hash('sha256', $hsm_template) : null;
        $job->hsm_data = json_encode($hsm_data);
        $job->expires_at = $expires_at;
        $job->initiated_at = date('Y-m-d H:i:s');

        // Combination of timestamp + 20 random characters.
        $job->transaction_id = time() . substr(str_shuffle(
            str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', 3)), 0, 20);

        $jsonifiedJob = json_encode($job);
        $this->validatePayload(json_decode($jsonifiedJob, true));

        $this->_queue->setDomain($this->_domain);

        $this->_queue
            ->setQueueName(Queue::GENERIC_TRANSACTIONAL_WHATSAPP_SENDER)
            ->sendMessage($jsonifiedJob, 10);

        return $job->transaction_id;
    }

    /**
     * Get status of a previously sent WhatsApp message using transaction ID
     *
     * @param string $transactionId
     *
     * @return array
     */
    public function getByTransactionId($transactionId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [
            'transaction_id' => $transactionId
        ];
        return $client->get('/generic_transactional_whatsapp/get_by_transaction_id', $params);
    }

    private function validatePayload($payload)
    {
        $requiredKeys = array(
            'app_name',
            'reference_type',
            'reference_id',
            'whatsapp_message_type',
            'message_type',
            'mobile'
        );
        $missingKeys = array_diff($requiredKeys, array_keys($payload));

        if (count($missingKeys)) {
           throw new \Exception('Missing keys: ' . implode(',', $missingKeys));
        }

        $typeRequiredKeys = array(
            'TEXT'     => array('message_text'),
            'IMAGE'    => array('media_s3_url'),
            'AUDIO'    => array('media_s3_url'),
            'DOCUMENT' => array('media_s3_url'),
            'HSM'      => array('hsm_hash', 'hsm_data')
        );

        $messageType = strtoupper($payload['whatsapp_message_type']);
        if (!isset($typeRequiredKeys[$messageType])) {
            throw new \Exception("Invalid WhatsApp message type $messageType");
        }

        $missingKeys = array_diff($typeRequiredKeys[$messageType], array_keys($payload));
        if (count($missingKeys)) {
           throw new \Exception('Missing keys: ' . implode(',', $missingKeys));
        }

        if (isset($payload['expires_at']) && ($payload['expires_at'] < strtotime('now'))) {
            throw new \Exception("Expiry time less than current time. Job would never run.");
        }
    }
}
