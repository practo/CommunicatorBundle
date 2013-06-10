<?php
namespace Practo\CommunicatorBundle\Clients;

use Pheanstalk\Pheanstalk;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Thrift;

/**
 * Mail clinet for mailing doctors
 */
class SmsCredit
{
    private $_smsCreditServer;
    private $_smsCreditPort;
    private $_insertCreditQueue;
    private $_updateCreditQueue;

    private $pheanstalk;

    public function __construct($pheanstalk, $queuePort, $insertCreditQueue,
                                $updateCreditQueue, $serviceHost, $servicePort)
    {
        $this->_smsCreditServer = $serviceHost;
        $this->_smsCreditPort = $servicePort;
        $this->_insertCreditQueue = $insertCreditQueue;
        $this->_updateCreditQueue = $updateCreditQueue;
        $this->pheanstalk = $pheanstalk;
    }

    public function insertSmsCredit($practiceId, $credit, $currency, $planId)
    {
        $job = new praccomm\SmsCredit();
        $job->practice_id = $practiceId;
        $job->app_name = getValidAppName($appName);
        $job->credit = $credit;
        $job->service_type = $serviceType;
        $job->action = 'insert';
        $jsonified_job = json_encode($job);
        $queueName = $this->_insertCreditQueue;
        $this->pheanstalk->useTube($queueName)->put($jsonified_job);
    }

    public function increaseSmsCredit($practiceId, $credit, $currency)
    {
        $job = new praccomm\SmsCredit();
        $job->practice_id = $practiceId;
        $job->credit = $credit;
        $job->currency = $currency;
        $job->action = 'update';
        $jsonified_job = json_encode($job);
        $queueName = $this->_updateCreditQueue;
        $this->pheanstalk->useTube($queueName)->put($jsonified_job);
    }

    public function decreaseSmsCredit($practiceId, $credit, $currency)
    {
        $job = new praccomm\SmsCredit();
        $job->practice_id = $practiceId;
        $job->credit = $credit;
        $job->currency = $currency;
        $job->action = 'update';
        $jsonified_job = json_encode($job);
        $queueName = $this->_updateCreditQueue;
        $this->pheanstalk->useTube($queueName)->put($jsonified_job);
    }

    public function getCredits($pid)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_credit($pid);
        $transport->close();
        return $result;
    }

    private function getTransport()
    {
        $socket = new Thrift\Transport\TSocket($this->_smsCreditServer,
                                               $this->_smsCreditPort);
        $socket->setRecvTimeout(20 * 1000);
        $socket->setSendTimeout(20 * 1000);

        $transport =
          new Thrift\Transport\TFramedTransport($socket, 1024, 1024);
        return $transport;
    }

    private function getClient($transport)
    {
        $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
        $client = new praccomm\SmsCreditServiceClient($protocol);
        return $client;
    }

}
?>