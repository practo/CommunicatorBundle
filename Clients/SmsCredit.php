<?php
namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Thrift;

require_once __DIR__.'/genphp/praccomm/SmsCreditService.php';

/**
 * Mail clinet for mailing doctors
 */
class SmsCredit
{
    private $smsCreditServer;
    private $smsCreditPort;
    private $queue;

    public function __construct($queue, $serviceHost, $servicePort)
    {
        $this->smsCreditServer = $serviceHost;
        $this->smsCreditPort = $servicePort;
        $this->queue = $queue;
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
        return $this->queue
                    ->setQueueName(Queue::SMS_CREDIT_QUEUE)
                    ->sendMessage($jsonifiedJob, 10);
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

    /**
     * Deduct wallet credit
     *
     * @param integer $practiceId - Practice Id
     * @param integer $credit     - Credit
     *
     * @return array
     */
    public function deductWalletCredit($practiceId, $credit)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->deduct_wallet_credit($practiceId, $credit);
        $transport->close();

        return $result;
    }

    /**
     * Deduct call cost
     *
     * @param integer $practiceId         - Practice Id
     * @param string  $callDurationInMins - Call duration
     *
     * @return array
     */
    public function deductHelloCallCost($practiceId, $callDurationInMins)
    {
        $transport = $this->getTransport();
        $client = $this->getClient($transport);
        $transport->open();
        $result = $client->deduct_hello_call_cost(
            $practiceId,
            $callDurationInMins
        );
        $transport->close();

        return $result;
    }

    /**
     * Get credits
     *
     * @param integer $pid - Credit Id
     *
     * @return array
     */
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
        $socket = new Thrift\Transport\TSocket(
            $this->smsCreditServer,
            $this->smsCreditPort
        );
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
                throw new \Exception("Invalid currency");
        }
    }
}
