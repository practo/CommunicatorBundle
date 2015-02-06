<?php

namespace Practo\CommunicatorBundle\Queue;

/**
 * Abstract Queue
 */
abstract class AbstractQueue
{
    private $queueName;
    private $queuePrefix = '';

    const SEND_MAIL_QUEUE              = 'mail_sender';
    const UPDATE_MAIL_QUEUE            = 'mail_updater';
    const SEND_APPOINTMENT_SMS_QUEUE   = 'appointment_sms_sender';
    const UPDATE_APPOINTMENT_SMS_QUEUE = 'appointment_sms_updater';
    const SEND_GROUP_SMS_QUEUE         = 'group_sms_sender';
    const UPDATE_GROUP_SMS_QUEUE       = 'group_sms_updater';
    const SEND_MAIL_ERROR_QUEUE        = 'mail_sender_error';
    const UPDATE_MAIL_ERROR_QUEUE      = 'mail_updater_error';
    const SEND_SMS_QUEUE               = 'sms_sender';
    const UPDATE_SMS_QUEUE             = 'sms_updater';
    const RESEND_SMS_QUEUE             = 'sms_resender';
    const SEND_SMS_ERROR_QUEUE         = 'sms_sender_error';
    const UPDATE_SMS_ERROR_QUEUE       = 'sms_updater_error';
    const SMS_CREDIT_QUEUE             = 'credit_handler';
    const METRIC_MONITOR               = 'metric_monitor';

    /**
     * Receive Message
     *
     * @return Message
     */
    abstract public function receiveMessage();

    /**
     * Send Message
     *
     * @param mixed   $message - Message
     * @param integer $delay   - Delay
     */
    public function sendMessage($message, $delay = null)
    {
        if (!($message instanceof Message)) {
            $message = new Message($message);
        }

        $this->doSendMessage($message, $delay);
    }

    /**
     * Actual Send Message
     *
     * @param Message $message - Message
     * @param integer $delay   - Delay
     */
    abstract protected function doSendMessage(Message $message, $delay = null);

    /**
     * Delete Message
     *
     * @param Message $message - Message
     */
    abstract public function deleteMessage($message);

    /**
     * Constructor
     *
     * @param string $queuePrefix - Queue Prefix
     */
    public function __construct($queuePrefix = '')
    {
        $this->queuePrefix = $queuePrefix;
    }

    /**
     * Get Queue Name
     *
     * @return string
     */
    public function getQueueName()
    {
        return $this->queuePrefix . $this->queueName;
    }

    /**
     * Set Queue Name
     *
     * @param string $queueName - Queue Name
     *
     * @return AbstractQueue
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }

    /**
     * Get Visibility Timeout
     *
     * @return integer
     */
    public function getVisibilityTimeout()
    {
        switch ($this->queueName) {
            case self::SEND_MAIL_QUEUE:
            case self::UPDATE_MAIL_QUEUE:
            case self::SEND_APPOINTMENT_SMS_QUEUE:
            case self::UPDATE_APPOINTMENT_SMS_QUEUE:
            case self::SEND_GROUP_SMS_QUEUE:
            case self::UPDATE_GROUP_SMS_QUEUE:
            case self::SEND_MAIL_ERROR_QUEUE:
            case self::UPDATE_MAIL_ERROR_QUEUE:
            case self::SEND_SMS_QUEUE:
            case self::UPDATE_SMS_QUEUE:
            case self::RESEND_SMS_QUEUE:
            case self::SEND_SMS_ERROR_QUEUE:
            case self::UPDATE_SMS_ERROR_QUEUE:
            case self::SMS_CREDIT_QUEUE:
            case self::METRIC_MONITOR:
            default:
                return 60;
        }
    }
}
