<?php

namespace Practo\CommunicatorBundle\Clients;

class CommunicatorClient {
    private $_communicator_base_uri;
    private $_communicator_api_key;

    public function __construct($communicator_base_uri, $communicator_api_key) {
        $this->_communicator_base_uri = $communicator_base_uri;
        $this->_communicator_api_key = $communicator_api_key;
    }

    public function get($endpoint, $request_params) {
        $communicatorBaseUri = $this->_communicator_base_uri;
        $api_secret_key = $this->_communicator_api_key;

        $api_url = $communicatorBaseUri.$endpoint;
        $api_url = $api_url."?data=".json_encode($request_params);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1 );
        $headers = array(
            'X-Api-Key: '.$api_secret_key, 
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $featureSet = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMessage = '';

        if (false !== $featureSet) {
            $featureSet = json_decode($featureSet, True);
            if($responseCode === 200){
                $result = $featureSet['result'];
                return $result;
            }
            else{
                $errorMessage = $responseCode.': '. $featureSet['message'];
            }
        }
        else{
            $errorMessage = curl_error($ch);
        }

        curl_close($ch);
        if($errorMessage){
            throw new Exception($errorMessage);
        }
    }

    public function post($endpoint, $request_params) {
        $communicatorBaseUri = $this->_communicator_base_uri;
        $api_secret_key = $this->_communicator_api_key;

        $api_url = $communicatorBaseUri.$endpoint;
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1 );
        $headers = array(
            'X-Api-Key: '.$api_secret_key,
            'Content-type: application/json'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = array();
        $data['data'] = $request_params;
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $featureSet = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMessage = '';

        if (false !== $featureSet) {
            $featureSet = json_decode($featureSet, True);
            if($responseCode === 200){
                $result = $featureSet['result'];
                return $result;
            }
            else{
                $errorMessage = $responseCode.': '. $featureSet['message'];
            }
        }
        else{
            $errorMessage = curl_error($ch);
        }

        curl_close($ch);
        if($errorMessage){
            throw new Exception($errorMessage);
        }
    }

}
