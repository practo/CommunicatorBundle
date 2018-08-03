<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;
use Practo\CommunicatorBundle\Exceptions\PushValidationException;

/**
 * Client for sending Patient Promotional Android Push notifications
 */
class PushAndroidPatientPromotional extends AbstractPush
{
    public function __construct($queue, $service)
    {
        parent::__construct($queue, $service);
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
        $jobData['app_name'] = $this->comm_app_name;
        $jsonifiedJob = json_encode($jobData);
        return $this->sendPush(Queue::SEND_PATIENT_PROMOTIONAL_PUSH_QUEUE, $jsonifiedJob);
    }

    protected function validateFilters($pushDict)
    {
        $this->validatePushRelationKeys($pushDict);
    }

    protected function validatePushType($pushType)
    {
        $allowedTypes = array(
            'ORDER_NOTIFICATION',
            'CONSULT_CHAT_PROMOTIONAL',
            'CONSULT_QUESTION_PROMOTIONAL'
        );
        if (!in_array($pushType, $allowedTypes, true)) {
            throw new PushValidationException("Invalid message type");
        }
    }
}
