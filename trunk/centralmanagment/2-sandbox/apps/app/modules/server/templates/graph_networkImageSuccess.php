<?php
foreach($networks as $network):
?>
<img src="<?php echo url_for("network/graphPNG?id=".$network->getId().
                    "&graph_start=".$graph_start."&graph_end=".$graph_end) ?>"/>
<?php
endforeach;
?>

 
