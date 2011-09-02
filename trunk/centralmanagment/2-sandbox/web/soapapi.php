<?php
/* Include PEAR::SOAP's SOAP_Server class: */
require_once('SOAP/Server.php');
require_once('SOAP/Disco.php');
require_once('SOAP/Server.php');

require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'soap', true);


sfContext::createInstance($configuration);

$webservice = new soapController();

/* Create a new SOAP server using PEAR::SOAP's SOAP_Server class: */
$server = new SOAP_Server();

/* Register this instance to the server class: */
$server->addObjectMap($webservice,'urn:soapController');

// set the path finder class as default responder for the WSDL class
// $server->addObjectMap($webservice,'http://schemas.xmlsoap.org/soap/envelope/');


// start serve

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']=='POST') {

     $server->service($HTTP_RAW_POST_DATA);

} else {

     $disco = new SOAP_DISCO_Server($server,'soapController');

     header("Content-type: text/xml");

     if (isset($_SERVER['QUERY_STRING']) && strcasecmp($_SERVER['QUERY_STRING'],'wsdl') == 0) {

         // show only the WSDL/XML output if ?wsdl is set in the address bar
         echo $disco->getWSDL();

     } else {

         echo $disco->getDISCO();

     }

}

