<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Clinet for sending generic mails
 */
class MailGeneric
{
    private $_mailServer;
    private $_mailPort;
    private $_appName;
    private $_queue;

    public function __construct($queue, $serviceHost, $servicePort, $appName)
    {
        $this->_mailServer = $serviceHost;
        $this->_mailPort = $servicePort;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendMail($genericId, $fromName,
                             $fromMail, $toName = "", $to, $cc = "",
                             $bcc = "", $subject, $body, $type, $files,
                             $hash, $plaintext = "", $replyTo = NULL)
    {
        $job = new praccomm\GenericMail();
        $job->generic_id = $genericId;
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
        $job->mail_type = 'generic';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateMail($hash, $status, $messageId,
                               $open_time, $open_bounce_data,
                               $unsubscribe_data, $spam_data)
    {
        $job = new praccomm\GenericMail();
        $job->hash = $hash;
        $job->delivery_status = getMailDeliveryStatus($status);
        $job->message_id = $messageId;
        $job->open_time = $open_time;
        $job->open_bounce_data = $open_bounce_data;
        $job->unsubscribe_data = $unsubscribe_data;
        $job->spam_data = $spam_data;
        $job->mail_type = 'generic';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    private function getTransport()
    {
        $socket = new Thrift\Transport\TSocket($this->_mailServer,
                                               $this->_mailPort);
        $socket->setRecvTimeout(20 * 1000);
        $socket->setSendTimeout(20 * 1000);
        $transport =
          new Thrift\Transport\TFramedTransport($socket, 1024, 1024);
        return $transport;
    }

    private function getClient($transport)
    {
        $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
        $client = new praccomm\GenericMailServiceClient($protocol);
        return $client;
    }

    private function getMailType($type)
    {
        switch($type)
        {
            case "INVITE":
              return \praccomm\GenericMailType::INVITE;
            case "NEW_EVENT":
              return \praccomm\GenericMailType::NEW_EVENT;
            case "EVENT_CANCELLATION":
              return \praccomm\GenericMailType::EVENT_CANCELLATION;
            case "EVENT_CONFIRMATION":
              return \praccomm\GenericMailType::EVENT_CONFIRMATION;
            case "EVENT_ATTENDANCE_CANCELLATION":
              return \praccomm\GenericMailType::EVENT_ATTENDANCE_CANCELLATION;
            case "EVENT_ATTENDANCE_CONFIRMATION":
              return \praccomm\GenericMailType::EVENT_ATTENDANCE_CONFIRMATION;
            default:
              throw new \Exception("Invalid mail type");
        }
    }
}
