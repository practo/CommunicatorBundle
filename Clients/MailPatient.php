<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Thrift;
require_once __DIR__.'/genphp/praccomm/PatientMailService.php';

/**
 * Mail clinet for mailing patients
 */
class MailPatient
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

    public function sendMail($contactId, $practiceId, $fromName,
                             $fromMail, $to_name = "", $to, $cc = "",
                             $bcc = "", $subject, $body, $type,
                             $files, $hash, $plaintext = "", $replyTo = null)
    {
        $job = new praccomm\PatientMail();
        $job->patient_contact_id = $contactId;
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
        $job->mail_type = 'patient';
        $jsonified_job = json_encode($job);
        $this->_queue
             ->setQueueName(Queue::SEND_MAIL_QUEUE)
             ->sendMessage($jsonified_job, 10);
    }

    public function updateMail($hash, $status, $messageId,
                               $open_time, $open_bounce_data,
                               $unsubscribe_data, $spam_data)
    {
        $job = new praccomm\PatientMail();
        $job->hash = $hash;
        $job->delivery_status = Helper::getMailDeliveryStatus($status);
        $job->email_provider = $emailProvider;
        $job->message_id = $messageId;
        $job->smtp_reply = $smtpReply;
        $job->open_time = $open_time;
        $job->open_bounce_data = $open_bounce_data;
        $job->unsubscribe_data = $unsubscribe_data;
        $job->spam_data = $spam_data;
        $job->mail_type = 'patient';
        $jsonified_job = json_encode($job);
        $this->_queue
             ->setQueueName(Queue::UPDATE_MAIL_QUEUE)
             ->sendMessage($jsonified_job, 10);
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

    public function searchMailByContactId($pid, $cid,
                                          $start = NULL, $end = NULL)
    {
        if(is_null($start) and !is_int($start))
        {
            $start_ts = mktime(0, 0, 0, 1, 2, 1970);
        }
        else
        {
            $start_ts = $start;
        }

        if(is_null($end) and !is_int($end))
        {
            $end_ts = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        }
        else
        {
            $end_ts = $end;
        }
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_mails_by_contact_id($pid, $cid,
                                                   $start_ts, $end_ts);
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

    public function getMailByCidGroupedByApp($pid, $cid, $startDate, $endDate)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result =
          $client->get_mails_by_app_for_cid($pid, $cid, $startDate, $endDate);
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
        $client = new praccomm\PatientMailServiceClient($protocol);
        return $client;
    }

    private function getMailType($type)
    {
      switch($type)
        {
        case "ACCOUNT_INVITE":
            return \praccomm\PatientEmailType::ACCOUNT_INVITE;
        case "AMOUNT_DUE":
        case "PATIENT_AMOUNT_DUE":
            return \praccomm\PatientEmailType::AMOUNT_DUE;
        case "BILL":
            return \praccomm\PatientEmailType::BILL;
        case "BIRTHDAY":
            return \praccomm\PatientEmailType::BIRTHDAY;
        case "CANCELLATION":
            return \praccomm\PatientEmailType::CANCELLATION;
        case "CONFIRMATION":
            return \praccomm\PatientEmailType::CONFIRMATION;
        case "FOLLOWUP":
            return \praccomm\PatientEmailType::FOLLOWUP;
        case "ONLINE_APPOINTMENT":
            return \praccomm\PatientEmailType::ONLINE_APPOINTMENT;
        case "PRESCRIPTION":
            return \praccomm\PatientEmailType::PRESCRIPTION;
        case "REMINDER":
            return \praccomm\PatientEmailType::REMINDER;
        case "RESCHEDULE":
            return \praccomm\PatientEmailType::RESCHEDULE;
        case "TREATMENT":
            return \praccomm\PatientEmailType::TREATMENT;
        case "TREATMENTPLAN":
            return \praccomm\PatientEmailType::TREATMENTPLAN;
        case "INVOICE":
            return \praccomm\PatientEmailType::INVOICE;
        case "PAYMENT":
            return \praccomm\PatientEmailType::PAYMENT;
        case "TIMELINE":
            return \praccomm\PatientEmailType::TIMELINE;
        case "FILE":
            return \praccomm\PatientEmailType::FILE;
        case "BILLS_REPORT":
            return \praccomm\PatientEmailType::BILLS_REPORT;
        case "MEDICAL_LEAVE":
            return \praccomm\PatientEmailType::MEDICAL_LEAVE;
        case "VITAL_SIGN":
            return \praccomm\PatientEmailType::VITAL_SIGN;
        case "PATIENT_GROWTH":
            return \praccomm\PatientEmailType::PATIENT_GROWTH;
        default:
            throw new \Exception("Invalid message type");
        }
    }
}
?>
