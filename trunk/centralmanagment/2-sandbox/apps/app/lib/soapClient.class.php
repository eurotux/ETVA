<?php
require_once 'SOAP/Client.php';

class soapClient extends SOAP_CLient
{

    function soapClient($host,$port)
    {
        return parent::SOAP_Client($host,false,$port);
    }
    

    function processSoap($method,$params)
    {
        $response = $this->call($method,$params);

        if ($response instanceOf PEAR_Error)
        {           
            $error = $response->getMessage();
            $fault = (array) $response->getFault();
            $faultcode = $fault['faultcode'];            
            $error_detail = $response->getDetail();            
            $error_resp = array('success'=>false,'error'=>$error,'info'=>$error_detail,'faultcode'=>$faultcode);
            return $error_resp;
        } // end error response

        $response_resp = array('success'=>true,'response'=>$response);

        return $response_resp;

   }


}
?>