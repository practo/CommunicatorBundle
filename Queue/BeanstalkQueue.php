<?php

namespace Practo\CommunicatorBundle\Queue;

use Pheanstalk_Pheanstalk as Pheanstalk;
use Pheanstalk_Job;

/**
 * Beanstalk Queue
 */
class BeanstalkQueue extends AbstractQueue
{
    protected $pheanstalk;

    /**
     * Constructor
     *
     * @param string $uri - Uri. Eg: beanstalk://localhost
     */
    public function __construct($uri)
    {
        $parts = parse_url($uri);
        $host = @$parts['host']?:'localhost';
        $port = @$parts['port']?:11300;
        $this->pheanstalk = new Pheanstalk($host, $port);
        if (($queueName = ltrim(@$parts['path'], '/'))) {
            if (in_array(substr($queueName, -1), array('-', '_'))) {
                parent::__construct($queueName);
            } else {
                $this->setQueueName($queueName);
            }
        }
    }

    /**
     * Receive Message
     *
     * @return Message
     */
    public function receiveMessage()
    {
        $job = $this->pheanstalk
                    ->watchOnly($this->getQueueName())
                    ->reserve();
        $message = new Message($job->getData(), $job->getId());

        return $message;
    }

    /**
     * Actual Send Message
     *
     * @param Message $message - Message
     * @param integer $delay   - Delay
     */
    protected function doSendMessage(Message $message, $delay=null)
    {
        $messageId = $this->pheanstalk
                          ->useTube($this->getQueueName())
                          ->put(
                              strval($message),
                              Pheanstalk::DEFAULT_PRIORITY,
                              $delay,
                              $this->getVisibilityTimeout()
                          );
        $message->setId($messageId);
    }

    /**
     * Delete Message
     *
     * @param Message $message - Message
     */
    public function deleteMessage($message)
    {
        $job = new Pheanstalk_Job($message->getId(), strval($message));
        $this->pheanstalk
             ->useTube($this->getQueueName())
             ->delete($job);
    }
}
