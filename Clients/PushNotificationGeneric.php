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

    public function sendPushNotification($fromName, $fromMail, $toName = "", $to,
                             $cc = "", $bcc = "", $subject, $body, $type,
                             $files, $hash, $plaintext = "", $replyTo = NULL,
                             $reference_id = NULL, $reference_type = NULL,
                             $expiryTime = NULL, $appName = null, $createdBy = NULL)
    {
        $this->validateNotificationType(strtoupper($type));
        $job = new praccomm\GenericPushNotification();

        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->app_name = $this->_appName;
        if ($appName) {
            $job->app_name = Helper::getValidAppName($appName);
        }
        
        $job->date = time();
        
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
}
