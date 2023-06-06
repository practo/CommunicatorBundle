<?php

namespace Practo\CommunicatorBundle\Clients;

use praccomm;
use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;

/**
 * Mail clinet for mailing doctors.
 */
class SmsDoctor extends Base
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

    public function sendSms(
        $practiceId,
        $doctorProfileId,
        $name,
        $to,
        $type,
        $body,
        $templateName,
        $unverified = false,
        $smsDisabled = false,
        $expiryTime = null,
        $secondaryMobile = null
    ) {
        $job = new praccomm\DoctorSms();
        $job->practice_id = $practiceId;
        $job->doctor_profile_id = $doctorProfileId;
        $job->name = $name;
        $job->mobile = $to;
        $job->secondary_mobile = $secondaryMobile;
        $job->templateName = $templateName;
        $job->template_name = $templateName;
        $job->expiry_time = $expiryTime;
        $job->message_type = $this->getSMSType($type);
        $job->message_text = $body;
        $job->app_name = $this->_appName;
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
        $this->_queue->setDomain($this->_domain);

        return $this->_queue
            ->setQueueName(Queue::SEND_SMS_QUEUE)
            ->sendMessage($jsonifiedJob)
        ;
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
        $this->_queue->setDomain($this->_domain);

        return $this->_queue
            ->setQueueName(Queue::UPDATE_SMS_QUEUE)
            ->sendMessage($jsonifiedJob)
        ;
    }

    public function searchSmsByPracticeId($pid, $sendDate, $smsTypes)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $pid;
        $params['send_date'] = $sendDate;
        $params['sms_types'] = $smsTypes;

        return $client->get('/doctorsms/search_by_practice_id', $params);
    }

    public function getFailedMessages($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;

        return $client->get('/doctorsms/get_failed_messages', $params);
    }

    public function getMessagesByDeliveryStatus(
        $pid,
        $startDate,
        $endDate,
        $smsTypes,
        $status
    ) {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $params['sms_types'] = $smsTypes;
        $params['status'] = $status;

        return $client->get('/doctorsms/get_messages_by_delivery_status', $params);
    }

    public function getMessagesByFailureReason(
        $pid,
        $startDate,
        $endDate,
        $smsTypes
    ) {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $params['sms_types'] = $smsTypes;

        return $client->get('/doctorsms/get_messages_by_failure_reason', $params);
    }

    public function getMessagesByApplication($startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;

        return $client->get('/doctorsms/get_messages_by_application', $params);
    }

    public function getMessagesByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;

        return $client->get('/doctorsms/get_messages_by_app_for_pid', $params);
    }

    public function getRetriedMessagesForId($smsId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['sms_id'] = $smsId;

        return $client->get('/doctorsms/get_retried_messages_for_smsid', $params);
    }

    public function getMessagesByDateRange($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;

        return $client->get('/doctorsms/get_messages_in_date_range', $params);
    }

    private function getSMSType($type)
    {
        switch ($type) {
            case 'BULK':
                return praccomm\DoctorSMSType::BULK;

            case 'CANCELLATION':
                return praccomm\DoctorSMSType::CANCELLATION;

            case 'CONFIRMATION':
                return praccomm\DoctorSMSType::CONFIRMATION;

            case 'CUSTOM':
                return praccomm\DoctorSMSType::CUSTOM;

            case 'ONLINE_APPOINTMENT':
                return praccomm\DoctorSMSType::APPOINTMENT;

            case 'REMINDER':
                return praccomm\DoctorSMSType::REMINDER;

            case 'RESCHEDULE':
                return praccomm\DoctorSMSType::RESCHEDULE;

            case 'SYSTEM':
                return praccomm\DoctorSMSType::SYSTEM;

            case 'PVR_APPOINTMENT':
                return praccomm\DoctorSMSType::PVR_APPOINTMENT;

            case 'PVR_CLINICDETAILS':
                return praccomm\DoctorSMSType::PVR_CLINICDETAILS;

            case 'PVR_MISSED':
                return praccomm\DoctorSMSType::PVR_MISSED;

            case 'PVR_EMERGENCY':
                return praccomm\DoctorSMSType::PVR_EMERGENCY;

            case 'PVR_VOICEMAIL':
                return praccomm\DoctorSMSType::PVR_VOICEMAIL;

            case 'PVR_CALL':
                return praccomm\DoctorSMSType::PVR_CALL;

            case 'BLOCKCALENDAR':
                return praccomm\DoctorSMSType::BLOCKCALENDAR;

            case 'QUOTA_USAGE':
                return praccomm\DoctorSMSType::QUOTA_USAGE;

            default:
                throw new \InvalidArgumentException('Invalid message type');
        }
    }
}
