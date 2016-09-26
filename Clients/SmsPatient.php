<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Mail clinet for mailing doctors
 */
class SmsPatient extends Base
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

    public function sendSms($practiceId, $contactId, $name, $to, $type, $body,
                            $unverified = false, $smsDisabled = false,
                            $dndFail = false, $expiryTime = null,
                            $secondaryMobile = null)
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
        $job->sms_type = 'patient';
        if($unverified)
        {
            $job->status = 'FAILED';
            $job->failure_reason = 'MOBILEVERIFICATION';
        }
        if($smsDisabled)
        {
            $job->status = 'FAILED';
            if ($dndFail) {
                $job->failure_reason = 'DND_FAIL';
            } else {
                $job->failure_reason = 'SMSDISABLED';
            }
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
        $job = new praccomm\PatientSms();
        $job->status = Helper::getSMSDeliveryStatus($status);
        $job->patient_sms_id = $key;
        $job->delivery_time = $deliveryTime;
        $job->operator = $operator;
        $job->operator_reply = $opReply;
        $job->operator_code = $opCode;
        $job->sms_type = 'patient';
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
        $result = $client->get("/patientsms/search_by_practice_id", $params);
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

        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['cid'] = $cid;
        $params['start_date'] = $start_ts;
        $params['end_date'] = $end_ts;
        $result = $client->get("/patientsms/get_messages_by_contact_id", $params);
        return $result;
    }

    public function getMessagesByDateRange($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientsms/get_messages_in_date_range", $params);
        return $result;
    }

    public function getMessagesByDeliveryStatus($pid, $startDate, $endDate,
                                                $smsTypes, $status)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $params['sms_types'] = $smsTypes;
        $params['status'] = $status;
        $result = $client->get("/patientsms/get_messages_by_delivery_status", $params);
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
        $result = $client->get("/patientsms/get_messages_by_failure_reason", $params);
        return $result;
    }

    public function getMessagesByApplication($startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientsms/get_messages_by_application", $params);
        return $result;
    }

    public function getMessagesByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientsms/get_messages_by_app_for_pid", $params);
        return $result;
    }

    public function getMessagesByCidGroupedByApp($pid, $cid,
                                                 $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['cid'] = $cid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientsms/get_messages_by_app_for_cid", $params);
        return $result;
    }

    public function getRetriedMessagesForId($smsId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['sms_id'] = $smsId;
        $result = $client->get("/patientsms/get_retried_messages_for_smsid", $params);
        return $result;
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
            'PATIENT_MEMBERSHIP',
            'PAYMENT',
            'PATIENT_LAB_ORDERS',
            'ANNIVERSARY',
            'LENS_REPLACEMENT_REMINDER',
            'PATIENT_SMS_BLACKLISTED'
        );

        if (!in_array($type, $allowedTypes, true)) {
            throw new Exception("Invalid message type");
        }
    }

    /**
     * Returns the last SMS sent to a patient.
     * Filters using mobile number and message type.
     *
     * @param string $mobile      - Mobile number of the patient
     * @param string $messageType - e.g. CONFIRMATION
     *
     * @return array
     */
    public function getLastMessageByMobile($mobile, $messageType)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['patient_mobile'] = $mobile;
        $params['message_type'] = $messageType;
        $params['app_name'] = $this->_appName;
        $result = $client->get('/patientsms/get_last_message_for_mobile', $params);

        return $result;
    }
}
?>
