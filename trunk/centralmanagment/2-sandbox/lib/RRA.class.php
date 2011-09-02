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
                             
                                            

    public function delete($remove_dir = false)
    {
        $deleted = true;
        if($this->filepath)
        {
            if (!unlink($this->filepath))
                $deleted = false;            

        } else {
            $deleted = false;
        }

        if($remove_dir)
        {
            $dir = dirname($this->filepath);
            if (is_dir($dir)){

                $objects = scandir($dir);
                // remove files...if any
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..")
                        if (filetype($dir."/".$object) != "dir") unlink($dir."/".$object);
                }

                $deleted = rmdir($dir);
            }else $deleted = false;
        }

        return $deleted;
    }
    
    function init_rrdcreate($opts)
    {        
              
        $dir = dirname($this->filepath);
        if (!is_dir($dir))
        {
            
            $hasDir = mkdir($dir, 0777, true);
            if(!$hasDir) throw new sfException(sprintf('Unable to create directory "%s" for writing.', $dir));
        }

        $fileExists = file_exists($this->filepath);
        
        if (!is_writable($dir) || ($fileExists && !is_writable($this->filepath)))      
        {
            
            throw new sfFileException(sprintf('Unable to open the rra file "%s" for writing.', $this->file));        

        }


        if (!$fileExists){
            $command = "rrdtool create '".$this->filepath."' ".implode(" ",$opts);
            $msg = "rrdtool create '".$this->file."'";
            system($command,$res);
            // if $res==1 means that command failed
            if($res) throw new sfException(sprintf('Unable to execute "%s" ', $msg));
            
            
        }

        
    }    

    function RRA($file,$opts,$init=true)
    {
       
        $this->file = $file;
        $this->filepath = sfConfig::get("app_rra_dir").'/'.$this->file;
        if($init) $this->init_rrdcreate($opts);
        
    }    

    function update($values)
    {
        
        $data_string = implode(':',$values);
        $data_string = "N:".$data_string;

        system("rrdupdate  ".$this->filepath." ".$data_string,$res);

        return $res;

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