<?php
class ServerNetworkRRA extends RRA
{    
   
    private $opts;    
    private $step = 300;
    static private $name = 'Network usage';
    
    

    function init_log()
    {
        $this->opts = array("--step",$this->step,
                            "DS:input:COUNTER:600:U:U",
                            "DS:output:COUNTER:600:U:U",
                            "RRA:AVERAGE:0.5:1:600", //daily (5 min average)
                            "RRA:AVERAGE:0.5:6:700", // weekly
                            "RRA:AVERAGE:0.5:24:775", // montlhy
                            "RRA:AVERAGE:0.5:288:797", // year
                            "RRA:MAX:0.5:1:600",
                            "RRA:MAX:0.5:6:700",
                            "RRA:MAX:0.5:24:775",
                            "RRA:MAX:0.5:288:797");     
        
    }

    function ServerNetworkRRA($node,$name,$interface){

        $file = $node.'/'.$name.'__serverNetwork__'.$interface.'.rrd';
        $this->init_log();

        parent::RRA($file, $this->opts);

        
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
                           '--title="'.$title.'  '.self::$name.'"');
        
        $defs = array('DEF:a="'.$this->getFilepath().'":input:AVERAGE',
                      'DEF:b="'.$this->getFilepath().'":output:AVERAGE',
                      'CDEF:cdefa=a,8,*',
                      'CDEF:cdefb=b,8,*'
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
                      '--vertical-label="bytes per second"',
                      'AREA:a#EACC00FF:"Inbound"',
                      'GPRINT:cdefa:LAST:" Current\:%8.2lf %sbps"',
                      'GPRINT:cdefa:AVERAGE:"Average\:%8.2lf %sbps"',
                      'GPRINT:cdefa:MAX:"Maximum\:%8.2lf %sbps\n"',
                      'LINE1:b#002A97FF:"Outbound"',
                      'GPRINT:cdefb:LAST:"Current\:%8.2lf %sbps"',
                      'GPRINT:cdefb:AVERAGE:"Average\:%8.2lf %sbps"',
                      'GPRINT:cdefb:MAX:"Maximum\:%8.2lf %sbps\n"'
                      
                      
                    );


               $opts = array( '--start=-1h', '--vertical-label="Bytes second"',
                 'DEF:inoctets="'.$this->getFilepath().'":input:AVERAGE',
                 'DEF:outoctets="'.$this->getFilepath().'":output:AVERAGE',
                 'AREA:inoctets#00FF00:"In traffic"',
                 'LINE1:outoctets#0000FF:"Out traffic"',
                 'CDEF:inbits=inoctets,8,*',
                 'CDEF:outbits=outoctets',
                 'COMMENT:"  \n"',
                 'GPRINT:inbits:AVERAGE:"Avg In traffic\: %6.2lf %sbps"',
                 'COMMENT:"  \n"',
                 'GPRINT:inbits:MAX:"Max In traffic\: %6.2lf %sbps"',
                 'GPRINT:outbits:AVERAGE:"Avg Out traffic\: %6.2lf %sbps"',
                 'COMMENT:"  \n"',
                 'GPRINT:outbits:MAX:"Max Out traffic\: %6.2lf %sbps"'
               );
//
//                    AREA:a#00CF00FF:"Inbound"  \
//GPRINT:a:LAST:" Current\:%8.2lf %s"  \
//GPRINT:a:AVERAGE:"Average\:%8.2lf %s"  \
//GPRINT:a:MAX:"Maximum\:%8.2lf %s\n"  \
//LINE1:b#002A97FF:"Outbound"  \
//GPRINT:b:LAST:"Current\:%8.2lf %s"  \
//GPRINT:b:AVERAGE:"Average\:%8.2lf %s"  \
//GPRINT:b:MAX:"Maximum\:%8.2lf %s\n"


        $default_opts = parent::getDefault_graph_opts();

        $params = array_merge($initial_params,$default_opts, $defs, $comments, $style );


        return $this->build_graph($params);



    }
    
    function xportData($graph_start,$graph_end,$step=null)
    {
        

        
        $defs = array('DEF:inoctets="'.$this->getFilepath().'":input:AVERAGE',
                      'DEF:outoctets="'.$this->getFilepath().'":output:AVERAGE'
                      );

        $xport = array('XPORT:inoctets:"Avg In traffic"',
                      'XPORT:outoctets:"Avg Out traffic"'
                      );
      
       

        
        $params = array_merge($defs, $xport);


        return $this->build_data($graph_start,$graph_end,$step=null,$params);



    }


   
    

}
?>