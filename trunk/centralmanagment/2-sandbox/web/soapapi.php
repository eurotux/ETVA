<?php
/* Include PEAR::SOAP's SOAP_Server class: */
require_once('SOAP/Server.php');
require_once('SOAP/Disco.php');


define('SF_ROOT_DIR',    realpath(dirname(__FILE__).'/..'));
define('SF_APP',         'app');
define('SF_ENVIRONMENT', 'soap');
define('SF_DEBUG',       true);
 require_once 'SOAP/Server.php';
//require_once(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');
require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'prod', false);



sfContext::createInstance($configuration);
// ->dispatch();


// require_once (SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps/app/lib/mySoapServer.class.php');

// require_once (SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps/app/lib/mySoapController.class.php');




$webservice = new soapController();

/* Create a new SOAP server using PEAR::SOAP's SOAP_Server class: */
    $server = new SOAP_Server();
/* Create an instance of our class: */
  //  $webservice = new mySoapServer();
    /* Register this instance to the server class: */
    // $server->addObjectMap($soaphelloserver,array('namespace'=> 'urn:helloworld'));
    $server->addObjectMap($webservice,'urn:soapController');


// $server = new Soap_Server(); //this file is defined in the next step
// $server->addObjectMap($soap_c,array('namespace'=> 'urn:soapapi'));

// $server->setClass("mySoapController"); // more to come on this one
// $server->handle();



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


/* The following line starts the actual service: */
   //  $server->service($HTTP_RAW_POST_DATA);
