<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for sending push notifications
 */
class PushNotificationGenericTransactional extends Base
{
    private $_service;
    private $_queue;
    private $_apiSecretKey;

    public function __construct($queue, $apiSecretKey, $service)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_service = Helper::getValidAppName($service);
    }

    public function sendPushNotification($reference_id, $reference_type, $notification_type,
                            $account_id, $data, $clientAppName)
    {
        $this->validateNotificationType(strtoupper($notification_type));
        $job = new praccomm\GenericTransactionalPushNotification();

        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->notification_type = strtoupper($notification_type);
        $job->service = $this->_service;
        $job->app_name = $clientAppName;
        $job->initiated_at = date("Y-m-d H:i:s");
        $job->account_id = $account_id;
        $job->data = $data;

        $jsonifiedJob = json_encode($job);
        $this->validatePayload(json_decode($jsonifiedJob, true));
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SEND_GENERIC_TRANSACTIONAL_PUSH_NOTIFICATION_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    private function validateNotificationType($type)
    {
        $allowedTypes = array(
            'ORDER_NOTIFICATION'
        );

        if (!in_array($type, $allowedTypes, true)) {
            throw new \Exception("Invalid message type");
        }
    }

    private function validatePayload($payload)
    {
        $pushKeys = array(
            'CITY' => 'city',
            'APP_VERSION'=> 'app_version',
            'APP_NAME' => 'app_name',
            'OS_NAME' => 'os_name',
            'OS_VERSION' => 'os_version',
            'LAST_ACTIVE' => 'last_active',
            'CREATED_AT' => 'created_at',
            'ACCOUNT_ID' => 'account_id',
            'VALUE' => 'value',
            'RELATION' => 'relation',
            'ONE_SIGNAL_ID' =>'one_signal_id',
            'DELETED_AT' => 'deleted_at',
            'REFERENCE_TYPE' => 'reference_type',
            'REFERENCE_ID' => 'reference_id',
            'SENT_AT' => 'sent_at',
            'RECEIVED_AT' => 'received_at',
            'SERVICE' => 'service',
            'DEVICE_ID' => 'device_id',
            'DATA' => 'data',
            'NOTIFICATION_TYPE' => 'notification_type'
            );

        if (array_key_exists($pushKeys['REFERENCE_ID'], $payload) &&
            !array_key_exists($pushKeys['REFERENCE_TYPE'], $payload)) {
            throw new \Exception("reference_type is missing, but reference_id is present");
        }
        if (empty($payload[$pushKeys['ACCOUNT_ID']])) {
            throw new \Exception("account_id is missing");
        }
    }
}
