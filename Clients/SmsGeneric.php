<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Clinet for generic sms
 */
class SmsGeneric
{
    private $_appName;
    private $_queue;
    private $_smsServer;
    private $_smsPort;

    public function __construct($queue, $serviceHost, $servicePort, $appName)
    {
        $this->_queue = $queue;
        $this->_smsServer = $serviceHost;
        $this->_smsPort = $servicePort;
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
            'VN_APP_PROMOTION_SMS'
        );
        if (!in_array($type, $allowedTypes, true)) {
            throw new Exception("Invalid message type");
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

    public function getMessagesBySmsTypeAndRecipient($messageType, $mobile)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_messages_by_smstype_and_recipient($messageType, $mobile);
        $transport->close();
        return $result;
    }

    private function getTransport()
    {
        $socket = new Thrift\Transport\TSocket($this->_smsServer, $this->_smsPort);

        $socket->setRecvTimeout(20 * 1000);
        $socket->setSendTimeout(20 * 1000);

        $transport =
          new Thrift\Transport\TFramedTransport($socket, 1024, 1024);
        return $transport;
    }

    private function getClient($transport)
    {
        $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
        $client = new praccomm\GenericSMSServiceClient($protocol);
        return $client;
    }
}
?>
