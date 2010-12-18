<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapcliapi.php');

if($argc<3){
 echo("Usage: virtClientCM_lvremove <host ip> <logical volume name>\n");
 echo("Example of usage: virtClientCM_lvremove 10.10.20.79 teste\n");
exit(0);
}

$host = $argv[1];

$lv = $argv[2];

$params = array($host,$lv);

/* Send a request to the server, and store its response in $response: */
$method = 'cli_lvremove';

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
