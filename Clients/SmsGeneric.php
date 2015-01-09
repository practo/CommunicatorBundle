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

    public function sendSms($generic_sms_id, $practice_id,
                            $name, $to, $personType, $messageType, $body,
                            $practice_doctor_id = null,
                            $notes = null, $expiryTime = null)
    {
        $job = new praccomm\GenericSms();
        $job->generic_sms_id = $generic_sms_id;
        $job->practice_id = $practice_id;
        $job->practice_doctor_id = $practice_doctor_id;
        $job->name = $name;
        $job->mobile = $to;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->notes = $notes;
        $job->message_type = $this->getSMSType($messageType);
        $job->app_name = $this->_appName;
        $job->person_type = $this->getPersonType($personType);
        $job->sms_type = 'generic';
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
            case "VN_DETAILS":
              return praccomm\GenericSMSType::VN_DETAILS;
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