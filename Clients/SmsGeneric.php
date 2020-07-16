<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for generic sms
 */
class SmsGeneric extends Base
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

    public function sendSms($reference_id, $reference_type,
                            $name, $to, $messageType, $body,
                            $notes = null, $expiryTime = null, $sender_id = null)
    {
        $this->validateSmsType(strtoupper($messageType));
        $job = new praccomm\GenericSms();
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->name = $name;
        $job->mobile = $to;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->notes = $notes;
        $job->message_type = strtoupper($messageType);
        $job->app_name = $this->_appName;
        $job->sms_type = 'generic';
        $job->sender_id = $sender_id;
        $jsonifiedJob = json_encode($job);
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SEND_GENERIC_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob);
    }

    private function validateSmsType($type)
    {
        $allowedTypes = array(
            'WEEKLY_ROI_SMS',
            'VN_DETAILS',
            'SURVEY_SMS',
            'MARKETING_APP_LINK_SMS',
            'VN_APP_PROMOTION_SMS',
            'SURVEY_VERIFICATION_SMS',
            'QIKWELL_APPOINTMENT_REMINDER_SMS',
            'OTP',
            'CONSULT_DOCTOR_REPLY_SMS',
            'CONSULT_GENERIC_SMS',
            'ORDER_SMS',
            'SMS_ERROR_RESPONSE',
            'GUARANTEE_CLAIM',
            'FEEDBACK_NOTIFICATION_SMS',
            'FIT_RAY_SHARE_SMS',
            'VN_CALL_SUMMARY',
            'DX_ORDER_SMS',
            'FEEDBACK_STATUS_SMS',
            'APPOINTMENT_SUMMARY',
            'APPOINTMENT_COUPON_CODE_SMS',
            'VN_OUTSIDE_PRACTICE_HOURS_SMS',
            'APPOINTMENT_REMINDER_SMS',
            'PAYTM_OFFERS_SMS',
            'VN_MONETIZATION_ANSWERED_CALL_SMS',
            'VN_MONETIZATION_MISSED_CALL_SMS',
            'VN_MONETIZATION_DAILY_SMS',
            'VN_MONETIZATION_WEEKLY_SMS',
            'VN_MONETIZATION_MONTHLY_SMS',
            'VN_MONETIZATION_RENEWAL_SMS',
            'VN_REACH_ANSWERED_CALL_SMS',
            'VN_REACH_MISSED_CALL_SMS',
            'APPOINTMENT_PAYMENT_CONFIRMED',
            'APPOINTMENT_PAYMENT_FAILED',
            'APPOINTMENT_REFUND_COMPLETED',
            'APPOINTMENT_REFUND_FAILED',
            'APPOINTMENT_FAILED_PAYMENT_CONFIRMED',
            'CLAIM_PROFILE_FLOW_SMS',
            'TATKAL_NOTIFICATION_SMS',
            'APPOINTMENT_PNS_DEFAULT',
            'APPOINTMENT_PNS_THIRD',
            'VN_COOL_OFF_SMS',
            'VN_PRIME_FORM',
            'BOOK_APT_PAGE_MAGIC_LINK',
            'APPOINTMENT_CANCELLED_CASHLESS_PLUS',
            'PRIME_ONLINE_CONSULT_COMMUNICATION'
        );
        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("Invalid message type");
        }
    }

    public function getMessageLogBySurveyId($surveyId)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['reference_id'] = $surveyId;
        $params['sms_types'] = array('SURVEY_SMS');
        $result = $client->get("/genericsms/get_messages_by_reference_id", $params);
        return $result;
    }

    /**
     * Get Messages by referenceId and referenceType
     *
     * @param string $referenceId   - Reference Id
     * @param string $referenceType - Reference Type
     * @param array  $smsTypes      - SMS Types
     * @param string $startDate     - Starting Date
     * @param string $endDate       - Ending Date
     *
     * @return Array
     */
    public function getMessagesByReferenceIdAndType($referenceId, $referenceType, $smsTypes,
        $startDate, $endDate)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array(
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'start_date'     => $startDate,
            'sms_types'      => $smsTypes,
            'end_date'       => $endDate
        );
        $result = $client->get("/genericsms/get_messages_by_ref_id_and_type", $params);

        return $result;
    }

    public function isMsgSentOfTypeForRecipient($messageType, $mobile)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['message_type'] = $messageType;
        $params['mobile'] = $mobile;
        $result = $client->get("/genericsms/is_msg_sent_of_type_for_recipient", $params);
        return $result;
    }

    /**
     * Returns count of messages grouped by their delivery status
     * Filtered on: date, message type
     *
     * @param string $messageType - Type of the message
     * @param string $startDate   - Start Date
     * @param string $endDate     - End Date
     * @param array  $status      - Array of status
     *
     * @return array - Array of message counts grouped by delivery status
     */
    public function getMessageCountByType($messageType, $startDate, $endDate, $status)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [
            'message_type' => $messageType,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'status'       => $status
        ];
        $result = $client->get("/genericsms/get_message_count_for_type", $params);
        return $result;
    }

    /**
     * Returns count of messages grouped by their status for given reference IDs
     * Filtered on: message type
     *
     * @param array $referenceIds   - Array of reference ids
     * @param array $smsTypes       - Array of SMS types
     *
     * @return array - Array of message counts grouped by delivery status
     */
    public function getMessageCountByReferenceIds($referenceIds, $smsTypes)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['reference_ids'] = $referenceIds;
        $params['sms_types'] = $smsTypes;
        $result = $client->get("/genericsms/get_message_count_by_reference_ids", $params);
        return $result;
    }
}
?>
