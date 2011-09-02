<?php
foreach($disks as $disk):
?>
<img src="<?php echo url_for("logicalvol/graphDiskRWPNG?id=".$disk->getId().
                    "&graph_start=".$graph_start."&graph_end=".$graph_end) ?>"/>
<?php
endforeach;
?>
