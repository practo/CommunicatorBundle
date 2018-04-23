<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Exceptions\PushValidationException;

/**
 * Client for sending Patient Transactional Ios Push notifications
 */
class PushIosPatientTransactional extends AbstractPush
{
    public function __construct($queue, $service)
    {
        parent::__construct($queue, $service);
        $this->comm_app_name = 'fabric-ios';
        $this->job = new praccomm\PatientTransIosPush();
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
            'ORDER_STATUS_NOTIFICATION'
        );
        if (!in_array($pushType, $allowedTypes, true)) {
            throw new PushValidationException("Invalid message type");
        }
    }
}
