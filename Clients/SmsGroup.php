<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Clinet for messaging doctors
 */
class SmsGroup
{
    private $_smsServer;
    private $_smsPort;
    private $_appName;
    private $_queue;

    private function __construct($queue, $serviceHost, $servicePort, $appName)
    {
        $this->_smsServer = $serviceHost;
        $this->_smsPort = $servicePort;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendSms($groupId, $name, $to, $messageType,
                            $body, $eventId, $expiryTime = null)
    {
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
        return $this->_queue
                    ->setQueueName(Queue::SEND_GROUP_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function getMessagesByGroupId($groupId, $smsTypes)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_messages_by_group_id($groupid, $smsTypes);
        $transport->close();
        return $result;
    }

    private function getTransport()
    {
        $socket = new Thrift\Transport\TSocket($this->_smsServer,
                                               $this->_smsPort);
        $socket->setRecvTimeout(20 * 1000);
        $socket->setSendTimeout(20 * 1000);
        $transport =
          new Thrift\Transport\TFramedTransport($socket, 1024, 1024);
        return $transport;
    }

    private function getClient($transport)
    {
        $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
        $client = new praccomm\GroupSMSService($protocol);
        return $client;
    }

    private function getSMSType($type)
    {
        switch($type)
        {
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