<?php
class ETFW_Squid
{
    private $data = array();
        
    private $http_port_fields = array('addr','opts','port');
    private $https_port_fields = array('addr','opts','port');
    
    private $acl_fields = array('index','name','vals','value','type','file','filecontent','deny_info');
    private $external_acl_type_fields = array('index','name','format','helper','args','options');
    private $http_access_fields = array('index','action','match','dontmatch','acl');
    private $icp_access_fields = array('index','action','match','dontmatch','acl');
    private $http_reply_access_fields = array('index','action','match','dontmatch','acl');
    private $always_direct_fields = array('index','action','match','dontmatch','acl');
    private $never_direct_fields = array('index','action','match','dontmatch','acl');

    private $cache_peer_fields = array('index','hostname','type','http-port','icp-port','options','cache_peer_domain');

    public function ETFW_Squid($data)
    {
        $this->data = $data;
    }

    private function buildDefaultData($field)
    {
        $record = array();
        $i = 0;
        $default_fields = $this->{$field.'_fields'};        
        $process_data = $this->data[$field];
        foreach($process_data as $http_data){
            $http_data = (array) $http_data;

            foreach($default_fields as $field_index=>$field_value){                
                switch($field_value){
                    case 'deny_info' :
                                        $deny_data = (isset($http_data[$field_value])) ? $http_data[$field_value] : array();
                                        if(!empty($deny_data)) $http_data[$field_value] = $deny_data[0];
                    default:
                                        $record[$i][$field_value] = (isset($http_data[$field_value])) ? $http_data[$field_value] : '';
                                        break;
                }
                

            }
            $i++;
        }        
        return $record;        
    }    
    

    public function getHttpPort()
    {
        $result = $this->buildDefaultData('http_port');
        return $result;
    }

    public function getHttpsPort()
    {
        $result = $this->buildDefaultData('https_port');
        return $result;
    }


    public function getAclData()
    {
        $result = $this->buildDefaultData('acl');
        return $result;
    }

    public function getExternalAclData()
    {
        $result = $this->buildDefaultData('external_acl_type');
        return $result;
    }

    public function getHttpAccessData()
    {
        $result = $this->buildDefaultData('http_access');
        return $result;
    }

    public function getIcpAccessData()
    {
        $result = $this->buildDefaultData('icp_access');
        return $result;
    }

    public function getHttpReplyAccessData()
    {
        $result = $this->buildDefaultData('http_reply_access');
        return $result;
    }

    public function getAlwaysDirectData()
    {
        $result = $this->buildDefaultData('always_direct');
        return $result;
    }

    public function getNeverDirectData()
    {
        $result = $this->buildDefaultData('never_direct');
        return $result;
    }

    public function getCachePeerData()
    {
        $result = $this->buildDefaultData('cache_peer');
        return $result;
    }



}
?>