<?php

/**
 *
 * Class to manipulate PBX data and SOAP requests to the MA (Management Agent)
 *
 */
class ETVOIP_Pbx
{

    private $devices_tech = array(
                                //tec       =>  array(text,fulltext)
                                'sip'       => array('SIP', 'Generic SIP Device'),
                                'iax'      => array('IAX2', 'Generic IAX Device'),
                                'iax2'      => array('IAX2', 'Generic IAX2 Device'),
                                'zap'       => array('ZAP', 'Generic ZAP Device'),
                                'custom'    => array('CUSTOM', 'Other (Custom) Device'),
                                ''          => array('VIRTUAL', 'None (virtual exten)')
                                );

    const ADD_EXTENSION = 'add_extension';
    const EDIT_EXTENSION = 'edit_extension';
    const GET_EXTENSION = 'get_extension';
    const DEL_EXTENSION = 'del_extension';
    const GET_EXTENSIONS = 'get_extensions';

    const ADD_TRUNK = 'add_trunk';
    const EDIT_TRUNK = 'edit_trunk';
    const DEL_TRUNK = 'del_trunk';
    const GET_TRUNK = 'get_trunk';
    const GET_TRUNKS = 'get_trunks';    

    const GET_OUTBOUNDROUTES = 'get_outboundroutes';
    const GET_OUTBOUNDROUTE = 'get_outboundroute';
    const ADD_OUTBOUNDROUTE = 'add_outboundroute';
    const EDIT_OUTBOUNDROUTE = 'edit_outboundroute';
    const DEL_OUTBOUNDROUTE = 'del_outboundroute';
    

    const ADD_INBOUNDROUTE = 'add_inboundroute';
    const EDIT_INBOUNDROUTE = 'edit_inboundroute';
    const DEL_INBOUNDROUTE = 'del_inboundroute';
    const GET_INBOUNDROUTE = 'get_inboundroute';
    const GET_INBOUNDROUTES = 'get_inboundroutes';

    const DO_RELOAD = 'do_reload';

    const _ERR_GET_EXTENSIONS_ = 'Could not retrieve extensions. %info%';
    const _ERR_ADD_EXTENSION_ = 'Could not add extension. %info%';

    const _ERR_UNDEF = 'Undefined error occured. %info%';


    private $default_params = array('dispatcher'=>'pbx');
    private $etva_server;

    public function ETVOIP_Pbx(EtvaServer $etva_server)
    {
        $this->etva_server = $etva_server;

    }

    /*
     * Return i18n error text in response
     */
    public function translate_error($response)
    {
        if(!$response['success']){

            $property = $response['error'];
            $prop_value = constant('self::'.$property);

            $msg_i18n = sfContext::getInstance()->getI18N()->__($prop_value ? $prop_value : self::_ERR_UNDEF,array('%info%'=>$response['info']));

            $response['error'] = $msg_i18n;
            return $response;
        }

        return $response;
    }

    private function class_not_found_err($name)
    {
        return array('success'=>false,'info'=>"Class $name not found",'error'=>"Class $name not found");
    }

    /**
     * Gets string representation of device tech field
     *
     * @param string $device
     * @param ('text' || 'fulltext') $ttype
     * @return string
     * 
     */
    public function translateDeviceTech($device, $ttype)
    {
        if(isset($this->devices_tech[$device]))
        {
            if($ttype=='text') return $this->devices_tech[$device][0];
            if($ttype=='fulltext') return $this->devices_tech[$device][1];
        }
        return '';
    }


    public function do_reload()
    {
        $method = self::DO_RELOAD;
        $etva_server = $this->etva_server;

        $params = $this->default_params;
        $response = $etva_server->soapSend($method,$params);

        $result = $response;
        return $result;
    }

    public function del_extension($extension)
    {             
        $method = self::DEL_EXTENSION;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,array('extension'=>$extension));

        $response = $etva_server->soapSend($method,$params);        
        $result = $response;
        return $result;

    }

    /*
     * retrive extension info by tech and number
     */
    public function get_extension($tech, $extension)
    {
        $method = self::GET_EXTENSION;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,array('extension'=>$extension));

        $response = $etva_server->soapSend($method,$params);
        $data = (array) $response['response'];
        
        $ext_class = "ETVOIP_Pbx_Extension_$tech";

        if(!class_exists($ext_class)) return $this->class_not_found_err($ext_class);

        $extObj = new $ext_class($data);

        $response['response'] = $extObj->toArray();

        $result = $response;        
        return $result;

    }


    public function add_extension(array $data)
    {
        
        $tech = $data['tech'];
        $dev_tech = $this->translateDeviceTech($tech, 'text');
        if(!$dev_tech) return array('success'=>false);

        $ext_class = "ETVOIP_Pbx_Extension_$dev_tech";
        $extObj = new $ext_class($data);
        
        $ext_data = $extObj->toArray();

        $method = self::ADD_EXTENSION;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$ext_data);

        $response = $etva_server->soapSend($method,$params);

        $result = $this->process_extensions($response);
        return $result;
    }


    public function edit_extension(array $data)
    {

        $tech = $data['tech'];
        $dev_tech = $this->translateDeviceTech($tech, 'text');
        if(!$dev_tech) return array('success'=>false);

        $ext_class = "ETVOIP_Pbx_Extension_$dev_tech";

        if(!class_exists($ext_class)) return $this->class_not_found_err($ext_class);

        $extObj = new $ext_class($data);

        $ext_data = $extObj->toArray();

        $method = self::EDIT_EXTENSION;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$ext_data);

        $response = $etva_server->soapSend($method,$params);

        $result = $this->process_extensions($response);
        return $result;

    }


    /**
     * get extensions list from agent
     *     
     * @return array
     */
    public function get_extensions()
    {
        $method = self::GET_EXTENSIONS;
        $etva_server = $this->etva_server;

        $params = $this->default_params;                
        $response = $etva_server->soapSend($method,$params);

        $result = $this->process_extensions($response);
        return $result;
    }    

    /**
     * Process soap get_extensions response
     *         
     *
     */
    public function process_extensions($response)
    {
        $response = $this->translate_error($response);
        if(!$response['success']) return $response;

        $response_decoded = &$response['response']->data;
        foreach($response_decoded as &$extension)
        {

            $extension->tech_name =  $this->translateDeviceTech($extension->tech, 'text');            

        }
        

        return $response;

    }

    /*
     *
     *  TRUNKS
     *
     */

    public function get_trunk($trunknum)
    {
        $method = self::GET_TRUNK;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,array('trunknum'=>$trunknum));

        $response = $etva_server->soapSend($method,$params);        

        $response_decoded = (array) $response['response'];        

        $tech = $response_decoded['tech'];
        $dev_tech = $this->translateDeviceTech($tech, 'text');
        if(!$dev_tech) return array('success'=>false);

        if($tech=='sip' || $tech=='iax') $trunk_class = "ETVOIP_Pbx_Trunk_SIP_IAX2";
        else $trunk_class = "ETVOIP_Pbx_Trunk_$dev_tech";

        if(!class_exists($trunk_class)) return $this->class_not_found_err($trunk_class);

        $trunkObj = new $trunk_class();                
        $trunkObj->importMAP($response_decoded);
        $trunkObj->setTrunknum($trunknum);
        $response['response'] = $trunkObj->toArray();
              
        $result = $response;
        return $result;
    }

    
    public function get_trunks()
    {
        $method = self::GET_TRUNKS;        
        
        $etva_server = $this->etva_server;

        $params = $this->default_params;
        $response = $etva_server->soapSend($method,$params);

        $result = $response;
        return $result;
    }    
    
    public function add_trunk(array $data)
    {

        $tech = $data['tech'];
        $dev_tech = $this->translateDeviceTech($tech, 'text');
        if(!$dev_tech) return array('success'=>false);

        if($tech=='sip' || $tech=='iax2' || $tech=='iax') $trunk_class = "ETVOIP_Pbx_Trunk_SIP_IAX2";
        else $trunk_class = "ETVOIP_Pbx_Trunk_$dev_tech";
        $trunkObj = new $trunk_class($data);
        $trunkObj_data = $trunkObj->toArray();

        $method = self::ADD_TRUNK;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$trunkObj_data);

        $response = $etva_server->soapSend($method,$params);
        $result = $response;        
        return $result;
    }


    public function edit_trunk(array $data)
    {
        $tech = $data['tech'];
        $dev_tech = $this->translateDeviceTech($tech, 'text');
        if(!$dev_tech) return array('success'=>false);

        if($tech=='sip' || $tech=='iax2' || $tech=='iax') $trunk_class = "ETVOIP_Pbx_Trunk_SIP_IAX2";
        else $trunk_class = "ETVOIP_Pbx_Trunk_$dev_tech";

        if(!class_exists($trunk_class)) return $this->class_not_found_err($trunk_class);
        
        $trunkObj = new $trunk_class($data);
        $trunkObj_data = $trunkObj->toArray();

        $method = self::EDIT_TRUNK;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$trunkObj_data);

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;
    }
    

    public function del_trunk($trunk)
    {
        $method = self::DEL_TRUNK;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,array('trunknum'=>$trunk));

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;

    }


    /*
     * OUTBOUND ROUTES
     *
     */

    public function get_outboundroute($route)
    {
        $method = self::GET_OUTBOUNDROUTE;

        $etva_server = $this->etva_server;

        $params = array_merge($this->default_params,array('routename'=>$route));
       
        $response = $etva_server->soapSend($method,$params);
        $result = $this->process_outboundroute($response);
        return $result;        
    }


    public function process_outboundroute($response)
    {
        $response = $this->translate_error($response);
        if(!$response['success']) return $response;
      
        //$response_decoded = (array) $response['response'];

        //$routeObj = new ETVOIP_Pbx_Route($response_decoded);
        //$response['response'] = $routeObj->toArray();
       
        $route = &$response['response'];
        if($route->emergency) $route->emergency = '1';
        if($route->intracompany) $route->intracompany = '1';
        if($route->routecid_mode && $route->routecid_mode=='override_extension') $route->routecid_mode = '1';
        

        return $response;
    }


        


    public function add_outboundroute(array $data)
    {        

        $routeObj = new ETVOIP_Pbx_Route($data);
        $routeObj_data = $routeObj->toArray();

        $method = self::ADD_OUTBOUNDROUTE;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$routeObj_data);

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;
    }


    public function edit_outboundroute(array $data)
    {
        $routeObj = new ETVOIP_Pbx_Route($data);
        $routeObj_data = $routeObj->toArray();

        $method = self::EDIT_OUTBOUNDROUTE;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$routeObj_data);

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;
    }


    public function del_outboundroute($route)
    {       
        $method = self::DEL_OUTBOUNDROUTE;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,array('routename'=>$route));

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;
    }
    

    public function get_outboundroutes()
    {
        $method = self::GET_OUTBOUNDROUTES;
        $etva_server = $this->etva_server;

        $params = $this->default_params;
        $response = $etva_server->soapSend($method,$params);

        $result = $response;
        return $result;
    }

    /*
     *
     * INBOUND ROUTES
     *
     */


    public function add_inboundroute(array $data)
    {

        $routeObj = new ETVOIP_Pbx_InboundRoute($data);
        $routeObj_data = $routeObj->toArray();

        $method = self::ADD_INBOUNDROUTE;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$routeObj_data);

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;
    }


    public function edit_inboundroute(array $data)
    {
        $routeObj = new ETVOIP_Pbx_InboundRoute($data);
        $routeObj_data = $routeObj->toArray();

        $method = self::EDIT_INBOUNDROUTE;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,$routeObj_data);

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;
    }

    public function del_inboundroute($extdisplay)
    {
        $method = self::DEL_INBOUNDROUTE;
        $etva_server = $this->etva_server;
        $params = array_merge($this->default_params,array('extdisplay'=>$extdisplay));

        $response = $etva_server->soapSend($method,$params);
        $result = $response;
        return $result;
    }


    public function get_inboundroute($extdisplay)
    {
        $method = self::GET_INBOUNDROUTE;

        $etva_server = $this->etva_server;

        $params = array_merge($this->default_params,array('extdisplay'=>$extdisplay));

        $response = $etva_server->soapSend($method,$params);        
        return $response;
    }

    public function get_inboundroutes()
    {
        $method = self::GET_INBOUNDROUTES;
        $etva_server = $this->etva_server;

        $params = $this->default_params;
        $response = $etva_server->soapSend($method,$params);

        $result = $response;
        return $result;
    }


    
}

class ETVOIP_Pbx_Extension
{
    var $extension;
    var $langcode;
    var $dictenabled;
    var $dictformat;
    var $dictemail;
    var $record_in;
    var $record_out;
    var $cid_masquerade;
    var $call_screen;
    var $callwaiting;
    var $pinless;
    var $sipname;
    var $tech;
    var $name;
    var $outboundcid;
    var $ringtimer;
    var $emergency_cid;
    var $newdid;
    var $newdid_name;
    var $newdidcid;
    var $faxenabled;
    var $faxemail;
    var $vm;
    var $options;
    var $imapuser;
    var $imappassword;
    var $attach;
    var $saycid;
    var $envelope;
    var $delete;
    var $vmcontext;
    var $vmpwd;    
    var $email;
    var $pager;    


    public function fromArray($fields)
    {        
        $class_vars = get_class_vars(get_class($this));        
        
        foreach($class_vars as $name => $value)
        {
            if(isset($fields[$name])){                
                $this->$name = $fields[$name];
            }else{
                if(isset($fields['devinfo_'.$name])) $this->$name = $fields['devinfo_'.$name];
            }
                    
        }
    }

    public function toArray()
    {        
        $class_vars = get_class_vars(get_class($this));
        $toArray = array();
        foreach($class_vars as $name => $value)
            if(isset($this->$name)) $toArray[$name] = $this->$name;

        return $toArray;

    }

    public function getHiddenDevinfo($exclude)
    {

        $class_vars = get_class_vars(get_class($this));
        
        $toArray = array();
        foreach($class_vars as $name => $value){
            
            if(preg_match('/^devinfo_/',$name) && !in_array($name,$exclude)){
                $toArray[$name] = $value;
            }
        }
        
        return $toArray;
    }

    public function __construct($data)
    {
        if($data) $this->fromArray($data);
    }
    

}

class ETVOIP_Pbx_Extension_SIP extends ETVOIP_Pbx_Extension
{
    var $devinfo_secret;
    var $devinfo_dtmfmode;
    var $devinfo_canreinvite = 'no';
    var $devinfo_context = 'from-internal';
    var $devinfo_host = 'dynamic';
    var $devinfo_type = 'friend';
    var $devinfo_nat = 'yes';
    var $devinfo_port = '5060';
    var $devinfo_qualify = 'yes';
    var $devinfo_callgroup;
    var $devinfo_pickupgroup;
    var $devinfo_disallow;
    var $devinfo_allow;
    var $devinfo_dial;
    var $devinfo_accountcode;
    var $devinfo_mailbox;
    var $devinfo_deny = '0.0.0.0/0.0.0.0';
    var $devinfo_permit = '0.0.0.0/0.0.0.0';


    public function getHiddenDevinfo(){

        $exclude = array('devinfo_secret','devinfo_dtmfmode');
        return parent::getHiddenDevinfo($exclude);
    }

}


class ETVOIP_Pbx_Extension_IAX2 extends ETVOIP_Pbx_Extension
{
    var $devinfo_secret;    
    var $devinfo_notransfer = 'yes';
    var $devinfo_context = 'from-internal';
    var $devinfo_host = 'dynamic';
    var $devinfo_type = 'friend';
    var $devinfo_port = '4569';
    var $devinfo_qualify = 'yes';
    var $devinfo_disallow;
    var $devinfo_allow;
    var $devinfo_dial;
    var $devinfo_accountcode;
    var $devinfo_mailbox;
    var $devinfo_deny = '0.0.0.0/0.0.0.0';
    var $devinfo_permit = '0.0.0.0/0.0.0.0';
    var $devinfo_requirecalltoken;

    public function getHiddenDevinfo(){

        $exclude = array('devinfo_secret');
        return parent::getHiddenDevinfo($exclude);
    }
}

class ETVOIP_Pbx_Extension_ZAP extends ETVOIP_Pbx_Extension
{
    var $devinfo_channel;
    var $devinfo_context = 'from-internal';
    var $devinfo_immediate = 'no';
    var $devinfo_signalling = 'fxo_ks';
    var $devinfo_echocancel = 'yes';
    var $devinfo_echocancelwhenbridged = 'no';
    var $devinfo_echotraining = '800';
    var $devinfo_busydetect = 'no';
    var $devinfo_busycount = '7';
    var $devinfo_callprogress = 'no';
    var $devinfo_dial;
    var $devinfo_accountcode;
    var $devinfo_callgroup;
    var $devinfo_pickupgroup;
    var $devinfo_mailbox;

    public function getHiddenDevinfo(){

        $exclude = array('devinfo_channel');
        return parent::getHiddenDevinfo($exclude);
    }

}



class ETVOIP_Pbx_Trunk
{    
    var $tech;    
    
    var $disabletrunk;
    const DISABLETRUNK_MAP = 'disabled';

    var $dialrules;
    var $trunk_name;

    var $trunknum;
    const TRUNK_NAME_MAP = 'name';

    var $outcid;
    var $keepcid;
    var $maxchans;

    var $failtrunk;
    const FAILTRUNK_MAP = 'failscript';    

    var $dialoutprefix;
    var $provider;

    public function fromArray($fields)
    {
        $class_vars = get_class_vars(get_class($this));

        foreach($class_vars as $name => $value)
            if(isset($fields[$name]))
                $this->$name = $fields[$name];
    }

    public function toArray()
    {
        $class_vars = get_class_vars(get_class($this));
        $toArray = array();
        foreach($class_vars as $name => $value)
            $toArray[$name] = $this->$name;

        return $toArray;

    }

    public function setTrunknum($num)
    {
        $this->trunknum = $num;

    }

    public function __construct($data)
    {
        if($data) $this->fromArray($data);
    }
    

    public function importMAP($arr)
    {       
        $class_vars = get_class_vars(get_class($this));

        foreach($class_vars as $name => $value){
            $const = constant('self::'.strtoupper($name).'_MAP');
                        
            if($const){
                $this->$name = $arr[$const];                
            }else{                
                if(isset($arr[$name])) $this->$name = $arr[$name];                
            }
        }

    }
    


}


class ETVOIP_Pbx_Trunk_SIP_IAX2 extends ETVOIP_Pbx_Trunk
{
    var $channelid;
    var $peerdetails;
    var $usercontext;
    var $userconfig;
    var $register;
}


class ETVOIP_Pbx_Trunk_ZAP extends ETVOIP_Pbx_Trunk
{
    var $channelid;
}


class ETVOIP_Pbx_Route
{
    var $routename;
    var $routepass;
    var $trunkpriority;
    var $dialpattern;
    var $emergency;
    var $intracompany;
    var $mohsilence;
    var $routecid;
    var $routecid_mode;


    public function fromArray($fields)
    {
        $class_vars = get_class_vars(get_class($this));

        foreach($class_vars as $name => $value)
            if(isset($fields[$name]))
                $this->$name = $fields[$name];
    }

    public function toArray()
    {
        $class_vars = get_class_vars(get_class($this));
        $toArray = array();
        foreach($class_vars as $name => $value)
            $toArray[$name] = $this->$name;

        return $toArray;

    }

    public function __construct($data)
    {
        if($data) $this->fromArray($data);
    }


}



class ETVOIP_Pbx_InboundRoute
{
    var $description;
    var $extdisplay;
    var $pricid;
    var $privacyman;
    var $alertinfo;
    var $grppre;
    var $ringing;
    var $delay_answer;
    var $extension;
    var $cidnum;
    var $cidlookup_id;
    var $mohclass;
    var $pmmaxretries;
    var $pmminlength;
    var $goto0;
    var $Extensions;
    var $Ring_Groups;
    var $Voicemail;
    var $Terminate_Call;
    var $Phonebook_Directory;
    var $IVR;

    public function fromArray($fields)
    {
        $class_vars = get_class_vars(get_class($this));

        foreach($class_vars as $name => $value)
            if(isset($fields[$name]))
                $this->$name = $fields[$name];
    }

    public function toArray()
    {
        $class_vars = get_class_vars(get_class($this));
        $toArray = array();
        foreach($class_vars as $name => $value)
            $toArray[$name] = $this->$name;

        return $toArray;

    }

    public function __construct($data)
    {
        if($data) $this->fromArray($data);
    }


}

?>