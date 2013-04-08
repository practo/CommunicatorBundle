<?php
namespace Practo\CommunicatorBundle\Clients;

use Pheanstalk\Pheanstalk;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;


/**
 * Mail clinet for mailing staff
 */
class MailStaff
{
    private $_mailServer;
    private $_mailPort;
    private $_sendMailQueue;
    private $_updateMailQueue;
    private $_appName;
    
    private $_pheanstalk;
    
    public function __construct($pheanstalk, $sendQueue,
                                $updateQueue, $serviceHost,
                                $servicePort, $appName)
    {
        $this->_mailServer = $serviceHost;
        $this->_mailPort = $servicePort;
        $this->_sendMailQueue = $sendQueue;
        $this->_updateMailQueue = $updateQueue;
        $this->_appName = Helper::getValidAppName($appName);
        $this->_pheanstalk = $pheanstalk;
    }

    public function sendMail($practiceId, $fromName, $fromMail,
                             $to_name = "", $to, $cc = "", $bcc = "",
                             $subject, $body, $type, $fileId,
                             $hash, $plaintext = "")
    {
        $job = new praccomm\StaffMail();
        $job->practice_id = $practiceId;
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
        $job->attachments = $fileId;
        $job->hash = $hash;
        $job->mail_type = 'staff';
        $jsonified_job = json_encode($job);
        $queueName = $this->_sendMailQueue;
        $this->_pheanstalk->useTube($queueName)->put($jsonified_job);
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
        $jsonified_job = json_encode($job);
        $queueName = $this->_updateMailQueue;
        $this->_pheanstalk->useTube($queueName)->put($jsonified_job);
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
        $transport =
          new Thrift\Transport\TBufferedTransport($socket, 1024, 1024);
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
        default:
          throw new Exception("Invalid message type");
      }
    }
}
?>