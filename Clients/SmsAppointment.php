<?php

namespace Practo\CommunicatorBundle\Clients;

use Pheanstalk\Pheanstalk;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Clinet for messaging doctors
 */
class SmsAppointment
{
    private $_smsServer;
    private $_smsPort;
    private $_sendAppointmentSmsQueue;
    private $_updateAppointmentSmsQueue;
    private $_appName;
    private $_pheanstalk;

    private function __construct($pheanstalk, $sendQueue, $updateQueue,
                                 $serviceHost, $servicePort, $appName)
    {
        $this->_smsServer = $serviceHost;
        $this->_smsPort = $servicePort;
        $this->_sendAppointmentSmsQueue = $sendQueue;
        $this->_updateAppointmentSmsQueue = $updateQueue;
        $this->_pheanstalk = $pheanstalk;
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
        $queueName = $this->_sendSmsQueue;
        $this->_pheanstalk->useTube($queueName)->put($jsonifiedJob);
    }

    public function getMessagesByAppointmentId($apptId, $smsTypes)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_messages_by_appointment_id($apptid, $smsTypes);
        $transport->close();
        return $result;
    }

    private function getTransport()
    {
        $socket = new Thrift\Transport\TSocket($this->_smsServer,
                                               $this->_smsPort);
        $socket->setRecvTimeout(20 * 1000);
        $socket->setSendTimeout(20 * 1000);
        $transport =
          new Thrift\Transport\TFramedTransport($socket, 1024, 1024);
        return $transport;
    }

    private function getClient($transport)
    {
        $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
        $client = new praccomm\AppointmentSMSService($protocol);
        return $client;
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
            default:
              throw new Exception("Invalid message type");
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
              throw new Exception("Invalid person type");
        }
    }
}