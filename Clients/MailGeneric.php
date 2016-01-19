<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for sending generic mails
 */
class MailGeneric
{
    private $_appName;
    private $_queue;
    private $_communicatorBaseUri;
    private $_apiSecretKey;

    public function __construct($queue, $communicatorBaseUri, $apiSecretKey, $appName)
    {
        $this->_communicatorBaseUri = $communicatorBaseUri;
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendMail($fromName, $fromMail, $toName = "", $to,
                             $cc = "", $bcc = "", $subject, $body, $type,
                             $files, $hash, $plaintext = "", $replyTo = NULL,
                             $reference_id = NULL, $reference_type = NULL)
    {
        $this->validateMailType(strtoupper($type));
        $job = new praccomm\GenericMail();
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
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->app_name = $this->_appName;
        $job->email_type = strtoupper($type);
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

    private function validateMailType($type)
    {
        $allowedTypes = array(
            'FABRIC_ROI_REPORT',
            'FABRIC_RENEWAL_ALERT',
            'MERGE_ACCOUNTS_NOTIFICATION',
            'VN_DETAILS',
            'ALERT_BAD_HEALTH',
            'FABRIC_DQS_REPORT',
            'FIT_GENERIC_MAIL',
            'CONSULT_GENERIC_MAIL',
            'REACH_TOS_MAIL',
            'REACH_QUALITY_REMINDER',
            'QIKWELL_APPOINTMENT_REMINDER_MAIL',
            'FEEDBACK_DAILY_HEALTH_EMAIL',
            'FEEDBACK_SURVEY_EMAIL',
        );

        if (!in_array($type, $allowedTypes, true)) {
            throw new \Exception("Invalid message type");
        }
    }

    /**
     * Returns count of mails grouped by their delivery status
     * Filtered on: date, mail type
     *
     * @param string $mailType       - Type of the mail
     * @param string $startDate      - Start Date
     * @param string $endDate        - End Date
     * @param array  $deliveryStatus - Array of delivery status
     *
     * @return array - Array of Mail counts grouped by delivery status
     */
    public function getMailCountByType($mailType, $startDate, $endDate, $deliveryStatus)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = [
            'mail_type'       => $mailType,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'delivery_status' => $deliveryStatus
        ];
        $result = $client->get("/genericmail/get_mail_count_for_type", $params);
        return $result;
    }
}
