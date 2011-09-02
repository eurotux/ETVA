<?php
class NodeLoadRRA extends RRA
{

    private $opts;
    private $step = 300;
    static private $name = 'CPU Load';


    function init_log()
    {
        $this->opts = array("--step",$this->step,
//"--start",0,
                            "DS:load_1min:GAUGE:600:0:500",
                            "DS:load_5min:GAUGE:600:0:500",
                            "DS:load_15min:GAUGE:600:0:500",
                            "RRA:AVERAGE:0.5:1:600", //daily (5 min average)
                            "RRA:AVERAGE:0.5:6:700", // weekly
                            "RRA:AVERAGE:0.5:24:775", // montlhy
                            "RRA:AVERAGE:0.5:288:797", // year
                            "RRA:MAX:0.5:1:600",
                            "RRA:MAX:0.5:6:700",
                            "RRA:MAX:0.5:24:775",
                            "RRA:MAX:0.5:288:797"
                //            "RRA:AVERAGE:0.5:1:2",
                         //   "RRA:AVERAGE:0.5:12:24",  // 1 row equals 1 hour (12x300) archive for 1 day (1hx24)
                  //          "RRA:AVERAGE:0.5:288:31" // 1 row equals 24 hours (288x300) archive for one month (1hx24)

            );

    }

    function NodeLoadRRA($node,$init_rrd=true){

        $file = $node.'/cpuLoad.rrd';
        $this->init_log();

        parent::RRA($file, $this->opts, $init_rrd);

    }


    static function getName(){
        return self::$name;
    }

    /*
     * xport xml data
     */
    function xportData($graph_start,$graph_end,$step=null)
    {



        $defs = array('DEF:load_1min="'.$this->getFilepath().'":load_1min:AVERAGE',
                      'DEF:load_5min="'.$this->getFilepath().'":load_5min:AVERAGE',
                      'DEF:load_15min="'.$this->getFilepath().'":load_15min:AVERAGE'
                      );

        $xport = array('XPORT:load_1min:"Avg 1min processes"',
                       'XPORT:load_5min:"Avg 5min processes"',
                       'XPORT:load_15min:"Avg 15min processes"'
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
                           '--title="'.$title.'"');

        $defs = array('DEF:a="'.$this->getFilepath().'":load_1min:AVERAGE',
                      'DEF:b="'.$this->getFilepath().'":load_5min:AVERAGE',
                      'DEF:c="'.$this->getFilepath().'":load_15min:AVERAGE',
                      'CDEF:cdefg=TIME,1251111204,GT,a,a,UN,0,a,IF,IF,TIME,1251111204,GT,b,b,UN,0,b,IF,IF,TIME,1251111204,GT,c,c,UN,0,c,IF,IF,+,+'
                      );

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
                      '--vertical-label="processes in the run queue"',


                      'AREA:a#EACC00FF:"1 Minute Average"',
                      'GPRINT:a:LAST:" Current\:%8.2lf\n"',
                      'AREA:b#EA8F00FF:"5 Minute Average":STACK',
                      'GPRINT:b:LAST:" Current\:%8.2lf\n"  ',
                      'AREA:c#FF0000FF:"15 Minute Average":STACK ',
                      'GPRINT:c:LAST:"Current\:%8.2lf\n"  ',
                      'LINE1:cdefg#000000FF:"Total"'

                    );




        $default_opts = parent::getDefault_graph_opts();

        $params = array_merge($initial_params,$default_opts, $defs, $comments, $style );


        return $this->build_graph($params);



    }

    



}


?>