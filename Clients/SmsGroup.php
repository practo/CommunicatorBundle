<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Clinet for messaging doctors
 */
class SmsGroup extends Base
{
    private $_apiSecretKey;
    private $_appName;
    private $_queue;

    private function __construct($queue, $apiSecretKey, $appName)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendSms(
        $groupId,
        $name,
        $to,
        $messageType,
        $body,
        $eventId,
        $expiryTime = null
    ) {

        $job = new praccomm\GroupSms();
        $job->group_id = $groupId;
        $job->name = $name;
        $job->mobile = $to;
        $job->expiry_time = $expiryTime;
        $job->event_id = $eventId;
        $job->message_text = $body;
        $job->message_type = $this->getSMSType($messageType);
        $job->app_name = $this->_appName;
        $jsonifiedJob = json_encode($job);
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SEND_GROUP_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function getMessagesByGroupId($groupId, $smsTypes)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['group_id'] = $groupId;
        $params['sms_types'] = $smsTypes;
        $result = $client->get("/groupsms/get_messages_by_group_id", $params);
        return $result;
    }

    private function getSMSType($type)
    {
        switch ($type) {
            case "VERIFICATION":
                return praccomm\GroupSMSType::INVITE;
            case "REQUEST":
                return praccomm\GroupSMSType::NEW_EVENT;
            case "CONFIRMATION":
                return praccomm\GroupSMSType::EVENT_CANCELLATION;
            case "CANCELLATION":
                return praccomm\GroupSMSType::EVENT_CONFIRMATION;
            default:
                throw new \Exception("Invalid message type");
        }
    }
}
