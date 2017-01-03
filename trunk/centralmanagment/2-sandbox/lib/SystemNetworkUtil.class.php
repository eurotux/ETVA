<?php
/**
 * class for manipuating system network data
 * 
 */
class SystemNetworkUtil
{
    private $networks;
    private $ntpservers;

    static function getDefaultIP(){
        return $_SERVER['SERVER_ADDR'];
    }

    public function getNetworks()
    {
        return $this->networks;
    }
    public function getNtpServers()
    {
        return $this->ntpservers;
    }

    /*
     * load CM networks. cm_management and lan
     */
    public function loadCMNetworks($devices)
    {
        $cm_networks = array('cm_management'=>$devices['cm_management']);
        if(isset($devices['lan'])) $cm_networks['lan'] = $devices['lan'];
        
        $this->loadSystemNetworks($cm_networks);

    }


    /**
     *
     * load network devices from system-config-network-cmd data to a SystemNetwork object
     * 
     */
    public function loadSystemNetworks($devices)
    {
        $dns_tmp = array();
        $matches_to_found = array();
        $this->networks = array();       

        $i = 0;
        foreach($devices as $type=>$device){
            $aux_network = new SystemNetwork();
            $aux_network->fromArray(array('if'=>$device));
            $this->networks[$device] = $aux_network;
    
            $matches_to_found[$i] = array('type'=>$type,'if'=>$device,'match'=>"/([\w\.]+\.(\w+))\.Device=$device/");
            $i++;
        }

        $path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."utils";
                    
        if(!($p = popen('echo /usr/sbin/system-config-network-cmd -e 2>&1 | sudo /usr/bin/php -f '.$path.'/sudoexec.php','r')))
        {
            throw new Exception('could not execute system-config-network-cmd');
            return false;
        }

        $aux = array();        
        $re_if = '';$re_dev = '';$if = '';
        $profile = '';
        while(!feof($p)){
            $r = fgets($p);

            //get profile for dns
            $match="/(ProfileList\.[\w\.]+)\.Active=true/i";
            if(preg_match($match,$r,$regs)){

                $profile = $regs[1];

                foreach($aux as $st){
                    $match = "/$profile\.DNS\.(\w+.DNS)=(.+)/";

                    if(preg_match($match,$st,$regs)) $dns_tmp[$regs[1]] = $regs[2];
                                          
                }

            }elseif($profile && preg_match("/$profile\.DNS\.(\w+.DNS)=(.+)/",$r,$regs)) $dns_tmp[$regs[1]] = $regs[2];
            //end dns stuff

            foreach($matches_to_found as $match_data){

                $match = $match_data['match'];
                $match_if = $match_data['if'];                

                if(preg_match($match,$r,$regs)){

                    $re_if = $regs[1];
                    $re_dev = $match_if;
                    $if = $regs[2];
                    $aux[] = $r;
    
                    foreach($aux as $st){
                        $match = "/$re_if\.(\w+)=(.+)/";
                        if(preg_match($match,$st,$regs)) $this->networks[$match_if]->set($regs[1], $regs[2]);
                    }

                }elseif($re_if && $re_dev == $match_if && preg_match("/$re_if\.(\w+)=(.+)/",$r,$regs)){
                    $this->networks[$match_if]->set($regs[1], $regs[2]);
                }else{
                    $aux[] = $r;
                }

            }//end foreach devices
        }//end while read data
        fclose($p);
        
        //udpate networks dns 
        foreach($this->networks as $network) $network->fromArray($dns_tmp,false);

    }


    
     /**
      *
      * update local machine interface. invoques local perl script throught php
      */
    static function updateLocalNetwork(SystemNetwork $network_obj)
    {
        $network = $network_obj->_VA();
        
        $build_args = array();
        if($network['if']) $build_args[] = '-if '.$network['if'];
        if($network['ip']) $build_args[] = '-ip '.$network['ip'];
        if($network['netmask']) $build_args[] = '-netmask '.$network['netmask'];
        if($network['gateway']) $build_args[] = '-gateway '.$network['gateway'];
        if($network['primarydns']) $build_args[] = '-primarydns '.$network['primarydns'];
        if($network['secondarydns']) $build_args[] = '-secondarydns '.$network['secondarydns'];
        if($network['dhcp']) $build_args[] = '-dhcp';

        $args = implode(' ',$build_args);    

        $path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."utils";

        $command = "perl -I".$path.DIRECTORY_SEPARATOR."pl"." ".$path.DIRECTORY_SEPARATOR."pl".
                    DIRECTORY_SEPARATOR."script_chgip.pl ".$args;
       
        ob_start();

        passthru('echo '.$command.' | sudo /usr/bin/php -f '.$path.DIRECTORY_SEPARATOR.'sudoexec.php',$return);

        $result = ob_get_contents();
        ob_end_clean();        

        if($result==0 && $return==0) return true;
        else return false;
        
    }
    
    /*
     * load NTP servers
     */
    public function loadNtpServers()
    {

        $this->ntpservers = array();       

        $path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."utils";
                    
        $command = "perl -I".$path.DIRECTORY_SEPARATOR."pl"." ".$path.DIRECTORY_SEPARATOR."pl".
                    DIRECTORY_SEPARATOR."script_config_ntp.pl -list";

        if(!($p = popen('echo '.$command.' 2>&1 | sudo /usr/bin/php -f '.$path.DIRECTORY_SEPARATOR.'sudoexec.php','r')))
        {
            throw new Exception('could not execute command '.$command);
            return false;
        }

        while(!feof($p)){
            if( $r = fgets($p) ){
                $this->ntpservers[] = trim($r);
            }
        }//end while read data

        fclose($p);

        return true;
    }

     /**
      *
      * update ntp servers. invoques local perl script throught php
      */
    static function updateNtpServers($ntp_obj)
    {

        $args = implode(' ',array_merge(array('-set'),(array)$ntp_obj));

        $path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."utils";

        $command = "perl -I".$path.DIRECTORY_SEPARATOR."pl"." ".$path.DIRECTORY_SEPARATOR."pl".
                    DIRECTORY_SEPARATOR."script_config_ntp.pl ".$args;
       
        ob_start();

        error_log("DEBUG  updateNtpServers command=$command");

        passthru('echo '.$command.' | sudo /usr/bin/php -f '.$path.DIRECTORY_SEPARATOR.'sudoexec.php',$return);

        $result = ob_get_contents();
        ob_end_clean();        

        if($result==0 && $return==0) return true;
        else return false;
        
    }
    

}
?>
