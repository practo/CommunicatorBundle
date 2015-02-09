<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Mail clinet for mailing doctors
 */
class SmsDoctor
{
    private $smsServer;
    private $smsPort;
    private $appName;
    private $queue;

    public function __construct(
        $queue,
        $serviceHost,
        $servicePort,
        $appName
    ) {
        $this->smsServer = $serviceHost;
        $this->smsPort = $servicePort;
        $this->appName = Helper::getValidAppName($appName);
        $this->queue = $queue;
    }

    public function sendSms(
        $practiceId,
        $doctorProfileId,
        $name,
        $to,
        $type,
        $body,
        $unverified = false,
        $smsDisabled = false,
        $reduceCredit = false
    ) {
        $job = new praccomm\DoctorSms();
        $job->practice_id = $practiceId;
        $job->doctor_profile_id = $doctorProfileId;
        $job->name = $name;
        $job->mobile = $to;
        $job->secondary_mobile = $secondaryMobile;
        $job->expiry_time = $expiryTime;
        $job->message_type = $this->getSMSType($type);
        $job->message_text = $body;
        $job->app_name = $this->appName;
        $job->reduce_credit = $reduceCredit;
        $job->sms_type = 'doctor';
        if ($unverified) {
            $job->status = 'FAILED';
            $job->failure_reason = 'MOBILEVERIFICATION';
        }
        if ($smsDisabled) {
            $job->status = 'FAILED';
            $job->failure_reason = 'SMSDISABLED';
        }
        $jsonifiedJob = json_encode($job);
        return $this->queue
                    ->setQueueName(Queue::SEND_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateSms(
        $key,
        $status,
        $deliveryTime,
        $operator,
        $opReply,
        $opCode
    ) {
        $job = new praccomm\DoctorSms();
        $job->status = Helper::getSMSDeliveryStatus($status);
        $job->doctor_sms_id = $key;
        $job->delivery_time = $deliveryTime;
        $job->operator = $operator;
        $job->operator_reply = $opReply;
        $job->operator_code = $opCode;
        $job->sms_type = 'doctor';
        $jsonifiedJob = json_encode($job);
        return $this->queue
                    ->setQueueName(Queue::UPDATE_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }


    public function searchSmsByPracticeId($pid, $sendDate, $smsTypes)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->search_by_practice_id($pid, $sendDate, $smsTypes);
        $transport->close();
        return $result;
    }

    public function getFailedMessages($pid, $startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_failed_messages($pid, $startDate, $endDate);
        $transport->close();
        return $result;
    }

    public function getMessagesByDeliveryStatus(
        $pid,
        $startDate,
        $endDate,
        $smsTypes,
        $status
    ) {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_messages_by_delivery_status(
              $pid,
              $startDate,
              $endDate,
              $smsTypes,
              $status
          );
        $transport->close();
        return $result;
    }

    public function getMessagesByFailureReason(
        $pid,
        $startDate,
        $endDate,
        $smsTypes
    ) {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_messages_by_failure_reason(
              $pid,
              $startDate,
              $endDate,
              $smsTypes
          );
        $transport->close();
        return $result;
    }

    public function getMessagesByApplication($startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_messages_by_application($startDate, $endDate);
        $transport->close();
        return $result;
    }

    public function getMessagesByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_messages_by_app_for_pid($pid, $startDate, $endDate);
        $transport->close();
        return $result;
    }

    private function getTransport()
    {
        $socket = new Thrift\Transport\TSocket($this->smsServer, $this->smsPort);

        $socket->setRecvTimeout(20 * 1000);
        $socket->setSendTimeout(20 * 1000);

        $transport =
          new Thrift\Transport\TFramedTransport($socket, 1024, 1024);
        return $transport;
    }

    private function getClient($transport)
    {
        $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
        $client = new praccomm\DoctorSMSServiceClient($protocol);
        return $client;
    }

    private function getSMSType($type)
    {
        switch($type)
        {
            case "BULK":
                return praccomm\DoctorSMSType::BULK;
            case "CANCELLATION":
                return praccomm\DoctorSMSType::CANCELLATION;
            case "CONFIRMATION":
                return praccomm\DoctorSMSType::CONFIRMATION;
            case "CUSTOM":
                return praccomm\DoctorSMSType::CUSTOM;
            case "ONLINE_APPOINTMENT":
                return praccomm\DoctorSMSType::APPOINTMENT;
            case "REMINDER":
                return praccomm\DoctorSMSType::REMINDER;
            case "RESCHEDULE":
                return praccomm\DoctorSMSType::RESCHEDULE;
            case "SYSTEM":
                return praccomm\DoctorSMSType::SYSTEM;
            case "PVR_APPOINTMENT":
                return praccomm\DoctorSMSType::PVR_APPOINTMENT;
            case "PVR_CLINICDETAILS":
                return praccomm\DoctorSMSType::PVR_CLINICDETAILS;
            case "PVR_MISSED":
                return praccomm\DoctorSMSType::PVR_MISSED;
            case "PVR_EMERGENCY":
                return praccomm\DoctorSMSType::PVR_EMERGENCY;
            case "PVR_VOICEMAIL":
                return praccomm\DoctorSMSType::PVR_VOICEMAIL;
            case "PVR_CALL":
                return praccomm\DoctorSMSType::PVR_CALL;
            default:
                throw new \Exception("Invalid message type");
        }
    }
}
