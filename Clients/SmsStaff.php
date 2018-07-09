<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Mail clinet for mailing doctors
 */
class SmsStaff extends Base
{
    private $_apiSecretKey;
    private $_appName;
    private $_queue;

    public function __construct($queue, $apiSecretKey, $appName)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_appName = Helper::getValidAppName($appName);
        $this->_queue = $queue;
    }

    public function sendSms($practice_id, $staffProfileId, $name, $to, $type, $body,
                            $unverified = false, $smsDisabled = false,
                            $expiryTime = null, $secondaryMobile = null)
    {
        $job = new praccomm\StaffSms();
        $job->practice_id = $practice_id;
        $job->staff_profile_id = $staffProfileId;
        $job->name = $name;
        $job->mobile = $to;
        $job->secondary_mobile = $secondaryMobile;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->message_type = $this->getSMSType($type);
        $job->app_name = $this->_appName;
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
        $this->_queue->setDomain($this->_domain);
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
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::UPDATE_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function searchSmsByPracticeId($pid, $sendDate, $smsTypes)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['send_date'] = $sendDate;
        $params['sms_types'] = $smsTypes;
        $result = $client->get("/staffsms/search_by_practice_id", $params);
        return $result;
    }

    public function getMessagesByDateRange($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/staffsms/get_messages_in_date_range", $params);
        return $result;
    }

    public function getMessagesByDeliveryStatus($pid, $startDate,
                                                $endDate, $smsTypes, $status)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $params['sms_types'] = $smsTypes;
        $params['status'] = $status;
        $result = $client->get("/staffsms/get_messages_by_delivery_status", $params);
        return $result;
    }

    public function getMessagesByFailureReason($pid, $startDate,
                                               $endDate, $smsTypes)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $params['sms_types'] = $smsTypes;
        $result = $client->get("/staffsms/get_messages_by_failure_reason", $params);
        return $result;
    }

    public function getMessagesByApplication($startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/staffsms/get_messages_by_application", $params);
        return $result;
    }

    public function getMessagesByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/staffsms/get_messages_by_app_for_pid", $params);
        return $result;
    }

    public function getRetriedMessagesForId($smsId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['sms_id'] = $smsId;
        $result = $client->get("/staffsms/get_retried_messages_for_smsid", $params);
        return $result;
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
            case "TOTP_ENABLED":
              return praccomm\StaffSMSType::TOTP_ENABLED;
            case "LAB_ORDERS":
              return praccomm\StaffSMSType::LAB_ORDERS;
            case "SAVE_AND_ORDER":
              return praccomm\StaffSMSType::SAVE_AND_ORDER;
            case 'PAYMENT':
                return praccomm\StaffSMSType::PAYMENT;
            case 'VERSION_UPGRADES':
                return praccomm\StaffSMSType::VERSION_UPGRADES;
            case 'CONNECT_LOW_CREDIT':
                return praccomm\StaffSMSType::CONNECT_LOW_CREDIT;
            default:
              throw new \Exception("Invalid message type");
        }
    }
}
?>
