<?php

namespace Practo\CommunicatorBundle\Exceptions;

class PushException extends Exception
{
    /**
     *    Base Exception for Push Notifications
     */
}

class PushValidationException extends PushException
{
    /**
     *    Exception for failing validations in Push Notifications
     */
}
