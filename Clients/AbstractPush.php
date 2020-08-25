<?php

namespace Practo\CommunicatorBundle\Clients;

use Practo\CommunicatorBundle\Queue\AbstractQueue as Queue;
use Practo\CommunicatorBundle\Clients\Helper;
use Practo\CommunicatorBundle\Exceptions\PushValidationException;
use praccomm;

/**
 * Abstract class for sending push notifications
 */
abstract class AbstractPush extends Base
{
    private $_service;
    private $_queue;
    private $push_relations = array('=', '>', '<', '>=', '<=');
    private $push_relation_keys = array('app_version', 'last_active');
    private $comm_app_name = '';
    private $mandatory_keys = array(
        'service', 'reference_id', 'reference_type', 'data', 'push_type'
    );

    abstract protected function validatePushType($pushType);
    abstract protected function validateFilters($inputDict);


    public function __construct($queue, $service)
    {
        $this->_queue = $queue;
        $this->_service = Helper::getValidAppName($service);
    }

    protected function sendPush($queueName, $jsonifiedJob) {
        $this->_queue->setDomain($this->_domain);
        return $this->_queue
                    ->setQueueName($queueName)
                    ->sendMessage($jsonifiedJob);
    }

    public function isAssoc($arr)
    {
        if (gettype($arr) !== 'array') return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function removeEmptyKeys($object) {
        $updated_object = array();
        foreach ($object as $key => $value) {
            if (in_array($value, array('', null, array())) && $value !== false)
                continue;
            else if ($this->isAssoc($value)) {
                $updated_val = $this->removeEmptyKeys($value);
                if (in_array($updated_val, array('', null, array())))
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
        if (array_key_exists('ttl', $inputDict))
            $pushObject->ttl = $inputDict['ttl'];
        if (array_key_exists('max_delivery_attempts', $inputDict))
            $pushObject->max_delivery_attempts = $inputDict['max_delivery_attempts'];
        if (array_key_exists('expires_at', $inputDict))
            $pushObject->expires_at = $inputDict['expires_at'];
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
                if (!in_array('value', array_keys($inputDict[$key])) ||
                    !in_array('relation', array_keys($inputDict[$key])))
                    throw new PushValidationException("Invalid keys in" . $key);
                if (!in_array($inputDict[$key]['relation'], $this->push_relations))
                    throw new PushValidationException("Invalid Relation " . $inputDict[$key]['relation']);
            }
        }
    }

    public function validateMandatoryKeys($mandatoryKeys, $inputDict) {
        $missing_keys = array_diff($mandatoryKeys, array_keys($inputDict));
        if (count($missing_keys))
            throw new PushValidationException("Missing Mandatory Keys: " . implode(', ', $missing_keys));
        foreach ($inputDict as $key => $value) {
            if (in_array($value, array('', null, array())) && $value !== false)
                throw new PushValidationException("Key: " . $key . "Cannot be empty");
        }
    }

    public function validateTypes($inputDict) {
        $pdt = new praccomm\PushDataTypes;
        foreach ($pdt->types as $key => $value) {
            if (in_array($key, array_keys($inputDict)) && (gettype($inputDict[$key]) !== $value))
                throw new PushValidationException("Invalid Type for key: " . $key);
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
            if (count(array_diff(array_keys($iosData), array('custom'))) > 0) {
                throw new PushValidationException("Silent Push can only have custom keys");
            }
        }
        else if (count(array_diff(array_keys($iosData), array('custom'))) == 0) {
            throw new PushValidationException("Non Silent Push data should have keys other than custom");
        }
    }

    public function validateData($inputDict) {
        if ($inputDict['is_silent'] === null && $inputDict['is_silent'] !== false)
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
        return $inputDict;
    }
}
