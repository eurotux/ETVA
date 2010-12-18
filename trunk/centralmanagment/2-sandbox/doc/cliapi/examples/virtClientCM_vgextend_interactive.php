<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapapi.php');

if($argc<2){
 echo("Usage: virtClientCM_vgcreate_interactive <host ip>\n");
 echo("Example of usage: virtClientCM_vgcreate_interactive 10.10.20.79\n");
exit(0);
}

$host = $argv[1];

/*
 *
 * list volume groups
 *
 */

$params = array(
		'host'=>$host
        );
$method = 'cli_vgList';

$response = $soapclient->call($method,$params,array('namespace'=> 'urn:soapController'));
$response = json_decode($response,true);


if(!$response['success']){
	echo($response['error']);
	echo("\n");
	exit(0);
}

$vgs = $response['response'];


fwrite(STDOUT, "Pick volume group (enter the number and press return)\n");
fwrite(STDOUT, "Enter 'q' to quit\n");

// Display the choices
foreach ( $vgs as $choice => $vg ) {
		fwrite(STDOUT, "\t$vg[id] : $vg[vg]\n");
}

do {
       $selection_vg = fgetc(STDIN);
   } while ( trim($selection_vg) == '' );

   if($selection_vg=='q') exit(0);

   if ( array_key_exists($selection_vg,$vgs) ) {
	fwrite(STDOUT, "You picked {$vgs[$selection_vg]['vg']}\n");

  }else exit(0);

$vg = $vgs[$selection_vg]['vg'];
/*
 *
 * list physical volumes
 *
 */
$params = array(
		'host'=>$host
        );
$method = 'cli_pvListAllocatable';

$response = $soapclient->call($method,$params,array('namespace'=> 'urn:soapController'));
$response = json_decode($response,true);


if(!$response['success']){
	echo($response['error']);
	echo("\n");
	exit(0);
}


// get data from response
$pvs_alloc = $response['response'];


fwrite(STDOUT, "Pick physical volume (enter the number and press return)\n");
fwrite(STDOUT, "Enter 'q' to quit\n");

// Display the choices
foreach ( $pvs_alloc as $choice => $pv ) {
		fwrite(STDOUT, "\t$pv[id] : $pv[pv]\n");
}

// Loop until they enter 'q' for Quit
//do {
   // A character from STDIN, ignoring whitespace characters
   do {
       $selection_pv = fgetc(STDIN);
   } while ( trim($selection_pv) == '' );

   if($selection_pv=='q') exit(0);
	
   if ( array_key_exists($selection_pv,$pvs_alloc) ) {
	fwrite(STDOUT, "You picked {$pvs_alloc[$selection_pv]['pv']}\n");

  }else exit(0);
  

  
  $list_pvs = array($pvs_alloc[$selection_pv]['pv']);


  $params = array($host,$vg,$list_pvs);


  /* Send a request to the server, and store its response in $response: */
  $method = 'cli_vgupdate';

  $response = $soapclient->call($method,$params,array('namespace'=> 'urn:soapController'));

  if (is_a($response, 'PEAR_Error')) {
    		echo $soapclient->getLastRequest() . "\n";
    		echo 'Error: ' . $response->getMessage() . "\n";

  } else {

		$response = json_decode($response,true);
		
		if(!$response['success']){
			echo($response['error']);
			
		}else print_r($response['response']);
  		
  }
  echo("\n");
		
	
   

//} while ( $selection != 'q' );



?>
