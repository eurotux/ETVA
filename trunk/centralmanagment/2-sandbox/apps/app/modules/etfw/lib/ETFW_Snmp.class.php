<?php
class ETFW_Snmp
{

    private $directives = array();
    private $view = array();
    private $groups = array();
    private $security = array();
    private $access = array();
    private $versions = array('v1','v2c');


    public function ETFW_Snmp($data = array())
    {
        if($data)
        {
            $this->build_directives($data);
            $this->build_groups($data);
            $this->build_security($data);
        }        
        
    }

    private function directives_VA()
    {
        $data = array();
        foreach($this->directives as $dir)
        {
            $dir_array = $dir->toArray();            
            if($dir_array['value']) $data[] = $dir->toArray();

        }
        
        return array('Directives'=>$data);

    }

    private function security_VA()
    {
        $data = array();
        foreach($this->security as $sec)
        {
            $data[] = $sec->toArray();
        }
        return array('Security'=>$data);
    }

    private function groups_VA()
    {
        $data = array();
        foreach($this->groups as $group)
        {
            $data[] = $group->toArray();
        }
        return array('Groups'=>$data);
    }


    private function view_VA()
    {
        $data = array();
        foreach($this->view as $v)
        {
            $data[] = $v->toArray();
        }
        return array('View'=>$data);
    }

    private function access_VA()
    {
        $data = array();
        foreach($this->access as $ac)
        {
            $data[] = $ac->toArray();
        }
        return array('Access'=>$data);
    }


    public function _VA()
    {    
        $directives = $this->directives_VA();
        $security = $this->security_VA();
        $groups = $this->groups_VA();
        $view = $this->view_VA();
        $access = $this->access_VA();
        $snmp_va = array_merge($directives,$security,$groups,$view,$access);
        
        return $snmp_va;
    }

    public function createConfig($data)
    {

        $groupName = "myROGroup";

        // create directives
        $directives = $data['directives'];
        $directives_data = array();
        foreach($directives as $dir=>$value)
        {
            if($dir == 'trapcommunity')
                $directives_data[] = array('directive' => 'rocommunity','value' => $value);

            $directives_data[] = array('directive' => $dir,'value' => $value);
        }
        
        
        $build_directives_data = array('Directives' =>$directives_data);
        $this->build_directives($build_directives_data);


        //create groups and security
        $security = $data['security'];
        $security_data = array();
        $groups_data = array();
        $i = 1;
        foreach($security as $sec_data)
        {
            $secname = 'myMonHost'.$i;
            $source = $sec_data['source'];
            $community = $sec_data['community'];

            //IMPORTANT! build groups first
            foreach($this->versions as $ver)
            {
                $groups_data[] = array(
                                    "securityname" =>$secname,
                                    "groupname" =>$groupName,
                                    "securitymodel" => $ver);             
            }

            $security_data[] = array(
                                    "secname" => $secname,
                                    "source" => $source,
                                    "community" => $community);
            $i++;            
        }

        $build_groups_data = array('Groups' =>$groups_data);
        $this->build_groups($build_groups_data);

        //build security
        $build_security_data = array('Security' =>$security_data);
        $this->build_security($build_security_data);


        //add default view        
        $view_data = array(array('name' => 'all', 'inc_exc' => 'included', 'subtree' => '.1', 'mask' => '80'));
        $build_view_data = array('View' =>$view_data);
        $this->build_view($build_view_data);

        //add default group access
        $access_data = array(array('group' => $groupName, 'context' => '', 'secmodel' => 'any', 'seclevel' => 'noauth', 'prefix' => 'exact', 'read' => 'all', 'write' => 'none', 'notif' => 'none'));
        $build_access_data = array('Access' =>$access_data);
        $this->build_access($build_access_data);

        

    }

    
    public function getSecurityInfo()
    {
        $result = array();
        foreach($this->security as $sec)
        {            
            $info = $sec->getInfo();
            $result[] =  $info;
        }
        return $result;
    }

    private function build_directives($data)
    {
        $directives = (array) $data['Directives'];
        foreach($directives as $directive_data)
            $this->directives[] = new ETFW_Snmp_Directive((array) $directive_data);

    }

    private function build_view($data)
    {
        $views = (array) $data['View'];
        foreach($views as $view_data)
            $this->view[] = new ETFW_Snmp_View((array) $view_data);

    }

    private function build_access($data)
    {
        $access = (array) $data['Access'];
        foreach($access as $access_data)
            $this->access[] = new ETFW_Snmp_Access((array) $access_data);

    }

    public function getDirectivesInfo()
    {
        $result = array();
        foreach($this->directives as $dir)
        {
            $info = $dir->getInfo();
            $result[] =  $info;
        }
        return $result;
    }

    private function build_groups($data)
    {
        $groups = (array) $data['Groups'];
        foreach($groups as $group_data)
            $this->groups[] = new ETFW_Snmp_Group((array) $group_data);
        
    }
    

    private function build_security($data)
    {
        $security = (array) $data['Security'];
        foreach($security as $security_data)
        {
            $data_array = (array) $security_data;            
            $this->security[] = new ETFW_Snmp_Security($data_array);
        }       
    }
   
    
}



class ETFW_Snmp_Security
{
    private $secname;
    private $source;
    private $community;        


    public function toArray()
    {
        $class_vars = get_class_vars(__CLASS__);
        $toArray = array();
        foreach($class_vars as $name => $value)
            if(!empty($this->$name)) $toArray[$name] = $this->$name;

        return $toArray;

    }

    public function ETFW_Snmp_Security($array = array())
    {
        if($array) $this->fromArray($array);
    }

    public function getInfo()
    {
        return array('source' => $this->source, 'community' => $this->community);

    }

    public function fromArray($fields)
    {

        $class_vars = get_class_vars(__CLASS__);
        foreach($class_vars as $name => $value)
            if(isset($fields[$name]))
                $this->$name = $fields[$name];
    }
}


class ETFW_Snmp_Directive
{
    private $value;
    private $content;
    private $directive;    



    public function toArray()
    {
        $class_vars = get_class_vars(__CLASS__);
        $toArray = array();
        foreach($class_vars as $name => $value)
            if(!empty($this->$name)) $toArray[$name] = $this->$name;

        return $toArray;

    }
    

    public function ETFW_Snmp_Directive($array = array())
    {
        if($array) $this->fromArray($array);
    }

    public function getInfo()
    {
        return array($this->directive => $this->value);

    }

    public function fromArray($fields)
    {
        
        $class_vars = get_class_vars(__CLASS__);        
        foreach($class_vars as $name => $value)            
            if(isset($fields[$name]))
                $this->$name = $fields[$name];                    
    }
}



class ETFW_Snmp_View
{     

    private $name;
    private $inc_exc;
    private $subtree;
    private $mask;

    public function toArray()
    {
        $class_vars = get_class_vars(__CLASS__);
        $toArray = array();
        foreach($class_vars as $name => $value)
            if(!empty($this->$name)) $toArray[$name] = $this->$name;

        return $toArray;
    }


    public function ETFW_Snmp_View($array = array())
    {
        if($array) $this->fromArray($array);
    }    

    public function fromArray($fields)
    {

        $class_vars = get_class_vars(__CLASS__);
        foreach($class_vars as $name => $value)
            if(isset($fields[$name]))
                $this->$name = $fields[$name];
    }
}



class ETFW_Snmp_Access
{
    
    private $group;
    private $context;
    private $secmodel;
    private $seclevel;
    private $prefix;
    private $read;
    private $write;
    private $notif;

    public function toArray()
    {
        $class_vars = get_class_vars(__CLASS__);
        $toArray = array();
        foreach($class_vars as $name => $value)
            if(!empty($this->$name)) $toArray[$name] = $this->$name;

        return $toArray;
    }


    public function ETFW_Snmp_Access($array = array())
    {
        if($array) $this->fromArray($array);
    }

    public function fromArray($fields)
    {

        $class_vars = get_class_vars(__CLASS__);
        foreach($class_vars as $name => $value)
            if(isset($fields[$name]))
                $this->$name = $fields[$name];
    }
}


class ETFW_Snmp_Group
{
    private $securityname;
    private $groupname;
    private $securitymodel;    

    public function ETFW_Snmp_Group($array = array())
    {
        if($array) $this->fromArray($array);
    }

    public function toArray()
    {
        $class_vars = get_class_vars(__CLASS__);
        $toArray = array();
        foreach($class_vars as $name => $value)
            if(!empty($this->$name)) $toArray[$name] = $this->$name;

        return $toArray;

    }

    public function get($field)
    {
        
        $class_vars = get_class_vars(__CLASS__);
        $var_keys = array_keys($class_vars);        

        if(in_array($field,$var_keys)) return $this->$field;

    }

    public function fromArray($fields)
    {
        static $fieldlist  = array(
            'securityname',
            'groupname',
            'securitymodel'               
        );

        foreach($fields as $field=>$value){            
                if(in_array($field,$fieldlist)){           
                    $this->$field = $value;
                }else return false;
        }
        return true;

    }
    
}

?>