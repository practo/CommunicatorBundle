<?php

namespace Practo\CommunicatorBundle\Clients;

/**
* Communicator Client with mandatory service nonce and token based auth.
*
*/

class CommunicatorClientV2 {
    private $_communicator_base_uri;
    private $_communicator_api_key;
    private $_service;

    public function __construct($communicator_base_uri, $communicator_api_key, $service) {
        $this->_communicator_base_uri = $communicator_base_uri;
        $this->_communicator_api_key = $communicator_api_key;
        $this->_service = $service;
    }

    protected function generateNonce(){
        return time() . '|' . substr(str_shuffle(
            str_repeat('abcdefghijklmnopqrstuvwxyz0123456789', 3)), 0, 20);
    }

    public function get($endpoint, $request_params, $service_headers) {
        $this->validateServiceHeaders($service_headers);
        $nonce = $this->generateNonce();
        $communicatorBaseUri = $this->_communicator_base_uri;
        $api_secret_key = $this->_communicator_api_key;
        $auth_token = hash('sha256', $api_secret_key.$nonce);

        $ch = curl_init();
        $api_url = $communicatorBaseUri.$endpoint . "?" . http_build_query($request_params);

        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $headers = array(
            'nonce: '.$nonce,
            'X-AUTH-TOKEN: '.$auth_token,
            'service:'.$this->_service
        );
        if (!empty($service_headers)) {
            foreach($service_headers as $key => $value) {
                $headers[] =  $key . ":" . $value;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $featureSet = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMessage = null;

        if (false !== $featureSet) {
            if ($responseCode === 200) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $respone_headers = substr($featureSet, 0, $header_size);
                $respone_headers = $this->getHeadersFromCurlResponse($respone_headers);
                $respone_body = json_decode(substr($featureSet, $header_size), True);
                
                return array(
                    "response_headers" => $respone_headers,
                    "response_body" => $respone_body
                );

            } else {
                $featureSet = json_decode($featureSet, True);
                $errorMessage = array();
                $errorMessage['response_code'] = $responseCode;
                $errorMessage['message'] = $featureSet['message'];
            }
        }
        else{
            $errorMessage = curl_error($ch);
        }

        curl_close($ch);
        if($errorMessage){
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }
            throw new \Exception($errorMessage);
        }
    }

    public function post($endpoint, $request_params, $service_headers) {
        $this->validateServiceHeaders($service_headers);
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
            'Content-type: application/json',
            'service:'.$this->_service
        );
        if (!empty($service_headers)) {
            foreach($service_headers as $key => $value) {
                $headers[] =  $key . ":" . $value;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_params));
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $featureSet = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMessage = null;

        if (false !== $featureSet) {
            if ($responseCode === 200) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $respone_headers = substr($featureSet, 0, $header_size);
                $respone_headers = $this->getHeadersFromCurlResponse($respone_headers);
                $respone_body = json_decode(substr($featureSet, $header_size), True);
                return array(
                    "response_headers" => $respone_headers,
                    "response_body" => $respone_body
                );

            } else {
                $featureSet = json_decode($featureSet, True);
                $errorMessage = array();
                $errorMessage['response_code'] = $responseCode;
                $errorMessage['message'] = $featureSet['message'];
            }
        }
        else{
            $errorMessage = curl_error($ch);
        }

        curl_close($ch);
        if($errorMessage){
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }
            throw new \Exception($errorMessage);
        }
    }

    private function validateServiceHeaders($service_headers)
    {
        $isValidHeader = false;
        if (isset($service_headers['Service-Nonce'])
            && isset($service_headers['User-Agent'])) {
            $isValidHeader = true;
        } else if (isset($service_headers['token'])) {
            $isValidHeader = true;
        }
        $errorMessage = "Missing Headers: Service-Nonce with User-Agent OR token  must be present";
        if (!$isValidHeader) {
            throw new \Exception($errorMessage);
        }
    }

    private function getHeadersFromCurlResponse($headerContent)
    {
        $headers = array();
        $arrRequests = explode("\r\n\r\n", $headerContent);

        for ($index = 0; $index < count($arrRequests) -1; $index++) {
            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else
                {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return $headers;
    }
}
