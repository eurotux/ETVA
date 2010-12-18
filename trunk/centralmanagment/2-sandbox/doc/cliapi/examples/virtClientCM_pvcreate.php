<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapcliapi.php');

if($argc<3){
    echo("Usage: virtClientCM_pvcreate <host ip> <device>\n");
    echo("Example of usage: virtClientCM_pvcreate 10.10.20.79 /dev/sdb1\n");
    exit(0);
}

$host = $argv[1];
$dev = $argv[2];

$params = array(
		'host'=>$host,		
		'dev'=>$dev
        );

/* Send a request to the server, and store its response in $response: */
$method = 'cli_pvcreate';

$response = $soapclient->call($method,$params,array('namespace'=> 'urn:soapCliController'));


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
