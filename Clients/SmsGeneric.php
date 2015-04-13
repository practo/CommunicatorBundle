<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Clinet for generic sms
 */
class SmsGeneric
{
    private $appName;
    private $queue;

    public function __construct($queue, $appName)
    {
        $this->queue = $queue;
        $this->appName = Helper::getValidAppName($appName);
    }

    public function sendSms(
        $reference_id,
        $reference_type,
        $name,
        $to,
        $messageType,
        $body,
        $notes = null,
        $expiryTime = null
    ) {
        $this->validateSmsType(strtoupper($messageType));
        $job = new praccomm\GenericSms();
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->name = $name;
        $job->mobile = $to;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->notes = $notes;
        $job->message_type = strtoupper($messageType);
        $job->app_name = $this->appName;
        $job->sms_type = 'generic';
        $jsonifiedJob = json_encode($job);
        return $this->queue
                    ->setQueueName(Queue::SEND_GENERIC_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    private function validateSmsType($type)
    {
        $allowedTypes = array(
            'WEEKLY_ROI_SMS',
            'VN_DETAILS',
            'SURVEY_SMS'
        );
        if (!in_array($type, $allowedTypes, true)) {
            throw new Exception("Invalid message type");
        }
    }
}
