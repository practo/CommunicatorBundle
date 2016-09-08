<?php
namespace Practo\CommunicatorBundle\Queue;

use Aws\Sqs\SqsClient;
use Aws\Sqs\Exception\SqsException;

/**
 * SQS Queue
 */
class SQSQueue extends AbstractQueue
{
    protected $sqs;
    protected $scheme;
    protected $region;
    protected $accountId;
    protected $spoolDir;
    const BASE64_EXPAND_RATIO = 1.37;
    const MAX_JOB_SIZE = 262144;
    /**
     * Constructor
     *
     * @param string $uri - Uri. Eg: http://sqs.ap-southeast-1.amazonaws.com/71203182391283/sample-queue
     * @param string $spoolDir - Spool Directory
     */
    public function __construct($uri, $spoolDir = null)
    {
        $this->spoolDir = $spoolDir;
        $parts = parse_url($uri);
        $this->scheme = @$parts['scheme']?:'https';
        $hParts = explode('.', $parts['host'], 3);
        $this->region = @$hParts[1]?:'ap-southeast-1';
        $accessKey = @$parts['user']?:'';
        $accessSecret = @$parts['pass']?:'';
        $this->sqs = new SqsClient(array(
            'version' => '2012-11-05',
            'region' => $this->region,
            'credentials' => array(
                'key'    => $accessKey,
                'secret' => $accessSecret
            )
        ));
        if (($path = ltrim(@$parts['path'], '/'))) {
            $pParts = explode('/', $path, 2);
            $this->accountId = $pParts[0];
            if (count($pParts) > 1 && ($queueName = $pParts[1])) {
                if (in_array(substr($queueName, -1), array('-', '_'))) {
                    parent::__construct($queueName);
                } else {
                    $this->setQueueName($queueName);
                }
            }
        }
    }
    /**
     * Create Queue if not already present
     *
     * @param string $queueName - Queue Name
     */
    protected function createQueueIfNotExists($queueName)
    {
        try {
            $visibilityTimeout = $this->getVisibilityTimeout();
            if ($visibilityTimeout > 43200) {
                // TODO: Post warning in sentry
                $visibilityTimeout = 43200;
            }
            $this->sqs->createQueue(array(
                'QueueName' => $queueName,
                'Attributes' => array(
                    'VisibilityTimeout' => $visibilityTimeout,
                    'ReceiveMessageWaitTimeSeconds' => 20
                )
            ));
        } catch (SqsException $e) {
            if ($e->getAwsErrorCode() === 'QueueAlreadyExists') {
                return;
            }
            throw $e;
        }
    }
    protected function getQueueUrl()
    {
        return "{$this->scheme}://sqs.{$this->region}.amazonaws.com/{$this->accountId}/{$this->getQueueName()}";
    }
    /**
     * Receive Message
     *
     * @return Message
     */
    public function receiveMessage()
    {
        $visibilityTimeout = $this->getVisibilityTimeout();
        if ($visibilityTimeout > 43200) {
            // TODO: Post warning in sentry
            $visibilityTimeout = 43200;
        }
        while (true) {
            try {
                $result = $this->sqs->receiveMessage(array(
                    'QueueUrl' => $this->getQueueUrl(),
                    'MaxNumberOfMessages' => 1,
                    'WaitTimeSeconds' => 20,
                    'VisibilityTimeout' => $visibilityTimeout
                ));
            } catch (SqsException $e) {
                if ($e->getAwsErrorCode() === 'AWS.SimpleQueueService.NonExistentQueue') {
                    $this->createQueueIfNotExists($this->getQueueName());
                    continue;
                }
                throw $e;
            }
            $data = $result->get('Messages');
            if ($data && $data[0]) {
                break;
            }
        }
        $data = $data[0];
        $message = new Message(base64_decode($data['Body']), $data['MessageId']);
        $message->setHandle($data['ReceiptHandle']);
        return $message;
    }
    /**
     * Do Send Message
     *
     * @param Message $message - Message
     * @param integer $delay   - Delay
     */
    protected function doSendMessage(Message $message, $delay = null)
    {
        if ($delay > 900) {
            // TODO: Post warning in sentry
            $delay = 900;
        }
        if ($this->spoolDir) {
            if (!file_exists($this->spoolDir)) {
                mkdir($this->spoolDir);
            }
            if (empty(strval($message))) {
                throw new \Exception('Error in '.$this->getQueueName().'. The message body can not be empty.');
            }
            if (strlen(strval($message)) * self::BASE64_EXPAND_RATIO > self::MAX_JOB_SIZE) {
                $msg = 'One or more parameters are invalid. Reason: Message must be shorter than 262144 bytes.';
                throw new \Exception($msg);
            }
            $currentTime = time();
            $jobDir = intval($currentTime/100);
            if (!file_exists($this->spoolDir.'/'.$jobDir)) {
                mkdir($this->spoolDir.'/'.$jobDir);
            }
            $filename = str_replace('.', '', microtime(true)) . $this->getRandomString(10);
            $payload = implode("\t", array(
                $this->region,
                $this->getQueueUrl(),
                base64_encode(strval($message)),
                $delay,
                $this->getVisibilityTimeout()
            ));
            $status = file_put_contents($this->spoolDir.'/'.$jobDir.'/'.$filename, $payload);
            if ($status === false) {
                $msg = 'File put contents failed. Dir';
                $msg = $msg.'=> '.$this->spoolDir.'/'.$jobDir.'/'.$filename.'. Payload=> '.json_encode($payload);
                throw new \Exception();
            }
            rename($this->spoolDir.'/'.$jobDir.'/'.$filename, $this->spoolDir.'/'.$jobDir.'/'.$filename.'.v1message');
        } else {
            try {
                $messageId = $this->sqs->sendMessage(array(
                    'QueueUrl' => $this->getQueueUrl(),
                    'MessageBody' => base64_encode(strval($message)),
                    'DelaySeconds' => $delay?:0
                ));
            } catch (SqsException $e) {
                if ($e->getAwsErrorCode() === 'AWS.SimpleQueueService.NonExistentQueue') {
                    $this->createQueueIfNotExists($this->getQueueName());
                    $messageId = $this->sqs->sendMessage(array(
                        'QueueUrl' => $this->getQueueUrl(),
                        'MessageBody' => base64_encode(strval($message)),
                        'DelaySeconds' => $delay?:0
                    ));
                } else {
                    throw $e;
                }
            }
            $message->setId($messageId);
        }
    }
    /**
     * Delete Message
     *
     * @param Message $message - Message
     */
    public function deleteMessage($message)
    {
        $this->sqs->deleteMessage(array(
            'QueueUrl' => $this->getQueueUrl(),
            'ReceiptHandle' => $message->getHandle()
        ));
    }
    /**
     * Get Random String
     *
     * @param Integer $count - length of the required random string
     */
    protected function getRandomString($count)
    {
        // This string MUST stay FS safe, avoid special chars
        $base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $ret = '';
        $strlen = strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[((int) rand(0, $strlen - 1))];
        }
        return $ret;
    }
}
