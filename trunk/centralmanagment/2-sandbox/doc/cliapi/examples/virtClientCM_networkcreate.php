<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapcliapi.php');

if($argc<4){
 echo('Usage: virtClientCM_networkreplace <host ip> <volume group name> <physical volumes>\n');
 echo('Example of usage: virtClientCM_networkreplace 10.10.20.79 teste "port=0,vlan=Management,mac=00:00:03:04:07:09"\n');
exit(0);
}

$host = $argv[1];

$server = $argv[2];

$networks_intfs = $argv[3];



$networks_intfs = explode(';',$networks_intfs);
$pieces = array();
$i = 0;

$build_nets = array();

foreach($networks_intfs as $intf){
    $pieces = explode(',',$intf);
    $build_intf = array();

    foreach($pieces as $item){
        
        $chunks = explode("=",$item);
        
        $build_intf[$chunks[0]] = $chunks[1];
    }

    $build_nets[$i] = $build_intf;

    $i++;
}


$params = array($host,$server,$build_nets);



/* Send a request to the server, and store its response in $response: */
$method = 'cli_networkreplace';

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
