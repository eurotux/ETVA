<?php
class ServerDisk_rwRRA extends RRA
{    
   
    private $opts;
    private $step = 300;    
    static private $name = 'Disk r/w';
    private $title;
    

    function init_log()
    {


        
        $this->opts = array("--step",$this->step,
                            "DS:reads:COUNTER:600:U:U",
                            "DS:writes:COUNTER:600:U:U",
                            "RRA:AVERAGE:0.5:1:600", //daily (5 min average)
                            "RRA:AVERAGE:0.5:6:700", // weekly
                            "RRA:AVERAGE:0.5:24:775", // montlhy
                            "RRA:AVERAGE:0.5:288:797", // year
                            "RRA:MAX:0.5:1:600",
                            "RRA:MAX:0.5:6:700",
                            "RRA:MAX:0.5:24:775",
                            "RRA:MAX:0.5:288:797");     
        
    }

    function ServerDisk_rwRRA($node,$name,$disk,$init_rrd=true)
    {
        if(!$node || !$name || !$disk) return;
        
        $file = $node.'/'.$name.'__serverDisk_rw__'.$disk.'.rrd';
        $this->init_log();

        parent::RRA($file, $this->opts, $init_rrd);

        
    }
    

    static function getName(){
        return self::$name;
    }

    function getGraphImg($title,$graph_start,$graph_end)
    {

        if(empty($graph_end)) $graph_end = 'now';

        $initial_params = array('--start='.$graph_start,
                           '--end='.$graph_end,
                           '--title="'.$title.' - '.self::$name.'"');

        $defs = array('DEF:cdefa="'.$this->getFilepath().'":reads:AVERAGE',
                      'DEF:cdefb="'.$this->getFilepath().'":writes:AVERAGE'
                   //   'CDEF:cdefa=a,8,*',
                   //   'CDEF:cdefb=b,8,*'
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
          '--vertical-label="bytes"',
          'AREA:cdefa#EACC00FF:"FS reads"',
          'GPRINT:cdefa:LAST:" Current\:%8.2lf %s"',
          'GPRINT:cdefa:AVERAGE:"Average\:%8.2lf %s"',
          'GPRINT:cdefa:MAX:"Maximum\:%8.2lf %s\n"',
          'LINE1:cdefb#002A97FF:"FS writes"',
          'GPRINT:cdefb:LAST:"Current\:%8.2lf %s"',
          'GPRINT:cdefb:AVERAGE:"Average\:%8.2lf %s"',
          'GPRINT:cdefb:MAX:"Maximum\:%8.2lf %s\n"'


        );

        $default_opts = parent::getDefault_graph_opts();

        $params = array_merge($initial_params,$default_opts, $defs, $comments, $style );


        return $this->build_graph($params);



    }


    /*
     * xport data in xml
     */
    function xportData($graph_start,$graph_end,$step=null)
    {

        $defs = array('DEF:a="'.$this->getFilepath().'":reads:AVERAGE',
                      'DEF:b="'.$this->getFilepath().'":writes:AVERAGE'
                      );

        $xport = array('XPORT:a:"Avg reads"',
                      'XPORT:b:"Avg writes"'
                      );

        $params = array_merge($defs, $xport);

        return $this->build_data($graph_start,$graph_end,$step=null,$params);


    }
   
    

}
?>