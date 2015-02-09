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
    private $appName;
    private $queue;

    public function __construct($queue, $appName)
    {
        $this->queue = $queue;
        $this->appName = Helper::getValidAppName($appName);
    }

    public function sendMail(
        $fromName,
        $fromMail,
        $toName,
        $to,
        $cc,
        $bcc,
        $subject,
        $body,
        $type,
        $files,
        $hash,
        $plaintext = "",
        $replyTo = null
    ) {
        $job = new praccomm\GenericMail();
        $job->from_name = $fromName;
        $job->from_address = $fromMail;
        $job->to_name = $toName;
        $job->to_address = $to;
        if (is_string($cc) && !empty($cc)) {
            $job->cc = array($cc);
        } else {
            $job->cc = $cc;
        }
        if (is_string($bcc) && !empty($bcc)) {
            $job->bcc = array($bcc);
        } else {
            $job->bcc = $bcc;
        }
        $job->subject = $subject;
        $job->body = $body;
        $job->plaintext = $plaintext;
        $job->app_name = $this->appName;
        $job->email_type = $this->getMailType($type);
        $job->date = time();
        $job->reply_to = $replyTo;
        $i = 0;
        $attachments = array();
        if ($files) {
            foreach ($files as $fname => $loc) {
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
        return $this->queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateMail(
        $hash,
        $status,
        $messageId,
        $open_time,
        $open_bounce_data,
        $unsubscribe_data,
        $spam_data
    ) {
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
        return $this->queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
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
            case "FABRIC_ROI_REPORT":
                return \praccomm\GenericMailType::FABRIC_ROI_REPORT;
            case "MERGE_ACCOUNTS_NOTIFICATION":
                return \praccomm\GenericMailType::MERGE_ACCOUNTS_NOTIFICATION;
            case "FABRIC_RENEWAL_ALERT":
                return \praccomm\GenericMailType::FABRIC_RENEWAL_ALERT;
            default:
                throw new \Exception("Invalid mail type");
        }
    }
}
