<?php
class RRA
{
    
    private $filepath;
    private $file;

    static private $graph_opts = array('--imgformat=PNG',
                                '--rigid',
                                '--base=1000',
                                '--height=120',
                                '--width=500',
                                '--alt-autoscale-max',
                                '--lower-limit=0',
                            //    '--units-exponent=0',
                                '--slope-mode',
                                '--font TITLE:12:',
                                '--font AXIS:8:',
                                '--font LEGEND:8:',
                                '--font UNIT:8:');
                             
                                            
 
    
    function init_rrdcreate($opts)
    {

        $this->filepath = sfConfig::get("app_rra_dir").$this->file;
        
        
        $dir = dirname($this->filepath);
        if (!is_dir($dir))
        {
            mkdir($dir, 0777, true);
        }

        $fileExists = file_exists($this->filepath);
        if (!is_writable($dir) || ($fileExists && !is_writable($this->filepath)))
        {
            throw new sfFileException(sprintf('Unable to open the log file "%s" for writing.', $this->filepath));
        }


        if (!$fileExists) system("rrdtool create ".$this->filepath." ".implode(" ",$opts),$res);

        
    }    

    function RRA($file,$opts)
    {
       
        $this->file = $file;
        $this->init_rrdcreate($opts);
        
    }    

    function update($values)
    {
        
        $data_string = implode(':',$values);
        $data_string = "N:".$data_string;

        system("rrdupdate  ".$this->filepath." ".$data_string,$res);

        return $res."d";

    }

    function getFile(){
        return $this->file;
    }

    function getFilepath(){
        return $this->filepath;
    }



    function getDefault_graph_opts(){

        return self::$graph_opts;

    }

    /*
     * return bynary graph generated from rrdtool graph
     */
    function build_graph($params)
    {
                
       $graph_string = implode(" ",$params);

       $fp = popen('rrdtool graph - '.$graph_string, "r");

       $content = stream_get_contents($fp);

       pclose($fp);

       return $content;


    }

    function build_data($graph_start,$graph_end,$step=null,$params){



        $initial_params = array();
        if(!empty($graph_start)){
            $graph_start = '--start='.$graph_start;
            $initial_params['start'] = $graph_start;
        }

        if(!empty($graph_end)){
            $graph_end = '--end='.$graph_end;
            $initial_params['end'] = $graph_end;
        }

        if(!empty($step)){
            $graph_step = '--step='.$step;
            $initial_params['step'] = $graph_step;
        }


        $allparams = array_merge($initial_params, $params);

        


        $params_string = implode(" ",$allparams);
                
        $fp = popen('rrdtool xport --enumds '.$params_string, "r");
        return fpassthru($fp);
    }
    

}
?>