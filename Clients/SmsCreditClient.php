<?php
namespace Practo\CommunicatorBundle\Clients;
require_once 'init.php';
require_once 'helper.php';

/**
 * Mail clinet for mailing doctors
 */
class SmsCredit
{
    private $_smsCreditServer;
    private $_smsCreditPort;
    private $_queueHost;
    private $_queuePort;
    private $_insertCreditQueue;
    private $_updateCreditQueue;

    private $pheanstalk;

    public function __construct($queueHost, $queuePort, $insertCreditQueue,
                                $updateCreditQueue, $serviceHost, $servicePort)
    {
        $this->_smsCreditServer = $serviceHost;
        $this->_smsCreditPort = $servicePort;
        $this->_queueHost = $queueHost;
        $this->_queuePort = $queuePort;
        $this->_insertCreditQueue = $insertCreditQueue;
        $this->_updateCreditQueue = $updateCreditQueue;
        $queue_location = $this->_queueHost.":".$this->_queuePort;
        $this->pheanstalk = new Pheanstalk($queue_location);
    }

    public function insertSmsCredit($practiceId, $appName,
                                    $credit, $serviceType)
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

    public function increaseSmsCredit($practiceId, $appName,
                                      $credit, $serviceType)
    {
        $job = new praccomm\SmsCredit();
        $job->practice_id = $practiceId;
        $job->app_name = getValidAppName($appName);
        $job->service_type = $serviceType;
        $job->credit = $credit;
        $job->action = 'update';
        $jsonified_job = json_encode($job);
        $queueName = $this->_updateCreditQueue;
        $this->pheanstalk->useTube($queueName)->put($jsonified_job);
    }

    public function decreaseSmsCredit($practiceId, $appName,
                                      $credit, $serviceType)
    {
        $job = new praccomm\SmsCredit();
        $job->practice_id = $practiceId;
        $job->app_name = getValidAppName($appName);
        $job->service_type = $serviceType;
        $job->credit = -1 * $credit;
        $job->action = 'update';
        $jsonified_job = json_encode($job);
        $queueName = $this->_updateCreditQueue;
        $this->pheanstalk->useTube($queueName)->put($jsonified_job);
    }

    public function getCredits($pid, $appName, $serviceType)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->get_credit($pid, $appName, $serviceType);
        $transport->close();
        return $result;
    }

    private function getTransport()
    {
        $socket = new Thrift\Transport\TSocket($this->_smsCreditServer,
                                               $this->_smsCreditPort);
        $transport =
          new Thrift\Transport\TBufferedTransport($socket, 1024, 1024);
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