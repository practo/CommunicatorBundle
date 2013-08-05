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

    public function insertCredit($practiceId, $credit, $currency, $planId, $helloPlan, $walletCredit)
    {
        $job = new praccomm\SmsCredit();
        $job->practice_id = $practiceId;
        $job->credit = $credit;
        $job->currency = $this->getCurrency($currency);
        $job->pricing_plan_id = $planId;
        $job->action = 'insert';
        $job->hello_plan = $helloPlan;
        $job->wallet_credit = $walletCredit;
        $jsonifiedJob = json_encode($job);
        $queueName = $this->_insertCreditQueue;
        $this->pheanstalk->useTube($queueName)->put($jsonified_job);
    }

    public function insertPractoCredit($practiceId, $credit)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->insert_practo_credit($practiceId, $credit);
        $transport->close();

        return $result;
    }

    public function addWalletCredit($practiceId, $credit)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->add_wallet_credit($practiceId, $credit);
        $transport->close();

        return $result;
    }

    public function deductCredit($practiceId, $credit)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->deduct_credit($practiceId, $credit);
        $transport->close();

        return $result;
    }

    public function deductWalletCredit($practiceId, $credit)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->deduct_wallet_credit($practiceId, $credit);
        $transport->close();

        return $result; 
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

    private function getCurrency($curr)
    {
        switch($curr)
        {
            case "INR":
              return praccomm\Currency::INR;
            case "USD":
              return praccomm\Currency::USD;
            case "SGD":
              return praccomm\Currency::SGD;
            default:
              throw new Exception("Invalid currency");
        }
    }
}
?>