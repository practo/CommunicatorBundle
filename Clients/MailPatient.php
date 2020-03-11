<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Mail clinet for mailing patients
 */
class MailPatient extends Base
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

    public function sendMail($contactId, $practiceId, $fromName,
                             $fromMail, $to_name = "", $to, $cc = "",
                             $bcc = "", $subject, $body, $type,
                             $files, $hash, $plaintext = "", $replyTo = NULL,
                             $expiryTime = NULL)
    {
        $this->validateMailType($type);
        $job = new praccomm\PatientMail();
        $job->patient_contact_id = $contactId;
        $job->practice_id = $practiceId;
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
        $job->email_type = $type;
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
        $this->_queue->setDomain($this->_domain);
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
        $this->_queue->setDomain($this->_domain);
        $this->_queue
             ->setQueueName(Queue::UPDATE_MAIL_QUEUE)
             ->sendMessage($jsonified_job, 10);
    }

    public function searchMailByPracticeId($pid, $sendDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['send_date'] = $sendDate;
        $result = $client->get("/patientmail/search_by_practice_id", $params);
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
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['cid'] = $cid;
        $params['start_date'] = $start_ts;
        $params['end_date'] = $end_ts;
        $result = $client->get("/patientmail/get_mails_by_contact_id", $params);
        return $result;
    }

    public function getMailByDateRange($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientmail/get_mails_by_status", $params);
        return $result;
    }

    public function getMailByApplication($startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientmail/get_mails_by_application", $params);
        return $result;
    }

    public function getMailByPidGroupedByApp($pid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientmail/get_mails_by_app_for_pid", $params);
        return $result;
    }

    public function getMailByCidGroupedByApp($pid, $cid, $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $params['cid'] = $cid;
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $result = $client->get("/patientmail/get_mails_by_app_for_cid", $params);
        return $result;
    }

    public function getMailById($mailId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['patient_mail_id'] = $mailId;
        $result = $client->get("/patientmail/get_mail_by_id", $params);
        return $result;
    }

    private function validateMailType($type)
    {
        $allowedTypes = array(
            'ACCOUNT_INVITE',
            'PATIENT_AMOUNT_DUE',
            'AMOUNT_DUE',
            'BILL',
            'BIRTHDAY',
            'CANCELLATION',
            'CONFIRMATION',
            'FOLLOWUP',
            'ONLINE_APPOINTMENT',
            'PRESCRIPTION',
            'REMINDER',
            'RESCHEDULE',
            'TREATMENT',
            'TREATMENTPLAN',
            'INVOICE',
            'PAYMENT',
            'TIMELINE',
            'FILE',
            'BILLS_REPORT',
            'MEDICAL_LEAVE',
            'VITAL_SIGN',
            'PATIENT_GROWTH',
            'IMMUNISATION',
            'IMMUNISATION_REMINDER',
            'PATIENT_SMS_BLACKLISTED',
            'IMMUNISATIONCARD',
            'GROWTH_CHARTS',
            'PATIENT_LAB_ORDER_RESULT',
            'PATIENT_LAB_ORDERS',
            'LENS_PRESCRIPTION',
            'LENS_REPLACEMENT_REMINDER',
            'LAB_ORDER_CONFIRMATION',
            'LAB_ORDER_DUEON',
            'LAB_ORDER_RESULT',
            'ANNIVERSARY',
            'LIFESTYLE_ADVISORY',
            'DENTAL_CHART',
            'CHART'
        );

        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid message type");
        }
    }
}
?>
