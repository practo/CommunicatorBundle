<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Exceptions\PushValidationException;

/**
 * Client for sending Patient Transactional Android Push notifications
 */
class PushAndroidPatientTransactional extends AbstractPush
{
    public function __construct($queue, $service)
    {
        parent::__construct($queue, $service);
        $this->comm_app_name = 'fabric-droid';
        $this->job = new praccomm\PatientTransAndroidPush();
        $this->job->service = $service;
    }

    public function sendPushNotification($pushDict)
    {
        $this->addHeaders($this->job, $pushDict);
        $this->addFilters($this->job, $pushDict);
        $this->addData($this->job, $pushDict);
        $jobData = (array) $this->job;
        $jobData = $this->validate($jobData);
        $jobData['app_name'] = $this->comm_app_name;
        $jsonifiedJob = json_encode($jobData);
        return $this->sendPush(Queue::SEND_PATIENT_TRANSACTIONAL_PUSH_QUEUE, $jsonifiedJob);
    }

    protected function validateFilters($pushDict)
    {
        $this->validatePushRelationKeys($pushDict);
        if ((!array_key_exists('account_id', $pushDict)) && (!array_key_exists('device_id', $pushDict)))
            throw new PushValidationException(
                "Account/Device ID is mandatory for transactional pushes"
            );
    }

    protected function validatePushType($pushType)
    {
        $allowedTypes = array(
            'PHR_RECORD_SHARED_NOTIF',
            'PHR_APPOINTMENT_RECORD_UPLOAD_NOTIF',
            'PHR_ADD_REMINDER_NOTIF',
            'ACCOUNTS_REMOTE_LOGOUT',
            'ORDER_STATUS_NOTIFICATION',
            'CONSULT_FOLLOWUP',
            'CONSULT_PAID_CHAT',
            'CONSULT_PAID_FEEDBACK',
            'CONSULT_QUESTION_REPLY',
            'CONSULT_QUESTION_FEEDBACK',
            'CONSULT_REFERRAL_CODE',
            'CONSULT_CHAT_PROMOTIONAL',
            'CONSULT_QUESTION_PROMOTIONAL',
            'CONSULT_FABRIC',
            'PRIVATE_THREAD',
            'PUBLIC_QUESTION',
            'USER_BLOCKED',
            'ANSWER_HELPFUL',
            'QUESTION_REJECTED',
            'QUESTION_ASSIGNED',
            'PRIVATE_QUESTION_ASSIGNED',
            'DOCTOR_BLOCK',
            'DOCTOR_VERIFIED',
            'DOCUMENT_SUBMISSION',
            'GROUP_NOTIFICATION',
            'PAID_CONSULTATION',
            'RAY_FOLLOW_UP',
            'APPOINTMENT_SHARE',
            'ONLINE_APPOINTMENT',
            'PAID_CONSULTATION_DOCTOR_REMINDER',
            'PAID_CONSULTATION_FEEDBACK',
            'PAID_CONSULTATION_CANCELLATION',
            'PAID_CONSULTATION_ANSWERED',
            'PAID_CONSULTATION_ASSIGNED',
            'PAID_CONSULTATION_NEW_CONVERSATION',
            'PAID_CONSULTATION_SETTLEMENT',
            'PAID_NEW_CONSULTATION_REQUEST',
            'RAY_FOLLOW_UP_CREATED',
            'RAY_FOLLOW_UP_COMPLETED',
            'RAY_FOLLOW_UP_DELETED',
            'RAY_FOLLOW_UP_EXPIRED',
            'RAY_FOLLOW_UP_CANCELLED',
            'FABRIC_MORNING_REMINDER',
            'RAY_FOLLOW_UP_ANSWERED',
            'RAY_FOLLOW_UP_ASSIGNED',
            'ONLINE_APPOINTMENT_CANCELLED',
            'PAID_CONSULTATION_PATIENT_REMINDER',
            'VIDEO_CALL',
            'VIDEO_CONSULT',
            'ABANDONED_CART_NOTIFICATION',
            'CUSTOM_PUSH_NOTIFICATION',
            'OUT_OF_STOCK_NOTIFICATION',
            'ORDER_STATUS_NOTIFICATION'
        );
        if (!in_array($pushType, $allowedTypes, true)) {
            throw new PushValidationException("Invalid message type");
        }
    }
}
