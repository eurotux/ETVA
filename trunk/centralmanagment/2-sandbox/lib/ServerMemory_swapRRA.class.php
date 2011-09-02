<?php
class ServerMemory_swapRRA extends RRA
{    
   
    private $opts;
    private $step = 300;
    

    function init_log()
    {
        $this->opts = array("--step",$this->step,
                            "DS:mem_swap:GAUGE:600:0:U",
                            "RRA:AVERAGE:0.5:1:600", //daily (5 min average)
                            "RRA:AVERAGE:0.5:6:700", // weekly
                            "RRA:AVERAGE:0.5:24:775", // montlhy
                            "RRA:AVERAGE:0.5:288:797", // year
                            "RRA:MAX:0.5:1:600",
                            "RRA:MAX:0.5:6:700",
                            "RRA:MAX:0.5:24:775",
                            "RRA:MAX:0.5:288:797");     
        
    }

    function ServerMemory_swapRRA($node,$name,$init_rrd=true){

        $file = $node.'/'.$name.'__serverMemory_swap.rrd';        
        $this->init_log();

        parent::RRA($file, $this->opts, $init_rrd);

        
    }
   
    

}
?>