<?php
class ServerMemoryRRA extends RRA
{

    private $opts;
    private $step = 300;
    static private $name = 'Memory usage';


    function init_log()
    {
        $this->opts = array("--step",$this->step,
                            "DS:mem_m:GAUGE:600:0:U",
                            "DS:mem_v:GAUGE:600:0:U",
                            "RRA:AVERAGE:0.5:1:600", //daily (5 min average)
                            "RRA:AVERAGE:0.5:6:700", // weekly
                            "RRA:AVERAGE:0.5:24:775", // montlhy
                            "RRA:AVERAGE:0.5:288:797", // year
                            "RRA:MAX:0.5:1:600",
                            "RRA:MAX:0.5:6:700",
                            "RRA:MAX:0.5:24:775",
                            "RRA:MAX:0.5:288:797");

    }

    function ServerMemoryRRA($node,$name){

        $file = $node.'/'.$name.'__serverMemory.rrd';
        $this->init_log();

        parent::RRA($file, $this->opts);


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

        $defs = array('DEF:a="'.$this->getFilepath().'":mem_m:AVERAGE',
                      'DEF:b="'.$this->getFilepath().'":mem_v:AVERAGE');

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
                      'AREA:a#FFC73BFF:"Swap":STACK ',
'GPRINT:a:LAST:"Current\:%8.2lf%s"  ',
'GPRINT:a:AVERAGE:"Average\:%8.2lf%s"  ',
'GPRINT:a:MAX:"Maximum\:%8.2lf%s\n" '

                    );



        $default_opts = parent::getDefault_graph_opts();

        $params = array_merge($initial_params,$default_opts, $defs, $comments, $style );


        return $this->build_graph($params);



    }

    function xportData($graph_start,$graph_end,$step=null)
    {






        $defs = array('DEF:m="'.$this->getFilepath().'":mem_m:AVERAGE',
                      'DEF:v="'.$this->getFilepath().'":mem_v:AVERAGE');

        $xport = array('XPORT:m:"Maximum memory"',
                       'XPORT:v:"Available memory"'
                        );


        $params = array_merge($defs, $xport);


        return $this->build_data($graph_start,$graph_end,$step=null,$params);



    }



}
?>