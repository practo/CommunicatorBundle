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
    private $_sendGenericSmsQueue;
    private $_updateGenericSmsQueue;
    private $_appName;
    private $_queue;

    public function __construct($queue, $appName)
    {
        $this->_queue = $queue;
        $this->_appName = getValidAppName($appName);
    }

    public function sendSms($name, $to, $personType,
                            $messageType, $body, $notes = null,
                            $expiryTime = null)
    {
        $job = new praccomm\GenericSms();
        $job->name = $name;
        $job->mobile = $to;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->notes = $notes;
        $job->message_type = $this->getSMSType($messageType);
        $job->app_name = $this->_appName;
        $job->person_type = $this->getPersonType($personType);
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function getSMSType($type)
    {
        switch($type)
        {
            case "WEEKLY_ROI_SMS":
              return praccomm\GenericSMSType::WEEKLY_ROI_SMS;
            default:
              throw new Exception("Invalid message type");
        }
    }

    public function getPersonType($type)
    {
        switch($type)
        {
            case "PATIENT":
              return praccomm\PersonType::PATIENT;
            case "STAFF":
              return praccomm\PersonType::STAFF;
            case "DOCTOR":
              return praccomm\PersonType::DOCTOR;
            default:
              throw new Exception("Invalid person type");
        }
    }
}
?>