<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use Practo\CommunicatorBundle\Clients\Exceptions;
use praccomm;

/**
 * Abstract class for sending push notifications
 */
abstract class AbstractPush extends Base
{
    private $_service;
    private $_queue;
    private $push_relations = ['=', '>', '<', '>=', '<='];
    private $push_relation_keys = ['app_version', 'last_active'];
    private $comm_app_name = '';
    private $mandatory_keys = [
        'service', 'reference_id', 'reference_type', 'data', 'push_type'
    ];

    abstract protected function validatePushType($pushType);
    abstract protected function validateFilters($inputDict);


    public function __construct($queue, $service)
    {
        $this->_queue = $queue;
        $this->_service = Helper::getValidAppName($service);
    }

    public function removeEmptyKeys($object) {
        var $updated_object = array();
        foreach ($object as $key => $value) {
            if (in_array($value, ['', null, array()]))
                continue;
            else if (type($value) == 'object') {
                var $updated_val = $this->removeEmptyKeys($value);
                if (in_array($updated_val, ['', null, array()]))
                    continue;
                $updated_object[$key] = $updated_val;
            }
            else
                $updated_object[$key] = $value;
        }
        return $updated_object;
    }

    public function addHeaders($pushObject, $inputDict) {
        $pushObject->reference_id = '';
        $pushObject->reference_type = '';
        $pushObject->push_type = '';
        if (array_key_exists('reference_type', $inputDict))
            $pushObject->reference_type = $inputDict['reference_type'];
        if (array_key_exists('reference_id', $inputDict))
            $pushObject->reference_id = $inputDict['reference_id'];
        if (array_key_exists('is_silent', $inputDict))
            $pushObject->is_silent = $inputDict['is_silent'];
        if (array_key_exists('is_content_available', $inputDict))
            $pushObject->is_content_available = $inputDict['is_content_available'];
        if (array_key_exists('push_type', $inputDict))
            $pushObject->push_type = $inputDict['push_type'];
    }

    public function addFilters($pushObject, $inputDict) {
        if (array_key_exists('last_active', $inputDict))
            $pushObject->last_active = $inputDict['last_active'];
        if (array_key_exists('account_id', $inputDict))
            $pushObject->account_id = $inputDict['account_id'];
        if (array_key_exists('device_id', $inputDict))
            $pushObject->device_id = $inputDict['device_id'];
        if (array_key_exists('app_version', $inputDict))
            $pushObject->app_version = $inputDict['app_version'];
    }

    public function addData($pushObject, $inputDict) {
        if (array_key_exists('data', $inputDict))
            $pushObject->data = $inputDict['data'];
    }

    public function preparePushBody($pushObject, $inputDict) {
        $this->addHeaders($pushObject, $inputDict);
        $this->addFilters($pushObject, $inputDict);
        $this->addData($pushObject, $inputDict);
    }

    public function validatePushRelationKeys($inputDict) {
        foreach ($this->push_relation_keys as $key) {
            if (array_key_exists($key, $inputDict)) {
                if (['value', 'relation'] != set(array_keys($inputDict[$key]))
                    throw new PushValidationException("Invalid keys in last active");
                if (!in_array($this->push_relations, $inputDict[$key]['relation'])))
                    throw new PushValidationException("Invalid keys in last active");
            }
        }
    }

    public function validateMandatoryKeys($mandatoryKeys, $inputDict) {
        var $missing_keys = array_diff($mandatory_keys, array_keys($inputDict));
        if (array_len($missing_keys))
            throw new PushValidationException("Missing Mandatory Keys: " . $missing_keys);
        foreach ($inputDict as $key => $value) {
            if (in_array($value, ['', null, array()]))
                throw new PushValidationException("Key: " . $key . "Cannot be empty");
        }
    }

    public function validateTypes($inputDict) {
        var $pdt = new PushDataTypes;
        foreach ($pdt->types as $key => $value) {
            if (in_array($key, $inputDict) && ($inputDict[$key] === null) && (type($inputDict[$key]) !== $value))
                throw new PushValidationException("Invalid Type for key: " . $key);
            }
        }
    }

    public function validateAndroidData($androidData) {
        if (empty($androidData))
            throw new PushValidationException("No Data in Android Payload");
    }

    public function validateIosData($iosData, $isIosSilent, $inputDict) {
        if (empty($iosData))
            throw new PushValidationException("No Data in Ios Payload");
        if ($isIosSilent) {
            $inputDict['is_content_available'] = true;
            if (array_len(array_diff(array_keys($iosData), ['custom'])) > 0) {
                throw new PushValidationException("Silent Push can only have custom keys");
            }
        }
        else if (array_len(array_diff(array_keys($iosData), ['custom'])) == 0) {
            throw new PushValidationException("Non Silent Push data cant be empty");
        }
    }

    public function validateData($inputDict) {
        if ($inputDict['is_silent'] === null)
            throw new PushValidationException("Is Silent value cannot be None");
        if ('android' === $inputDict['app_name'])
            $this->validateAndroidData($inputDict['data']);
        else if ('ios' === $inputDict['app_name'])
            $this->validateIosData($inputDict['data'], $inputDict['is_silent'], $inputDict);
    }

    public function validate($inputDict) {
        $inputDict = $this->removeEmptyKeys($inputDict);
        $this->validateTypes($inputDict);
        $this->validateMandatoryKeys($this->mandatory_keys, $inputDict);
        $this->validateFilters($inputDict);
        $this->validateData($inputDict);
        $this->validatePushType($inputDict['push_type']);
        $inputDict['app_name'] = $this->comm_app_name;
        return $inputDict;
    }
}
