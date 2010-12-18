<?
require_once 'SOAP/Client.php';


$soapclient = new SOAP_Client('http://10.10.20.116:8004/soapapi.php');

if($argc<2){
 echo("Usage: virtClientCM_vgcreate_interactive <host ip>\n");
 echo("Example of usage: virtClientCM_vgcreate_interactive 10.10.20.79\n");
exit(0);
}

$host = $argv[1];


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



fwrite(STDOUT, "Enter volume group name\n");
$vg = rtrim(fgets(STDIN),"\n");

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
       $selection = fgetc(STDIN);
   } while ( trim($selection) == '' );

   if($selection=='q') exit(0);
	
   if ( array_key_exists($selection,$pvs_alloc) ) {
	fwrite(STDOUT, "You picked {$pvs_alloc[$selection]['pv']}\n");

  }else exit(0);
  

  
  $list_pvs = array($pvs_alloc[$selection]['pv']);

  // $pvs = array($list_pvsid); // third arg should be ( /dev/sdb1 )


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
