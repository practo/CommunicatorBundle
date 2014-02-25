<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;

/**
 * Mail clinet for mailing doctors
 */
class MailDoctor
{
    private $_mailServer;
    private $_mailPort;
    private $_appName;
    private $_queue;

    public function __construct($queue, $serviceHost,
                                $servicePort, $appName)
    {
        $this->_mailServer = $serviceHost;
        $this->_mailPort = $servicePort;
        $this->_appName = Helper::getValidAppName($appName);
        $this->_queue = $queue;
    }

    public function sendMail($practice_id, $doctorProfileId, $from_name,
                             $from_mail, $to_name = "", $to, $cc = "",
                             $bcc = "", $subject, $body, $type, $files,
                             $hash, $plaintext = "", $replyTo=null)
    {
        $job = new praccomm\DoctorMail();
        $job->practice_id = $practice_id;
        $job->doctor_profile_id = $doctorProfileId;
        $job->from_name = $from_name;
        $job->from_address = $from_mail;
        $job->to_name = $to_name;
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
        $job->mail_type = 'doctor';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateMail($hash, $status, $messageId,
                               $open_time, $open_bounce_data,
                               $unsubscribe_data, $spam_data)
    {
        $job = new praccomm\DoctorMail();
        $job->hash = $hash;
        $job->delivery_status = Helper::getMailDeliveryStatus($status);
        $job->message_id = $messageId;
        $job->open_time = $open_time;
        $job->open_bounce_data = $open_bounce_data;
        $job->unsubscribe_data = $unsubscribe_data;
        $job->spam_data = $spam_data;
        $job->mail_type = 'doctor';
        $jsonifiedJob = json_encode($job);
        $queueName = $this->_updateMailQueue;
        return $this->_queue
                    ->setQueueName(Queue::UPDATE_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function searchMailByPracticeId($pid, $sendDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->search_by_practice_id($pid, $sendDate);
        $transport->close();
        return $result;
    }

    public function getMailByDateRange($pid, $startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_mails_by_status($pid, $startDate, $endDate);
        $transport->close();
        return $result;
    }

    public function getMailByApplication($startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_mails_by_application($startDate, $endDate);
        $transport->close();
        return $result;
    }

    public function getMailByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_mails_by_app_for_pid($pid, $startDate, $endDate);
        $transport->close();
        return $result;
    }

    public function getMailById($mailId)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_mail_by_id($mailId);
        $transport->close();
        return $result;
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
        $client = new praccomm\DoctorMailServiceClient($protocol);
        return $client;
    }

    private function getMailType($type)
    {
        switch($type)
        {
            case "CONFIRMATION":
              return \praccomm\DoctorEmailType::CONFIRMATION;
            case "CANCELLATION":
              return \praccomm\DoctorEmailType::CANCELLATION;
            case "ACCOUNT_INVITE":
              return \praccomm\DoctorEmailType::ACCOUNT_INVITE;
            case "ONLINE_APPOINTMENT":
              return \praccomm\DoctorEmailType::ONLINE_APPOINTMENT;
            case "REMINDER":
              return \praccomm\DoctorEmailType::REMINDER;
            case "RESCHEDULE":
              return \praccomm\DoctorEmailType::RESCHEDULE;
            case "BILLS_REPORT":
              return \praccomm\DoctorEmailType::BILLS_REPORT;
            default:
              throw new Exception("Invalid message type");
        }
    }
}
?>