<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Clinet for messaging doctors
 */
class SmsAppointment
{
    private $_communicatorBaseUri;
    private $_apiSecretKey;
    private $_appName;
    private $_queue;

    public function __construct($queue, $communicatorBaseUri,
                                $apiSecretKey, $appName)
    {
        $this->_communicatorBaseUri = $communicatorBaseUri;
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendSms($appointmentId, $name, $to, $personType,
                            $messageType, $body, $notes = null,
                            $expiryTime = null)
    {
        $job = new praccomm\AppointmentSms();
        $job->appointment_id = $appointmentId;
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
                    ->setQueueName(Queue::SEND_APPOINTMENT_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }


    public function getMessagesByAppointmentId($apptId, $smsTypes)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['appointment_id'] = $apptId;
        $params['sms_types'] = $smsTypes;
        $result = $client->get("/appointmentsms/get_messages_by_appointment_id", $params);
        return $result;
    }

    private function getSMSType($type)
    {
        switch($type)
        {
            case "VERIFICATION":
              return praccomm\AppointmentSMSType::VERIFICATION;
            case "REQUEST":
              return praccomm\AppointmentSMSType::REQUEST;
            case "CONFIRMATION":
              return praccomm\AppointmentSMSType::CONFIRMATION;
            case "CANCELLATION":
              return praccomm\AppointmentSMSType::CANCELLATION;
            case "RESCHEDULE":
              return praccomm\AppointmentSMSType::RESCHEDULE;
            case "TEST":
              return praccomm\AppointmentSMSType::TEST;
            case "PASSED_ON":
              return praccomm\AppointmentSMSType::PASSED_ON;
            case "WALK_IN":
              return praccomm\AppointmentSMSType::WALK_IN;
            case "PENDING":
              return praccomm\AppointmentSMSType::PENDING;
            default:
              throw new \Exception("Invalid message type");
        }
    }

    private function getPersonType($type)
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
              throw new \Exception("Invalid person type");
        }
    }
}