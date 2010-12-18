<?

require_once 'SOAP/Client.php';

$addr = "10.10.20.67";
$port = 7001;
$proto = "tcp";
$host = "" . $proto . "://" . $addr . ":" . $port;
$soapclient = new SOAP_Client($host,false,$port);

$a = array("nil"=>"true");

$method = "listDomains";
$ret = $soapclient->call($method,array($a));

if (is_a($ret, 'PEAR_Error')) {
    echo $soapclient->getLastRequest() . "\n";
    echo 'Error: ' . $ret->getMessage() . "\n";
} else {
    print_r($ret);
}

?>
