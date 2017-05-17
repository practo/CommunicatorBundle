<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;


/**
 * Mail clinet for mailing staff
 */
class MailStaff extends Base
{
    private $_apiSecretKey;
    private $_appName;
    private $_queue;

    public function __construct($queue, $apiSecretKey, $appName)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_appName = Helper::getValidAppName($appName);
        $this->_queue = $queue;
    }

    public function sendMail($practiceId, $staffProfileId, $fromName, $fromMail,
                             $to_name = "", $to, $cc = "", $bcc = "",
                             $subject, $body, $type, $files,
                             $hash, $plaintext = "", $replyTo=NULL,
                             $expiryTime = NULL)
    {
        $job = new praccomm\StaffMail();
        $job->practice_id = $practiceId;
        $job->staff_profile_id = $staffProfileId;
        $job->from_name = $fromName;
        $job->from_address = $fromMail;
        $job->to_name = $to_name;
        $job->to_address = $to;
        $job->expiry_time = $expiryTime;
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
        $this->_queue->setDomain($this->_domain);
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
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::UPDATE_MAIL_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    public function searchMailByPracticeId($pid, $sendDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['send_date'] = $sendDate;
        $result = $client->get("/staffmail/search_by_practice_id", $params);
        return $result;
    }

    public function getMailByDateRange($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/staffmail/get_mails_by_status", $params);
        return $result;
    }

    public function getMailByApplication($startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/staffmail/get_mails_by_application", $params);
        return $result;
    }

    public function getMailByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/staffmail/get_mails_by_app_for_pid", $params);
        return $result;
    }

    public function getMailById($mailId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['staff_mail_id'] = $mailId;
        $result = $client->get("/staffmail/get_mail_by_id", $params);
        return $result;
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
        case "TOTP_ENABLED":
          return \praccomm\StaffEmailType::TOTP_ENABLED;
        case "LAB_ORDERS":
          return \praccomm\StaffEmailType::LAB_ORDERS;
        case "V6_UPGRADE":
          return \praccomm\StaffEmailType::V6_UPGRADE;
        case "SHARE_SETTINGS_UPDATE":
          return \praccomm\StaffEmailType::SHARE_SETTINGS_UPDATE;
        case "SAVE_AND_ORDER":
          return praccomm\StaffEmailType::SAVE_AND_ORDER;
        case "EXPORT_FILES":
          return praccomm\StaffEmailType::EXPORT_FILES;
        case 'ONLINE_TRANSACTION_SUMMARY':
          return praccomm\StaffEmailType::ONLINE_TRANSACTION_SUMMARY;
        case 'EXPIRING_BATCHES':
          return praccomm\StaffEmailType::EXPIRING_BATCHES;
        case "MY_DOCTORS_SETTINGS_UPDATE":
          return \praccomm\StaffEmailType::MY_DOCTORS_SETTINGS_UPDATE;
        case "VERSION_UPGRADES":
          return \praccomm\StaffEmailType::VERSION_UPGRADES;
        default:
          throw new \Exception("Invalid message type");
      }
    }
}
?>
