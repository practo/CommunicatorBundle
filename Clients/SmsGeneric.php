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
    private $_appName;
    private $_queue;

    public function __construct($queue, $appName)
    {
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendSms($reference_id, $reference_type,
                            $name, $to, $messageType, $body,
                            $notes = null, $expiryTime = null)
    {
        $this->validateMailType(strtoupper($messageType));
        $job = new praccomm\GenericSms();
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->name = $name;
        $job->mobile = $to;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->notes = $notes;
        $job->message_type = strtoupper($messageType);
        $job->app_name = $this->_appName;
        $job->sms_type = 'generic';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_GENERIC_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function validateSmsType($type)
    {
        $allowedTypes = array(
            'WEEKELY_ROI_SMS',
            'VN_DETAILS',
        );
        if (!in_array($type, $allowedTypes, true)) {
            throw new Exception("Invalid message type");
        }
    }
}
?>