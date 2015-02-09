<?php

namespace Practo\CommunicatorBundle\Clients;

require_once __DIR__.'/genphp/praccomm/Types.php';

class Helper
{
    public static function getMailDeliveryStatus($status)
    {
        switch($status)
        {
            case 'DELIVERED':
                return praccomm\DeliveryStatus::DELIVERED;
            case 'OPENED':
                return praccomm\DeliveryStatus::OPENED;
            case 'BOUNCED':
                return praccomm\DeliveryStatus::BOUNCED;
            case 'FAILED':
                return praccomm\DeliveryStatus::FAILED;
            case 'NOVALIDEMAIL':
                return praccomm\DeliveryStatus::NOVALIDEMAIL;
            default:
                throw new \Exception("Invalid delivery status");
        }
    }

    public static function getSMSDeliveryStatus($status)
    {
        switch($status)
        {
            case 'DELIVERED':
                return praccomm\DeliveryStatus::DELIVERED;
            case 'FAILED':
                return praccomm\DeliveryStatus::FAILED;
            case 'SENT':
                return praccomm\DeliveryStatus::SENT;
            case 'WAITING':
                return praccomm\DeliveryStatus::WAITING;
            default:
                throw new \Exception("Invalid delivery status");
        }
    }

    public static function getValidAppName($appName)
    {
        $apps = array("RAY", "HELLO", "FABRIC", "EPICENTER");
        $appNameUpper = strtoupper($appName);
        if (in_array($appNameUpper, $apps)) {
            return $appNameUpper;
        } else {
            throw new \Exception('Invalid application name. Has to be one of' +
                                ' RAY, HELLO, FABRIC, EPICENTER');
        }
    }
}
