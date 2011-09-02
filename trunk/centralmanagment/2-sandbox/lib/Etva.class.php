<?php
class Etva
{
    const _AGENT_MSG_  = '(%agent%) %msg%';
    const _ERR_ISODIR_INUSE_   = 'ISO REPO in use. Make sure it is not currently in use. %info%';

    const _CDROM_INUSE_ = 'CD-ROM in use';
    const _ERR_ISO_INUSE_   = 'ISO %name% in use. %info%';
    const _ERR_ISO_PROBLEM_   = 'ISO %name% problem. %info%';
    const _ERR_ISO_DELETE_ = 'Could not delete ISO. %info%';
    const _ERR_ISO_RENAME_ = 'Could not rename ISO. %info%';

    static public function MB_to_Byteconvert($inputMB)
    {        
        $bytes = $inputMB * pow(2,20);
        return $bytes;

    }

    static public function Byte_to_MBconvert($inputByte, $round = 0)
    {
        $mbytes = $inputByte / pow(2,20);
        $rmb = round($mbytes, $round);
        return $rmb;

    }


    function listDir($dir,$reg_exp = false)
    {
        $dirpath = $dir;
        $found = false;

        if(!is_dir($dirpath)) return false;
        $data = array();

        $dh = opendir($dirpath);
        while (false !== ($file_aux = readdir($dh)) && !$found) {
            //dont list subdirectories
            if (!is_dir("$dirpath/$file_aux")) {

                if($reg_exp)
                {
                    preg_match($reg_exp,$file_aux,$matches);
                    if($matches) $data[] = $matches;
                }
                else $data[] = $file_aux;
                                
            }
        }
        closedir($dh);
        return $data;
    }

    /*
     * include all services partial
     * loads _<agent_tmpl>_<service>.php file
     */
    static public function loadServicesPartials(EtvaServer $etva_server)
    {
        $server_services = $etva_server->getEtvaServices();
        $agent_tmpl = $etva_server->getAgentTmpl();

        $module_agent = strtolower($etva_server->getAgentTmpl());

        $directory = sfContext::getInstance()->getConfiguration()->getTemplateDir($module_agent, 'viewSuccess.php');

        $reg_exp = "/_".$agent_tmpl."_(([a-zA-Z])+)\.php/";
        $services_data = self::listDir($directory,$reg_exp);        

        foreach($services_data as $service_process){
            $service_name = $service_process[1];
            $tmpl = $agent_tmpl.'_'.$service_name;
            $service_path = sfContext::getInstance()->getConfiguration()->getTemplateDir($module_agent, '_'.$tmpl.'.php');
            if($service_path)
                include_partial($module_agent.'/'.$tmpl);
        }
    }


    static public function loadServicesPartials_old(EtvaServer $etva_server)
    {
        $server_services = $etva_server->getEtvaServices();
        $module_agent = strtolower($etva_server->getAgentTmpl());

        $directory = sfContext::getInstance()->getConfiguration()->getTemplateDir(strtolower($etva_server->getAgentTmpl()), 'viewSuccess.php');

        $services_data = self::listDir($directory,'/_ETVOIP_([^_]+)\.php/');


        die();

        foreach($server_services as $service){
            $tmpl = $etva_server->getAgentTmpl().'_'.$service->getNameTmpl();
            $service_path = sfContext::getInstance()->getConfiguration()->getTemplateDir($module_agent, '_'.$tmpl.'.php');
            if($service_path)
                include_partial($module_agent.'/'.$tmpl,array('etva_server'=>$etva_server,'etva_service'=>$service));
        }
    }
    
    
    static public function to_MBconvert($input)
    {        
        if($input > 100000000){
            //assume bytes
            $result = (int) $input / pow(2,20);
        }elseif($input > 100000){
            //assume kilobytes
            $result = (int) $input / pow(2,10);
        }elseif($input < 32){
            //assume GB
            $result = (int) $input * pow(2,10);
        }else{
            $result = (int) $input;
        }
        
        $rounded = floor($result);
        return $rounded;

    }
    
    /*
     * parses $params array in $code message format
     */
    static function getLogMessage($params,$code){
        $parse_params = array();

        foreach($params as $param_k => $param_v)
            $parse_params['%'.$param_k.'%'] = $param_v;

        $msg = strtr($code, $parse_params);

        return $msg;

    }

    /*
     * get data from file conf
     */
    static function getEtvaModelFile()
    {
        if(file_exists('/etc/sysconfig/etva-model.conf'))
        {
            $ini_array = parse_ini_file('/etc/sysconfig/etva-model.conf',true);
            return $ini_array;
        }
        else return false;
                    
    }

    /*
     * verify if a ISO is not allocated to a server
     * if allocated return list of servers using ISO
     */
    static function verify_iso_usage($iso)
    {
        $directory = sfConfig::get("config_isos_dir");
        $iso_path = $directory . '/' . $iso;

        /*
         *
         * check ISO DIR in use
         *
         */
        $errors = array();
        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::LOCATION, $iso_path);
        $servers_running_iso = EtvaServerPeer::doSelect($criteria);

        foreach($servers_running_iso as $server)
            $errors[] = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_CDROM_INUSE_,array('%name%'=>$server->getName()));

        return $errors;

    }

}
?>