<?php
$params = join('',file('php://stdin'));
passthru($params,$ret);
exit($ret);
?>
