<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Thrift;
require_once __DIR__.'/genphp/praccomm/PatientSMSService.php';

/**
 * Mail clinet for mailing doctors
 */
class SmsPatient
{
    private $_smsServer;
    private $_smsPort;
    private $_appName;
    private $_queue;

    public function __construct($queue, $serviceHost,
                                $servicePort, $appName)
    {
        $this->_smsServer = $serviceHost;
        $this->_smsPort = $servicePort;
        $this->_appName = Helper::getValidAppName($appName);
        $this->_queue = $queue;
    }

    public function sendSms($practiceId, $contactId, $name, $to, $type, $body,
                            $unverified = false, $smsDisabled = false,
                            $reduceCredit = false)
    {
        $this->validateSMSType($type);
        $job = new praccomm\PatientSms();
        $job->practice_id = $practiceId;
        $job->patient_contact_id = $contactId;
        $job->name = $name;
        $job->mobile = $to;
        $job->secondary_mobile = $secondaryMobile;
        $job->expiry_time = $expiryTime;
        $job->message_type = $type;
        $job->message_text = $body;
        $job->app_name = $this->_appName;
        $job->reduce_credit = $reduceCredit;
        $job->sms_type = 'patient';
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
        $job = new praccomm\PatientSms();
        $job->status = Helper::getSMSDeliveryStatus($status);
        $job->patient_sms_id = $key;
        $job->delivery_time = $deliveryTime;
        $job->operator = $operator;
        $job->operator_reply = $opReply;
        $job->operator_code = $opCode;
        $job->sms_type = 'patient';
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
        $result = $client->search_by_practice_id($pid, $sendDate, $smsTypes);
        $transport->close();
        return $result;
    }

    public function searchSmsByContactId($pid, $cid,
                                         $start = NULL, $end = NULL)
    {
        if(is_null($start) and !is_int($start))
        {
            $start_ts = mktime(0, 0, 0, 1, 2, 1970);
        }
        else
        {
            $start_ts = $start;
        }

        if(is_null($end) and !is_int($end))
        {
            $end_ts = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        }
        else
        {
            $end_ts = $end;
        }

        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_messages_by_contact_id($pid, $cid,
                                                      $start_ts, $end_ts);
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

    public function getMessagesByDeliveryStatus($pid, $startDate, $endDate,
                                                $smsTypes, $status)
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

    public function getMessagesByCidGroupedByApp($pid, $cid,
                                                 $startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_messages_by_app_for_cid($pid, $cid,
                                                       $startDate, $endDate);
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
        $client = new praccomm\PatientSMSServiceClient($protocol);
        return $client;
    }

    private function validateSMSType($type)
    {
        $allowedTypes = array(
            'BIRTHDAY',
            'BULK',
            'CANCELLATION',
            'CONFIRMATION',
            'CUSTOM',
            'FOLLOWUP',
            'ONLINE_APPOINTMENT',
            'REMINDER',
            'RESCHEDULE',
            'PRESCRIPTION',
            'SYSTEM',
            'PVR_APPOINTMENT',
            'PVR_CLINICDETAILS',
            'PVR_MISSED',
            'PVR_EMERGENCY',
            'PVR_VOICEMAIL',
            'PVR_CALL',
            'AMOUNT_DUE',
            'RX_REMINDER',
        );

        if (!in_array($type, $allowedTypes, true)) {
            throw new \Exception("Invalid message type");
        }
    }

}
?>