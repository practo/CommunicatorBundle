<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Clients\Exceptions;

/**
 * Client for sending Patient Transactional Android Push notifications
 */
class PushAndroidPatientTransactional extends AbstractPush
{
    public function __construct($queue, $service)
    {
        parent::__construct();
        $this->comm_app_name = 'fabric-android';
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
        $jsonifiedJob = json_encode($jobData);
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SEND_PATIENT_TRANSACTIONAL_PUSH_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    private function validateFilters($pushDict)
    {
        $this->validatePushRelationKeys($pushDict);
        if ((!array_key_exists('account_id', $inputDict)) && (!array_key_exists('device_id', $inputDict)))
            throw new \PushValidationException(
                "Account/Device ID is mandatory for transactional pushes"
            );
    }

    private function validatePushType($pushType)
    {
        $allowedTypes = array(
            'PHR_RECORD_SHARED_NOTIF',
            'PHR_APPOINTMENT_RECORD_UPLOAD_NOTIF',
            'PHR_ADD_REMINDER_NOTIF',
            'ACCOUNTS_REMOTE_LOGOUT',
            'ORDER_NOTIFICATION'
        );
        if (!in_array($pushType, $allowedTypes, true)) {
            throw new \PushValidationException("Invalid message type");
        }
    }
}
