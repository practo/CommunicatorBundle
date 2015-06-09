<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Mail clinet for mailing doctors
 */
class SmsStaff
{
    private $_smsServer;
    private $_smsPort;
    private $_appName;
    private $_queue;

    public function __construct($queue, $serviceHost, $servicePort, $appName)
    {
        $this->_smsServer = $serviceHost;
        $this->_smsPort = $servicePort;
        $this->_appName = Helper::getValidAppName($appName);
        $this->_queue = $queue;
    }

    public function sendSms($practice_id, $name, $to, $type, $body,
                            $unverified = false, $smsDisabled = false,
                            $reduceCredit = false)
    {
        $job = new praccomm\StaffSms();
        $job->practice_id = $practice_id;
        $job->name = $name;
        $job->mobile = $to;
        $job->secondary_mobile = $secondaryMobile;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->message_type = $this->getSMSType($type);
        $job->app_name = $this->_appName;
        $job->reduce_credit = $reduceCredit;
        $job->sms_type = 'staff';
        if($unverified)
        {
            $job->status = 'FAILED';
            $job->failure_reason = 'MOBILEVERIFICATION';
        }
        if($smsDisabled)
        {
            $job->status = 'FAILED';
            $job->failure_reason = 'SMSDISABLED';
        }
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateSms($key, $status, $deliveryTime,
                              $operator, $opReply, $opCode)
    {
        $job = new praccomm\StaffSms();
        $job->status = Helper::getSMSDeliveryStatus($status);
        $job->staff_sms_id = $key;
        $job->delivery_time = $deliveryTime;
        $job->operator = $operator;
        $job->operator_reply = $opReply;
        $job->operator_code = $opCode;
        $job->sms_type = 'staff';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::UPDATE_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function searchSmsByPracticeId($pid, $sendDate, $smsTypes)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->search_by_practice_id($pid, $sendDate);
        $transport->close();
        return $result;
    }

    public function getMessagesByDateRange($pid, $startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_messages_in_date_range($pid, $startDate, $endDate);
        $transport->close();
        return $result;
    }

    public function getMessagesByDeliveryStatus($pid, $startDate,
                                                $endDate, $smsTypes, $status)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_messages_by_delivery_status($pid, $startDate, $endDate,
                                                   $smsTypes, $status);
        $transport->close();
        return $result;
    }

    public function getMessagesByFailureReason($pid, $startDate,
                                               $endDate, $smsTypes)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_messages_by_failure_reason($pid, $startDate,
                                                  $endDate, $smsTypes);
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
        $client = new praccomm\StaffSMSServiceClient($protocol);
        return $client;
    }

    private function getSMSType($type)
    {
        switch($type)
        {
            case "BULK":
              return praccomm\StaffSMSType::BULK;
            case "CUSTOM":
              return praccomm\StaffSMSType::CUSTOM;
            case "ONLINE_APPOINTMENT":
              return praccomm\StaffSMSType::ONLINE_APPOINTMENT;
            case "REMINDER":
              return praccomm\StaffSMSType::REMINDER;
            case "SYSTEM":
              return praccomm\StaffSMSType::SYSTEM;
            case "PVR_APPOINTMENT":
              return praccomm\StaffSMSType::PVR_APPOINTMENT;
            case "PVR_CLINICDETAILS":
              return praccomm\StaffSMSType::PVR_CLINICDETAILS;
            case "PVR_MISSED":
              return praccomm\StaffSMSType::PVR_MISSED;
            case "PVR_EMERGENCY":
              return praccomm\StaffSMSType::PVR_EMERGENCY;
            case "PVR_VOICEMAIL":
              return praccomm\StaffSMSType::PVR_VOICEMAIL;
            case "PVR_CALL":
              return praccomm\StaffSMSType::PVR_CALL;
            case "LAB_ORDERS":
              return praccomm\StaffSMSType::LAB_ORDERS;
            default:
              throw new \Exception("Invalid message type");
        }
    }
}
?>
