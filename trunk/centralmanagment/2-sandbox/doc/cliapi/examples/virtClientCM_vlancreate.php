<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapcliapi.php');

if($argc<2){
 echo("Usage: virtClientCM_vlancreate <vlan name>\n");
 echo("Example of usage: virtClientCM_vlancreate Management\n");
exit(0);
}

$netname = $argv[1];



$params = array('name'=>$netname);



/* Send a request to the server, and store its response in $response: */
$method = 'cli_vlancreate';

$response = $soapclient->call($method,$params,array('namespace'=> 'urn:soapCliController'));
// $ret = $soapclient->call($method,array($a));

if (is_a($response, 'PEAR_Error')) {
    echo $soapclient->getLastRequest() . "\n";
    echo 'Error: ' . $response->getMessage() . "\n";
} else {

	$response = json_decode($response,true);	

	if(!$response['success']){
		echo($response['error']);
		echo("\n");
		exit(0);
	}else print_r($response['response']);
		
}

echo("\n");

?>
