<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Clinet for sending appointment mails
 */
class MailAppointment
{
    private $_communicatorBaseUri;
    private $_apiSecretKey;
    private $_appName;
    private $_queue;

    public function __construct($queue, $communicatorBaseUri, $apiSecretKey, $appName)
    {
        $this->_communicatorBaseUri = $communicatorBaseUri;
        $this->_apiSecretKey = $apiSecretKey;
        $this->_appName = Helper::getValidAppName($appName);
        $this->_queue = $queue;
    }

    public function sendMail($appointmentId, $fromName,
                             $fromMail, $toName = "", $to, $cc = "",
                             $bcc = "", $subject, $body, $type, $files,
                             $hash, $plaintext = "", $replyTo = NULL)
    {
        $job = new praccomm\AppointmentMail();
        $job->appointment_id = $appointmentId;
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
        $job->mail_type = 'appointment';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateMail($hash, $status, $messageId,
                               $open_time, $open_bounce_data,
                               $unsubscribe_data, $spam_data)
    {
        $job = new praccomm\AppointmentMail();
        $job->hash = $hash;
        $job->delivery_status = getMailDeliveryStatus($status);
        $job->message_id = $messageId;
        $job->open_time = $open_time;
        $job->open_bounce_data = $open_bounce_data;
        $job->unsubscribe_data = $unsubscribe_data;
        $job->spam_data = $spam_data;
        $job->mail_type = 'appointment';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::UPDATE_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function getMailsByAppointmentId($apptId, $mailTypes)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['appointment_id'] = $apptId;
        $params['mail_types'] = $mailTypes;
        $result = $client->get("/appointmentmail/get_mails_by_appointment_id", $params);
        return $result;
    }

    private function getMailType($type)
    {
        switch($type)
        {
            case "CONFIRMATION":
              return \praccomm\AppointmentMailType::CONFIRMATION;
            case "CANCELLATION":
              return \praccomm\AppointmentMailType::CANCELLATION;
            case "VERIFICATION":
              return \praccomm\AppointmentMailType::VERIFICATION;
            case "REQUEST":
              return \praccomm\AppointmentMailType::REQUEST;
            case "TEST":
              return \praccomm\AppointmentMailType::TEST;
            case "RESCHEDULE":
              return \praccomm\AppointmentMailType::RESCHEDULE;
            case "PASSED_ON":
              return \praccomm\AppointmentMailType::PASSED_ON;
            case "CUSTOM":
              return \praccomm\AppointmentMailType::CUSTOM;
            case "WALK_IN":
              return \praccomm\AppointmentMailType::WALK_IN;
            case "PENDING":
              return \praccomm\AppointmentMailType::PENDING;
            default:
              throw new \Exception("Invalid mail type");
        }
    }
}