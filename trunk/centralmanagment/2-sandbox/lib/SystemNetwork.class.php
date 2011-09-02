<?php
/**
 * class for representation of system netowrk interface info ip, gw, netmask, dns....
 */
class SystemNetwork
{
    
         
    const IP = 'IP';
    const NETMASK = 'Netmask';
    const GW = 'Gateway';
    const BOOTP = 'BootProto';
    const AUTODNS = 'AutoDNS';
    const DNS = 'PrimaryDNS';
    const DNSSec = 'SecondaryDNS';
    const INTF = 'Device';    
    const CM_URI = 'CM_URI';
    
    const _ERR_NOTFOUND_INTF_   = 'Interface %name% could not be found. %info%';
    const _ERR_IP_INUSE_   = 'IP address %ip% in use by %name%';

    /*
     * initialize uri ip defaults => server ip
     */
    public function __construct()
    {
        $this->setURI($_SERVER['SERVER_ADDR'],$_SERVER['SERVER_PORT']);
    }
  

    /*
     * set cm_uri deafults => server ip
     */
    public function setURI($ip,$port)
    {

        if(!$port) $port = $_SERVER['SERVER_PORT'];        
    
        if($port == 80 ) $this->set(self::CM_URI, 'http://'.$ip.'/soapapi.php');
        else $this->set(self::CM_URI, 'http://'.$ip.':'.$port.'/soapapi.php');
    }

    /*
     * creates network representation to use for soap sending to agent
     */
    public function _VA()
    {
        //if network is dhcp send only specific info
        if($this->get(self::BOOTP)=='dhcp'){

            $sysnetwork_VA = array(
                                'if'=>$this->get(self::INTF),
                                'cm_uri' => $this->get(self::CM_URI),
                                'dhcp'   => 1);
        }else{
            
            $sysnetwork_VA = array('cm_uri' => $this->get(self::CM_URI));

            $if = $this->get(self::INTF);
            if($if) $sysnetwork_VA['if'] = $if;

            $ip = $this->get(self::IP);
            if($ip) $sysnetwork_VA['ip'] = $ip;

            $netmask = $this->get(self::NETMASK);
            if($netmask) $sysnetwork_VA['netmask'] = $netmask;

            $gateway = $this->get(self::GW);
            if($gateway) $sysnetwork_VA['gateway'] = $gateway;

            $primarydns = $this->get(self::DNS);
            if($primarydns) $sysnetwork_VA['primarydns'] = $primarydns;

            $secondarydns = $this->get(self::DNSSec);
            $sysnetwork_VA['secondarydns'] = $secondarydns;                                
        }

        return $sysnetwork_VA;

    }
        
   


    /*
     * build system network from array. MUST have 'if' specified
     */
    public function fromArray($fields,$php_mode = true)
    {
        static $fieldlist  = array(
            'if'   => self::INTF,            
            'ip'   => self::IP,
            'netmask'   => self::NETMASK,
            'gateway'   => self::GW,
            'bootp'   => self::BOOTP,
            'primarydns'   => self::DNS,
            'secondarydns'   => self::DNSSec
        );

        foreach($fields as $field=>$value){
            if($php_mode){
                if (isset($fieldlist[$field])){
                    $this->set($fieldlist[$field], $value);
                }else return false;
            }
            else{
                $this->set($field, $value);
            }
        }
        return true;
       
    }

    public function equals(SystemNetwork $other)
    {
        return ($this->get(SystemNetwork::IP) == $other->get(SystemNetwork::IP))
                && ($this->get(SystemNetwork::NETMASK) == $other->get(SystemNetwork::NETMASK))
                && ($this->get(SystemNetwork::GW) == $other->get(SystemNetwork::GW))
                && ($this->get(SystemNetwork::DNS) == $other->get(SystemNetwork::DNS))
                && ($this->get(SystemNetwork::DNSSec) == $other->get(SystemNetwork::DNSSec));

    }


   
    function validateTarget($target){
        $valid = true;
        switch($target){
            case self::IP:
            case self::NETMASK:
            case self::GW:
            case self::AUTODNS:
            case self::CM_URI:
            case self::DNS:
            case self::DNSSec:
            case self::BOOTP:
            case self::INTF:            
                            break;
            default: $valid = false;

        }
        return $valid;
    }

   

    function set($target,$value)
    {
        if($this->validateTarget($target)) $this->$target = $value;
    }

    

    function get($target)
    {
        if($this->validateTarget($target)) return $this->$target;
    }


}
?>