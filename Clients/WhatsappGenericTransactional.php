<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm\WhatsappGenericTransactional;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for sending messages via WhatsApp
 */
class WhatsappGenericTransactional extends Base
{
    private $_service;
    private $_queue;

    public function __construct($queue, $service)
    {
        $this->_queue = $queue;
        $this->_service = Helper::getValidAppName($service);
    }

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
        $expires_on = null)
    {
        $job = new WhatsappGenericTransactional();

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
        $job->hsm_template_hash = $hsm_template ? md5($hsm_template) : null;
        $job->hsm_data = $hsm_data;
        $job->expires_on = $expires_on;
        $job->initiated_on = date('Y-m-d H:i:s');

        $jsonifiedJob = json_encode($job);
        $this->validatePayload(json_decode($jsonifiedJob, true));

        $this->_queue->setDomain($this->_domain);

        return $this->_queue
            ->setQueueName(Queue::GENERIC_TRANSACTIONAL_WHATSAPP_SENDER)
            ->sendMessage($jsonifiedJob, 10);
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
            'HSM'      => array('hsm_template_hash', 'hsm_data')
        );

        $messageType = strtoupper($payload['whatsapp_message_type']);
        if (!isset($typeRequiredKeys[$messageType])) {
            throw new \Exception("Invalid WhatsApp message type $messageType");
        }

        $missingKeys = array_diff($typeRequiredKeys[$messageType], array_keys($payload));
        if (count($missingKeys)) {
           throw new \Exception('Missing keys: ' . implode(',', $missingKeys));
        }

        if (isset($payload['expires_on']) && ($payload['expires_on'] < strtotime('now'))) {
            throw new \Exception("Expiry time less than current time. Job would never run.");
        }
    }
}
