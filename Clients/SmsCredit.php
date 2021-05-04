<?php
namespace Practo\CommunicatorBundle\Clients;
require_once __DIR__.'/Types.php';

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use praccomm;
use Practo\CommunicatorBundle\Clients\CommunicatorClient;

/**
 * Mail clinet for mailing doctors
 */
class SmsCredit extends Base
{
    private $_apiSecretKey;
    private $_queue;

    public function __construct($queue, $apiSecretKey)
    {
        $this->_apiSecretKey = $apiSecretKey;
        $this->_queue = $queue;
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
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName(Queue::SMS_CREDIT_QUEUE)
                    ->sendMessage($jsonifiedJob);
    }

    public function addPractoCredit($practiceId, $credit)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $practiceId;
        $params['amount'] = $credit;
        $result = $client->post("/smscredit/add_practo_credit", $params);
        return $result;
    }

    // This resets practo credit (free credit) to a given value
    public function resetPractoCredit($practiceId, $credit=0)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $practiceId;
        $params['credit'] = $credit;
        $result = $client->post("/smscredit/reset_practo_credit", $params);
        return $result;
    }

    public function addWalletCredit($practiceId, $credit)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $practiceId;
        $params['amount'] = $credit;
        $result = $client->post("/smscredit/add_wallet_credit", $params);
        return $result;
    }

    public function deductCredit($practiceId, $credit)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $practiceId;
        $params['amount'] = $credit;
        $result = $client->post("/smscredit/deduct_credit", $params);
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
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $practiceId;
        $params['amount'] = $credit;
        $result = $client->post("/smscredit/deduct_wallet_credit", $params);
        return $result;
    }

    /**
     * Deduct call cost
     *
     * @param integer $practiceId         - Practice Id
     * @param string  $callDurationInMins - Call duration
     * @param string  $callType               - Call type
     *
     * @return array
     */
    public function deductHelloCallCost($practiceId, $callDurationInMins, $callType = 'inbound')
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $practiceId;
        $params['call_duration_mins'] = $callDurationInMins;
        $params['call_type'] = $callType;
        $result = $client->post("/smscredit/deduct_hello_call_cost", $params);
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
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = array();
        $params['pid'] = $pid;
        $result = $client->get("/smscredit/get_credit", $params);
        return $result;
    }

    /**
     * Is practice exist
     *
     * @param integer $pid - Credit Id
     *
     * @return array
     */
    public function isPracticeExist($pid)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $pid;
        $result = $client->get("/smscredit/is_practice_exist", $params);

        return $result;
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

    public function insertPracticeCredit($practiceId, $credit, $currency, $planId, $helloPlan, $walletCredit)
    {
        $client = new CommunicatorClient($this->getCommunicatorUri(), $this->_apiSecretKey);
        $params = [];
        $params['pid'] = $practiceId;
        $params['credit'] = $credit;
        $params['user_credit'] = $walletCredit;
        $params['currency'] = $this->getCurrency($currency);
        $params['plan_id'] = $planId;
        $params['hello_plan'] = $helloPlan;
        $result = $client->post("/smscredit/insert_credit", $params);

        return $result;
    }
}
