<?php

namespace Practo\CommunicatorBundle\Queue;

/**
 * Queue Message
 */
class Message
{
    protected $messageId;
    protected $messageText;
    protected $messageHandle;

    /**
     * Constructor
     *
     * @param string $messageText - Message Text
     * @param string $messageId   - Message Id
     */
    public function __construct($messageText, $messageId = null)
    {
        $this->messageText = strval($messageText);
        $this->messageId = $messageId;
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->messageId;
    }

    /**
     * Set Id
     *
     * @param string $messageId - Message Id
     *
     * @return Message
     */
    public function setId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get Handle
     *
     * @return string
     */
    public function getHandle()
    {
        return $this->messageHandle;
    }

    /**
     * Set Handle
     *
     * @param string $messageHandle - Message Handle
     *
     * @return Message
     */
    public function setHandle($messageHandle)
    {
        $this->messageHandle = $messageHandle;

        return $this;
    }

    /**
     * To String
     *
     * @return string
     */
    public function __toString()
    {
        return $this->messageText;
    }
}
