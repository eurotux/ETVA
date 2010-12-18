<?php
class ServerMemoryUsageRRA extends RRA
{    
   
    private $opts;    
    private $step = 300;
    private $node_name;
    private $server_name;
    static private $name = 'Memory usage';
    

    function init_log()
    {
        $this->opts = array("--step",$this->step,
                            "DS:mem_per:GAUGE:600:U:U",
                            "RRA:AVERAGE:0.5:1:600", //daily (5 min average)
                            "RRA:AVERAGE:0.5:6:700", // weekly
                            "RRA:AVERAGE:0.5:24:775", // montlhy
                            "RRA:AVERAGE:0.5:288:797", // year
                            "RRA:MAX:0.5:1:600",
                            "RRA:MAX:0.5:6:700",
                            "RRA:MAX:0.5:24:775",
                            "RRA:MAX:0.5:288:797");     
        
    }

    function ServerMemoryUsageRRA($node,$name)
    {
        if(!$node || !$name) return;
        $this->node_name = $node;
        $this->server_name = $name;
        
        $file = $node.'/'.$name.'__serverMemoryUsage.rrd';
        $this->init_log();

        parent::RRA($file, $this->opts);

        
    }

    

    static function getName(){
        return self::$name;
    }


    function xportData($graph_start,$graph_end,$step=null)
    {



        $defs = array('DEF:mem="'.$this->getFilepath().'":mem_per:AVERAGE');

        $xport = array('XPORT:mem:"Memory Utilization"');


        $params = array_merge($defs, $xport);


        return $this->build_data($graph_start,$graph_end,$step=null,$params);



    }


    function xportUsageData($graph_start,$graph_end,$step=null)
    {


        $server_memory_buffers = new ServerMemory_buffersRRA($this->node_name,$this->server_name);
           // $return = $server_memory_buffers_rra->update($server_memory_buffers);

        $server_memory_swap = new ServerMemory_swapRRA($this->node_name,$this->server_name);



        $defs = array('DEF:mem_b="'.$server_memory_buffers->getFilepath().'":mem_buffers:AVERAGE',
                      'DEF:mem_s="'.$server_memory_swap->getFilepath().'":mem_swap:AVERAGE');

        $xport = array('XPORT:mem_b:"Memory buffers"',
                       'XPORT:mem_s:"Memory swap"'
                        );


        $params = array_merge($defs, $xport);


        return $this->build_data($graph_start,$graph_end,$step=null,$params);



    }

    /*
     * returns PNG image data
     */
    function getGraphImg($title,$graph_start,$graph_end)
    {

        if(empty($graph_end)) $graph_end = 'now';

        $initial_params = array('--start='.$graph_start,
                           '--end='.$graph_end,
                           '--title="'.$title.'  '.self::$name.'"');

        $defs = array('DEF:a="'.$this->getFilepath().'":mem_per:AVERAGE');

        $comments = array();

        $start_date_string = $end_date_string = '';
        if(is_numeric($graph_start)){
            $start_date_string = date('d/m/Y H:i',$graph_start);
            $start_date_string = str_replace(':','\:',$start_date_string);
        }

        if(is_numeric($graph_end)){

            $end_date_string = date('d/m/Y H:i',$graph_end);
            $end_date_string = str_replace(':','\:',$end_date_string);
        }

        if($start_date_string && $end_date_string)
        $comments = array('COMMENT:"From '.$start_date_string.' To '.addslashes($end_date_string).'\c"',
                        'COMMENT:"  \n"');

        $style = array(
                      '--vertical-label="percent"',
                      'AREA:a#EACC00FF:"Mem %"',
                      'GPRINT:a:LAST:" Current\:%8.2lf%s"',
                      'GPRINT:a:AVERAGE:"Average\:%8.2lf%s"',
                      'GPRINT:a:MAX:"Maximum\:%8.2lf%s\n"'

                    );



        $default_opts = parent::getDefault_graph_opts();

        $params = array_merge($initial_params,$default_opts, $defs, $comments, $style );


        return $this->build_graph($params);



    }


    function getGraphUsageImg($title,$graph_start,$graph_end)
    {

        if(empty($graph_end)) $graph_end = 'now';

        $initial_params = array('--start='.$graph_start,
                           '--end='.$graph_end,
                           '--title="'.$title.'  '.self::$name.'"');

        $server_memory_buffers = new ServerMemory_buffersRRA($this->node_name,$this->server_name);
        $server_memory_swap = new ServerMemory_swapRRA($this->node_name,$this->server_name);

        $defs = array('DEF:b="'.$server_memory_buffers->getFilepath().'":mem_buffers:AVERAGE',
                      'DEF:s="'.$server_memory_swap->getFilepath().'":mem_swap:AVERAGE');

        $comments = array();

        $start_date_string = $end_date_string = '';
        if(is_numeric($graph_start)){
            $start_date_string = date('d/m/Y H:i',$graph_start);
            $start_date_string = str_replace(':','\:',$start_date_string);
        }

        if(is_numeric($graph_end)){

            $end_date_string = date('d/m/Y H:i',$graph_end);
            $end_date_string = str_replace(':','\:',$end_date_string);
        }

        if($start_date_string && $end_date_string)
        $comments = array('COMMENT:"From '.$start_date_string.' To '.addslashes($end_date_string).'\c"',
                        'COMMENT:"  \n"');

        $style = array(
                      '--vertical-label="bytes"',
                      'AREA:b#EACC00FF:"Free"',
                      'GPRINT:b:LAST:" Current\:%8.2lf%s"',
                      'GPRINT:b:AVERAGE:"Average\:%8.2lf%s"',
                      'GPRINT:b:MAX:"Maximum\:%8.2lf%s\n"',
                      'AREA:s#FFC73BFF:"Swap":STACK ',
'GPRINT:s:LAST:"Current\:%8.2lf%s"  ',
'GPRINT:s:AVERAGE:"Average\:%8.2lf%s"  ',
'GPRINT:s:MAX:"Maximum\:%8.2lf%s\n" '

                    );



        $default_opts = parent::getDefault_graph_opts();

        $params = array_merge($initial_params,$default_opts, $defs, $comments, $style );


        return $this->build_graph($params);



    }


   
    

}
?>