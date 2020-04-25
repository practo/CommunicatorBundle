<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for generic sms
 */
class SmsGenericPromotional extends Base
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
                            $expiryTime = null, $campaign_id = null)
    {
        $this->validateSmsType(strtoupper($messageType));
        $job = new praccomm\GenericPromotionalSMS();
        $job->reference_id = $reference_id;
        $job->reference_type = $reference_type;
        $job->name = $name;
        $job->mobile = $to;
        $job->expiry_time = $expiryTime;
        $job->message_text = $body;
        $job->message_type = strtoupper($messageType);
        $job->app_name = $this->_appName;
        $job->campaign_id = $campaign_id;
        $job->sms_type = 'generic_promotional';
        $job->initiated_at = date("Y-m-d H:i:s");
        $jsonifiedJob = json_encode($job);
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SEND_GENERIC_PROMOTIONAL_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
    }

    private function validateSmsType($type)
    {
        $allowedTypes = array(
          'VN_APP_PROMOTION_SMS',
          'FIT_RAY_SHARE_SMS',
          'PAYTM_OFFERS_SMS',
          'MARKETING_APP_LINK_SMS',
          'APPOINTMENT_COUPON_CODE_SMS',
          'PHR_TAX_SAVING_NOTIFICATION_SMS',
          'ORDER_PROMOTIONAL_SMS',
          'SURVEY_SMS',
          'CONSULT_FREE_QUESTION_PROMOTIONAL',
          'CONSULT_PAID_PROMOTIONAL',
          'CONSULT_FOLLOWUP_PROMOTIONAL',
          'CONSULT_REFERRAL_CODE',
          'PRIME_ONLINE_RAY',
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
        $result = $client->get("/genericpromotionalsms/get_messages_by_reference_id", $params);
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
        $result = $client->get("/genericpromotionalsms/get_messages_by_ref_id_and_type", $params);

        return $result;
    }

    public function isMsgSentOfTypeForRecipient($messageType, $mobile)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['message_type'] = $messageType;
        $params['mobile'] = $mobile;
        $result = $client->get("/genericpromotionalsms/is_msg_sent_of_type_for_recipient", $params);
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
        $result = $client->get("/genericpromotionalsms/get_message_count_for_type", $params);
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
        $result = $client->get("/genericpromotionalsms/get_message_count_by_reference_ids", $params);
        return $result;
    }
}
?>
