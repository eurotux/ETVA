<?php
class ServerDisk_rwspentRRA extends RRA
{    
   
    private $opts;
    private $step = 300;    
    static private $name = 'Disk r/w';
    private $title;
    

    function init_log()
    {


        // GAUGE
        $this->opts = array("--step",$this->step,
                            "DS:rspent:COUNTER:600:U:U",
                            "DS:wspent:COUNTER:600:U:U",
                            "RRA:AVERAGE:0.5:1:600", //daily (5 min average)
                            "RRA:AVERAGE:0.5:6:700", // weekly
                            "RRA:AVERAGE:0.5:24:775", // montlhy
                            "RRA:AVERAGE:0.5:288:797", // year
                            "RRA:MAX:0.5:1:600",
                            "RRA:MAX:0.5:6:700",
                            "RRA:MAX:0.5:24:775",
                            "RRA:MAX:0.5:288:797");     
        
    }

    function ServerDisk_rwspentRRA($node,$name,$disk,$init_rrd=true)
    {
        if(!$node || !$name || !$disk) return;
        
        $file = $node.'/'.$name.'__serverDisk_rwspent__'.$disk.'.rrd';
        $this->init_log();

        parent::RRA($file, $this->opts, $init_rrd);

        
    }
    

    static function getName(){
        return self::$name;
    }

    /*
     * returns PNG image data
     */
    function getGraphImg($title,$graph_start,$graph_end)
    {


        if(empty($graph_end)) $graph_end = 'now';

        $initial_params = array('--start='.$graph_start,
                           '--end='.$graph_end,
                           '--title="'.$title.' - '.self::$name.'"');

        $defs = array('DEF:a="'.$this->getFilepath().'":rspent:AVERAGE',
                      'DEF:b="'.$this->getFilepath().'":wspent:AVERAGE'
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
                      'AREA:a#EACC00FF:"FS reads"',
                      'GPRINT:a:LAST:"Current\:%8.0lf"',
                      'GPRINT:a:AVERAGE:"Average\:%8.0lf\n"',
                      'AREA:b#EA8F00FF:"FS writes":STACK',
                      'GPRINT:b:LAST:"Current\:%8.0lf"',
                      'GPRINT:b:AVERAGE:"Average\:%8.0lf\n"'
                    );

        $default_opts = parent::getDefault_graph_opts();

        $params = array_merge($default_opts,$initial_params, $defs, $comments, $style );


        return $this->build_graph($params);



    }


    /*
     * xport data in xml
     */
    function xportData($graph_start,$graph_end,$step=null)
    {

        $defs = array('DEF:a="'.$this->getFilepath().'":rspent:AVERAGE',
                      'DEF:b="'.$this->getFilepath().'":wspent:AVERAGE'
                      );

        $xport = array('XPORT:a:"Avg read time"',
                      'XPORT:b:"Avg write time"'
                      );

        $params = array_merge($defs, $xport);

        return $this->build_data($graph_start,$graph_end,$step=null,$params);


    }
   
    

}
?>