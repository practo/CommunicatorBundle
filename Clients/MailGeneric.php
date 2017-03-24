<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for sending generic mails
 */
class MailGeneric extends Base
{
    private $_appName;
    private $_queue;
    private $_apiSecretKey;

    public function __construct($queue, $apiSecretKey, $appName)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendMail($fromName, $fromMail, $toName = "", $to,
                             $cc = "", $bcc = "", $subject, $body, $type,
                             $files, $hash, $plaintext = "", $replyTo = NULL,
                             $reference_id = NULL, $reference_type = NULL,
                             $expiryTime = NULL, $appName = null, $createdBy = NULL)
    {
        $this->validateMailType(strtoupper($type));
        $job = new praccomm\GenericMail();
        $job->from_name = $fromName;
        $job->from_address = $fromMail;
        $job->to_name = $toName;
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
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->app_name = $this->_appName;
        if ($createdBy) {
            $job->created_by = $createdBy;
        }
        if ($appName) {
            $job->app_name = Helper::getValidAppName($appName);
        }
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
        $this->_queue->setDomain($this->_domain);
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
        $this->_queue->setDomain($this->_domain);
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
            'MERGE_FAILED',
            'VN_DETAILS',
            'ALERT_BAD_HEALTH',
            'FABRIC_DQS_REPORT',
            'SURVEY_VERIFICATION_EMAIL',
            'FIT_GENERIC_MAIL',
            'FIT_EDITOR_FEEDBACK_MAIL',
            'CONSULT_GENERIC_MAIL',
            'REACH_TOS_MAIL',
            'REACH_QUALITY_REMINDER',
            'QIKWELL_APPOINTMENT_REMINDER_MAIL',
            'FEEDBACK_DAILY_HEALTH_EMAIL',
            'FEEDBACK_SURVEY_EMAIL',
            'TAB_BIAS_DAILY_REPORT',
            'TAB_BIAS_DETECTION_EMAIL',
            'SCOUT_HOURLY_MAIL',
            'PHARMA_MAIL',
            'RAY_REPORT_APPOINTMENTS',
            'RAY_REPORT_APPOINTMENTS_SUMMARY',
            'RAY_REPORT_DAILY_SUMMARY',
            'RAY_REPORT_EXPENSE',
            'RAY_REPORT_EXPENSE_SUMMARY',
            'RAY_REPORT_INVOICE',
            'RAY_REPORT_INVOICE_SUMMARY',
            'RAY_REPORT_PATIENTS',
            'RAY_REPORT_PATIENTS_SUMMARY',
            'RAY_REPORT_PAYMENT',
            'RAY_REPORT_PAYMENTS_SUMMARY',
            'RAY_REPORT_PAYMENT_DETAILS_SUMMARY',
            'RAY_REPORT_TREATMENTS',
            'RAY_REPORT_TREATMENTS_SUMMARY',
            'RAY_REPORT_INVENTORY_TIMELINE',
            'RAY_REPORT_PAYMENT_DETAILS',
            'DOCTOR_NO_SHOW',
            'REACH_DAILY_ROI_EMAIL',
            'FABRIC_BLUEBOOK_MAIL',
            'APPOINTMENT_COUPON_CODE_MAIL',
            'APPOINTMENT_REMINDER_MAIL',
            'SPACE_WORKFLOW_MAIL',
            'CROWDSOURCE_DOCTOR_PROOF',
            'REACH_REFUND_INITIATION_WITH_KYCLINK',
            'REACH_REFUND_INITIATION',
            'REACH_REFUND_SUCCESS',
            'REACH_REFUND_FAILURE',
            'APPOINTMENT_PAYMENT_CONFIRMED',
            'APPOINTMENT_PAYMENT_FAILED',
            'APPOINTMENT_REFUND_COMPLETED',
            'APPOINTMENT_REFUND_FAILED',
            'APPOINTMENT_FAILED_PAYMENT_CONFIRMED',
            'CLAIM_PROFILE_FLOW_MAIL',
            'TATKAL_NOTIFICATION_MAIL',
            'PARTNER_PUBLISH_NOTIFICATION_EMAIL',
            'BOOK_GENERIC_NOTIFICATION',
            'APPOINTMENT_PNS',
            'ENTERPRISE_REPORT'
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
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
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
