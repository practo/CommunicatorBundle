<?php

namespace Practo\CommunicatorBundle\Clients;

class CommunicatorClient {
    private $_communicator_base_uri;
    private $_communicator_api_key;

    public function __construct($communicator_base_uri, $communicator_api_key) {
        $this->_communicator_base_uri = $communicator_base_uri;
        $this->_communicator_api_key = $communicator_api_key;
    }

    protected function generateNonce(){
        return time() . '|' . substr(str_shuffle(
            str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', 3)), 0, 20);
    }

    public function get($endpoint, $request_params) {
        $nonce = $this->generateNonce();
        $communicatorBaseUri = $this->_communicator_base_uri;
        $api_secret_key = $this->_communicator_api_key;
        $auth_token = hash('sha256', $api_secret_key.$nonce);

        $ch = curl_init();
        $api_url = $communicatorBaseUri.$endpoint . "?" . http_build_query($request_params);

        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1 );
        $headers = array(
            'nonce: '.$nonce,
            'X-AUTH-TOKEN: '.$auth_token
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
            throw new \Exception($errorMessage);
        }
    }

    public function post($endpoint, $request_params) {
        $nonce = $this->generateNonce();
        $communicatorBaseUri = $this->_communicator_base_uri;
        $api_secret_key = $this->_communicator_api_key;
        $auth_token = hash('sha256', $api_secret_key.$nonce);

        $api_url = $communicatorBaseUri.$endpoint;
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt ($ch , CURLOPT_RETURNTRANSFER , 1 );
        $headers = array(
            'nonce: '.$nonce,
            'X-AUTH-TOKEN: '.$auth_token,
            'Content-type: application/json'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_params));
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
            throw new \Exception($errorMessage);
        }
    }
}
