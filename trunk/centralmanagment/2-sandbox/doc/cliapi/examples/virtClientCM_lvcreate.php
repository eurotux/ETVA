<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapcliapi.php');

if($argc<5){
 echo("Usage: virtClientCM_lvcreate <host ip> <logical volume name> <volume group name> <size M>\n");
 echo("Example of usage: virtClientCM_lvcreate 10.10.20.79 teste black 1000\n");
 exit(0);
}

$host = $argv[1];

$lv = $argv[2];

$vg = $argv[3];

$size = $argv[4];



$params = array($host,$lv,$vg,$size);


/* Send a request to the server, and store its response in $response: */
$method = 'cli_lvcreate';

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
