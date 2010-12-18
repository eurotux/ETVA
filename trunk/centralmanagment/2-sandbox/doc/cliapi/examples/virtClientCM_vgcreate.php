<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapcliapi.php');

if($argc<4){
 echo("Usage: virtClientCM_vgcreate <host ip> <volume group name> <physical volumes>\n");
 echo("Example of usage: virtClientCM_vgcreate 10.10.20.79 black /dev/sdb1,/dev/sdb2\n");
exit(0);
}

$host = $argv[1];

$vg = $argv[2]; 

$pvs = $argv[3];

$list_pvs = explode(',',$pvs);


$params = array($host,$vg,$list_pvs);



/* Send a request to the server, and store its response in $response: */
$method = 'cli_vgupdate';

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
