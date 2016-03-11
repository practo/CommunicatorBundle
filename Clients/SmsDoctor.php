<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;


/**
 * Mail clinet for mailing doctors
 */
class SmsDoctor
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
        $this->_appName = Helper::getValidAppName($appName);
        $this->_queue = $queue;
    }

    public function sendSms($practiceId, $doctorProfileId, $name, $to, $type,
                            $body, $unverified = false, $smsDisabled = false,
                            $expiryTime = null, $secondaryMobile = null)
    {
        $job = new praccomm\DoctorSms();
        $job->practice_id = $practiceId;
        $job->doctor_profile_id = $doctorProfileId;
        $job->name = $name;
        $job->mobile = $to;
        $job->secondary_mobile = $secondaryMobile;
        $job->expiry_time = $expiryTime;
        $job->message_type = $this->getSMSType($type);
        $job->message_text = $body;
        $job->app_name = $this->_appName;
        $job->sms_type = 'doctor';
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
        $job = new praccomm\DoctorSms();
        $job->status = Helper::getSMSDeliveryStatus($status);
        $job->doctor_sms_id = $key;
        $job->delivery_time = $deliveryTime;
        $job->operator = $operator;
        $job->operator_reply = $opReply;
        $job->operator_code = $opCode;
        $job->sms_type = 'doctor';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::UPDATE_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }


    public function searchSmsByPracticeId($pid, $sendDate, $smsTypes)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['send_date'] = $sendDate;
        $params['sms_types'] = $smsTypes;
        $result = $client->get("/doctorsms/search_by_practice_id", $params);
        return $result;
    }

    public function getFailedMessages($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/doctorsms/get_failed_messages", $params);
        return $result;
    }

    public function getMessagesByDeliveryStatus($pid, $startDate,
                                                $endDate, $smsTypes, $status)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $params['sms_types'] = $smsTypes;
        $params['status'] = $status;
        $result = $client->get("/doctorsms/get_messages_by_delivery_status", $params);
        return $result;
    }

    public function getMessagesByFailureReason($pid, $startDate,
                                               $endDate, $smsTypes)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $params['sms_types'] = $smsTypes;
        $result = $client->get("/doctorsms/get_messages_by_failure_reason", $params);
        return $result;
    }

    public function getMessagesByApplication($startDate, $endDate)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/doctorsms/get_messages_by_application", $params);
        return $result;
    }

    public function getMessagesByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/doctorsms/get_messages_by_app_for_pid", $params);
        return $result;
    }

    public function getRetriedMessagesForId($smsId)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['sms_id'] = $smsId;
        $result = $client->get("/doctorsms/get_retried_messages_for_smsid", $params);
        return $result;
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
?>
