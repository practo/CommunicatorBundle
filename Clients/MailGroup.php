<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Clinet for sending group mails
 */
class MailGroup
{
    private $_communicatorBaseUri;
    private $_apiSecretKey;
    private $_appName;
    private $_queue;

    private function __construct($queue, $communicatorBaseUri, $apiSecretKey, $appName)
    {
        $this->_communicatorBaseUri = $communicatorBaseUri;
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendMail($groupId, $eventId, $fromName,
                             $fromMail, $toName = "", $to, $cc = "",
                             $bcc = "", $subject, $body, $type, $files,
                             $hash, $plaintext = "", $replyTo = NULL)
    {
        $job = new praccomm\GroupMail();
        $job->group_id = $groupId;
        $job->event_id = $eventId;
        $job->from_name = $fromName;
        $job->from_address = $fromMail;
        $job->to_name = $toName;
        $job->to_address = $to;
        if(is_string($cc) && !empty($cc))
        {
            $job->cc = array($cc);
        }
        else
        {
            $job->cc = $cc;
        }
        if(is_string($bcc) && !empty($bcc))
        {
            $job->bcc = array($bcc);
        }
        else
        {
            $job->bcc = $bcc;
        }
        $job->subject = $subject;
        $job->body = $body;
        $job->plaintext = $plaintext;
        $job->app_name = $this->_appName;
        $job->date = time();
        $job->email_type = $this->getMailType($type);
        $job->reply_to = $replyTo;
        $i = 0;
        $attachments = array();
        if ($files)
        {
            foreach($files as $fname=>$loc)
            {
                $att = new praccomm\Attachment();
                $att->name = $fname;
                $att->location = $loc;
                $attachments[$i] = $att;
                $i = $i + 1;
            }
        }
        $job->attachments = $attachments;
        $job->hash = $hash;
        $job->mail_type = 'group';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateMail($hash, $status, $messageId,
                               $open_time, $open_bounce_data,
                               $unsubscribe_data, $spam_data)
    {
        $job = new praccomm\GroupMail();
        $job->hash = $hash;
        $job->delivery_status = getMailDeliveryStatus($status);
        $job->message_id = $messageId;
        $job->open_time = $open_time;
        $job->open_bounce_data = $open_bounce_data;
        $job->unsubscribe_data = $unsubscribe_data;
        $job->spam_data = $spam_data;
        $job->mail_type = 'group';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::UPDATE_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function getMailsByGroupId($groupId, $mailTypes)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['group_id'] = $groupId;
        $params['mail_types'] = $mailTypes;
        $result = $client->get("/groupmail/get_mails_by_group_id", $params);
        return $result;
    }

    public function getMailsByEventId($groupId, $eventId, $mailTypes)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['group_id'] = $groupId;
        $params['event_id'] = $eventId;
        $params['mail_types'] = $mailTypes;
        $result = $client->get("/groupmail/get_mails_by_event_id", $params);
        return $result;
    }

    private function getMailType($type)
    {
        switch($type)
        {
            case "INVITE":
              return \praccomm\GroupMailType::INVITE;
            case "NEW_EVENT":
              return \praccomm\GroupMailType::NEW_EVENT;
            case "EVENT_CANCELLATION":
              return \praccomm\GroupMailType::EVENT_CANCELLATION;
            case "EVENT_CONFIRMATION":
              return \praccomm\GroupMailType::EVENT_CONFIRMATION;
            case "EVENT_ATTENDANCE_CANCELLATION":
              return \praccomm\GroupMailType::EVENT_ATTENDANCE_CANCELLATION;
            case "EVENT_ATTENDANCE_CONFIRMATION":
              return \praccomm\GroupMailType::EVENT_ATTENDANCE_CONFIRMATION;
            default:
              throw new \Exception("Invalid mail type");
        }
    }
}