<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;


/**
 * Mail clinet for mailing staff
 */
class MailStaff
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

    public function sendMail($practiceId, $staffProfileId, $fromName, $fromMail,
                             $to_name = "", $to, $cc = "", $bcc = "",
                             $subject, $body, $type, $files,
                             $hash, $plaintext = "", $replyTo=null)
    {
        $job = new praccomm\StaffMail();
        $job->practice_id = $practiceId;
        $job->staff_profile_id = $staffProfileId;
        $job->from_name = $fromName;
        $job->from_address = $fromMail;
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
        $job->mail_type = 'staff';
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function updateMail($hash, $status, $messageId,
                               $open_time, $open_bounce_data,
                               $unsubscribe_data, $spam_data)
    {
        $job = new praccomm\StaffMail();
        $job->hash = $hash;
        $job->delivery_status = Helper::getMailDeliveryStatus($status);
        $job->message_id = $messageId;
        $job->open_time = $open_time;
        $job->open_bounce_data = $open_bounce_data;
        $job->unsubscribe_data = $unsubscribe_data;
        $job->spam_data = $spam_data;
        $job->mail_type = 'staff';
        $jsonifiedJob = json_encode($job);
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
        $client = new praccomm\StaffMailServiceClient($protocol);
        return $client;
    }

    private function getMailType($type)
    {
      switch($type)
      {
        case "ACCOUNT_MAILS":
          return \praccomm\StaffEmailType::ACCOUNT_MAILS;
        case "EXPENSES_REPORT":
          return \praccomm\StaffEmailType::EXPENSES_REPORT;
        case "INCOME_REPORT":
          return \praccomm\StaffEmailType::INCOME_REPORT;
        case "ACCOUNT_INVITE":
          return \praccomm\StaffEmailType::ACCOUNT_INVITE;
        case "ONLINE_APPOINTMENT":
          return \praccomm\StaffEmailType::ONLINE_APPOINTMENT;
        case "REMINDER":
          return \praccomm\StaffEmailType::REMINDER;
        case "SUMMARY_FOLLOWUPBIRTHDAY":
          return \praccomm\StaffEmailType::SUMMARY_FOLLOWUPBIRTHDAY;
        case "ACCOUNT_DELETE":
          return \praccomm\StaffEmailType::ACCOUNT_DELETE;
        case "BILLS_REPORT":
          return \praccomm\StaffEmailType::BILLS_REPORT;
        case "LOW_STOCK":
          return \praccomm\StaffEmailType::LOW_STOCK;
        case "CREDIT_LOW":
          return \praccomm\StaffEmailType::CREDIT_LOW;
        case "CREDIT_ZERO":
          return \praccomm\StaffEmailType::CREDIT_ZERO;
        case "V6_UPGRADE":
          return \praccomm\StaffEmailType::V6_UPGRADE;
        case "EXPIRING_BATCHES":
          return \praccomm\StaffEmailType::EXPIRING_BATCHES;
        default:
          throw new Exception("Invalid message type");
      }
    }
}
?>