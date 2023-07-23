<?php
/*
 * This file is part of the EpicPay package.
 *
 */
class EpicPay {
    private $MerchantLongId;  
    private $token;
    
    function __construct($MerchantLongId, $TerminalKey, $api_gateway_urlID) {
       
        $this->MerchantLongId = $MerchantLongId;        
        $this->TerminalKey = $TerminalKey;
        $this->ApiGatewayUrl  = $api_gateway_urlID . 'transactions';
    }
    private function request($method = 'POST', $url, $json = false) {
        $curl = curl_init();
        $header = [
            "Content-Type: application/vnd.api+json",
            "cache-control: no-cache"
        ];
     
            $header[] = "Authorization: Api-Key ". $this->TerminalKey;
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json?json_encode($json):"",
            CURLOPT_HTTPHEADER => $header,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
       
        if(!empty($this->token)){
            $this->token = '';
        }
        
        return $response;
    }
    function transaction($order_id,$amount,$currency,$email, $merchantID, $return_url, $referenceID) {
        try{
            
            $price_show = sprintf('%02.2f', $amount);
            $params = [
            'data' => [
                'attributes' =>
                [
                    'projectId' => $merchantID,
                    'paymentMethod' => 'card',
                    'currency' => $currency,
                    'amount' => $price_show, 
                    'referenceId'=> $referenceID,
                    'returnUrl'=> $return_url,
                    'email' => $email,
                ],
                'type' => 'charge',
                ],
            ];
            
            $response_json = $this->request('POST', $this->ApiGatewayUrl.'', $params);
           
            return $response_json;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
       }
    }
}