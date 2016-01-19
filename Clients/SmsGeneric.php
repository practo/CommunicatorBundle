<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Client for generic sms
 */
class SmsGeneric
{
    private $_appName;
    private $_queue;
    private $_communicatorBaseUri;
    private $_apiSecretKey;

    public function __construct($queue, $communicatorBaseUri, $apiSecretKey, $appName)
    {
        $this->_communicatorBaseUri = $communicatorBaseUri;
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
        $this->_appName = Helper::getValidAppName($appName);
    }

    public function sendSms($reference_id, $reference_type,
                            $name, $to, $messageType, $body,
                            $notes = null, $expiryTime = null)
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
        $jsonifiedJob = json_encode($job);
        return $this->_queue
                    ->setQueueName(Queue::SEND_GENERIC_SMS_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
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
            'QIKWELL_APPOINTMENT_REMINDER_SMS'
        );
        if (!in_array($type, $allowedTypes, true)) {
            throw new \Exception("Invalid message type");
        }
    }

    public function getMessageLogBySurveyId($surveyId)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = array();
        $params['reference_id'] = $surveyId;
        $params['sms_types'] = array('SURVEY_SMS');
        $result = $client->get("/genericsms/get_messages_by_reference_id", $params);
        return $result;
    }

    public function isMsgSentOfTypeForRecipient($messageType, $mobile)
    {
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
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
        $client = new CommunicatorClient($this->_communicatorBaseUri, $this->_apiSecretKey);
        $params = [
            'message_type' => $messageType,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'status'       => $status
        ];
        $result = $client->get("/genericsms/get_message_count_for_type", $params);
        return $result;
    }
}
?>
