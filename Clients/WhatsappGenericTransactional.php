<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for sending push notifications
 */
class WhatsappGenericTransactional extends Base
{
    private $_service;
    private $_queue;
    private $_apiSecretKey;

    public function __construct($queue, $apiSecretKey, $service)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_service = Helper::getValidAppName($service);
    }

    public function sendGenericTransactionalWhatsapp(
        $clientAppName,
        $mobile,
        $whatsapp_message_type,
        $message_type,
        $reference_id,
        $reference_type,
        $message_text,
        $media_s3_path,
        $preview_first_url,
        $hsm_id,
        $hsm_data
        $expires_on,
    )
    {
        $this->validateWhatsappMessageType(strtoupper($notification_type));
        $job = new praccomm\WhatsappGenericTransactional();

        $job->service = $this->_service;
        $job->app_name = $clientAppName;
        $job->mobile = $mobile;
        $job->whatsapp_message_type = $whatsapp_message_type;
        $job->message_type = $message_type;
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->message_text = $message_text;
        $job->media_s3_path = $media_s3_path;
        $job->preview_first_url = $preview_first_url;
        $job->hsm_id = $hsm_id;
        $job->hsm_data = $hsm_data;
        $job->expires_on = $expires_on;
        $job->initiated_on = date("Y-m-d H:i:s");

        $jsonifiedJob = json_encode($job);
        $this->validatePayload(json_decode($jsonifiedJob, true));

        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SEND_GENERIC_TRANSACTIONAL_WHATSAPP)
                    ->sendMessage($jsonifiedJob, 10);
    }

    private function validatePayload($payload)
    {
        $RequiredKeys = array(
            'app_name',
            'reference_type',
            'reference_id',
            'whatsapp_message_type',
            'message_type',
            'mobile'
        )
        $allowedWAMessageTypes = array(
            'IMAGE',
            'TEXT',
            'DOCUMENT',
            'AUDIO',
            'HSM'
        )
        $typeRequiredKeys = array(
            "IMAGE" => array("media_s3_url"),
            "AUDIO" => array("media_s3_url"),
            "TEXT"  => array("message_text"),
            "DOCUMENT" => array("media_s3_url"),
            "HSM" => array("hsm_id", "hsm_data")
        )
        foreach ($RequiredKeys as $key) {
            if (!isset($key, $payload)){
                throw new \Exception("Required key ". $key . " not present.");
            }
        }
        if (!in_array($payload['whatsapp_message_type'], $allowedWAMessageTypes){
            throw new \Exception("Invalid whatsapp message type ". $payload['whatsapp_message_type']);
        }
        foreach ($typeRequiredKeys[$payload['whatsapp_message_type']] as $key){
            if (!isset($key, $payload)){
                throw new \Exception("Type Required key ". $key . " not present.");
            }
        }
        if (isset('expires_on', $payload) && !($payload['expires_on'] > strtotime('now'))) {
            throw new \Exception("Expiry time less than current time. Job would never run.")
        }
    }
}
