<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Clients\Exceptions;

/**
 * Client for sending Patient Promotional Android Push notifications
 */
class PushAndroidPatientPromotional extends AbstractPush
{
    public function __construct($queue, $service)
    {
        parent::__construct();
        $this->comm_app_name = 'fabric-droid';
        $this->job = new praccomm\PatientPromoAndroidPush();
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
                    ->setQueueName(Queue::SEND_PATIENT_PROMOTIONAL_PUSH_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    private function validateFilters($pushDict)
    {
        $this->validatePushRelationKeys($pushDict);
    }

    private function validatePushType($pushType)
    {
        $allowedTypes = array();
        if (!in_array($pushType, $allowedTypes, true)) {
            throw new \PushValidationException("Invalid message type");
        }
    }
}
