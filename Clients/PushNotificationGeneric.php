<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for sending push notifications
 */
class PushNotificationGeneric extends Base
{
    private $_appName;
    private $_queue;
    private $_apiSecretKey;

    public function __construct($queue, $apiSecretKey, $appName)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendPushNotification($reference_id, $reference_type, $notification_type,
                             $app_version, $account_id, $data)
    {
        $this->validateNotificationType(strtoupper($notification_type));
        $job = new praccomm\GenericPushNotification();

        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->notification_type = strtoupper($notification_type);
        $job->app_name = $this->_appName;
        if ($appName) {
            $job->app_name = Helper::getValidAppName($appName);
        }
        $job->app_version = $app_version;
        $job->initiated_at = date("Y-m-d H:i:s");
        $job->account_id = $account_id;
        $job->data = $data;

        $jsonifiedJob = json_encode($job);
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SEND_PUSH_NOTIFICATION_QUEUE)
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
            'DATA' => 'data'
            );

        $oneSignalAllowedKeys = array(
            'city',
            'app_version',
            'app_name',
            'last_active',
            'created_at',
            'account_id',
            'data',
            'service',
            'reference_type',
            'reference_id');

        $oneSignalApps = array(
            'fabric',
            'fabric-droid');

        $oneSignalRelations = array(
            '=',
            '>',
            '<',
            '>=',
            '<=');

        $jsonKeys = array(
            'app_version',
            'last_active'
        );

        $services = array(
            'fabric',
            'genesis',
            'pharma',
            'fit');
    }
}
