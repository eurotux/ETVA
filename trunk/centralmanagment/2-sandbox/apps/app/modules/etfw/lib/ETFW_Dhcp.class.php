<?php
class ETFW_Dhcp
{
    private $coll_subnet = array();    
    private $coll_group = array();
    private $coll_sharednetwork = array();

    private $host_parents = array();
    private $group_hosts = array();
    private $group_hosts_parents = array(); // group with parent uuid
    
    private $subnet_shared = array();


    private $subnet = array('groups_list'=>array('success'=>true,'data'=>array()),
                                                      'hosts_list'=>array('success'=>true,'data'=>array())
                                              );
    private $sharednetwork = array('subnets_list'=>array('success'=>true,'data'=>array()),
                                                      'groups_list'=>array('success'=>true,'data'=>array()),
                                                      'hosts_list'=>array('success'=>true,'data'=>array())
                                                     );     
    
    private $data = array();

    private $action = null; //stores the action instantiated list_subnet, list_pool....


    /*
     * NOTICE:
     *      if a field has hifen '-' it will be checked also if it is a array structure
     *      Ex: parent-type checks for an array('parent'=>array('type'=>value))
     */

    private $list_clientoptions_fields = array(
                'filename',
                'next-server',
                'dynamic-bootp-lease-length',
                'ddns-rev-domainname',
                'default-lease-time',
                'max-lease-time',
                'server-name',
                'dynamic-bootp-lease-cutoff',
                'ddns-domainname',
                'ddns-hostname',
                'ddns-updates',
                'use-host-decl-names',
                'ddns-update-style',
                'authoritative',
                'option',
                'allow-fields',
                'deny-fields',
                'ignore-fields'
    );

    private $list_host_fields = array(
            'uuid',
            'host',
            'assigned',
            'assigned_net',
            'assigned_type',
            'option',
            'params'=>array(                
                'host',
                'lastcomment',
                'hardware',
                'fixed-address',
                'parent-type',
                'parent-uuid',
                'allow-fields',
                'deny-fields',
                'ignore-fields',

            // ------------ //
                'filename',
                'next-server',
                'dynamic-bootp-lease-length',
                'ddns-rev-domainname',
                'default-lease-time',
                'max-lease-time',
                'server-name',
                'dynamic-bootp-lease-cutoff',
                'ddns-domainname',
                'ddns-hostname',
                'ddns-updates'
            )
    );
    

    private $list_group_fields = array(
            'uuid',
            'group',
            'assigned',            
            'assigned_type',
            'parent',
            'option',
            'params'=>array(
                'lastcomment',                
                'parent-type',
                'parent-uuid',
                'hosts',
                'use-host-decl-names',
                'allow-fields',
                'deny-fields',
                'ignore-fields',

            // ------------ //
                'filename',
                'next-server',
                'dynamic-bootp-lease-length',
                'ddns-rev-domainname',
                'default-lease-time',
                'max-lease-time',
                'server-name',
                'dynamic-bootp-lease-cutoff',
                'ddns-domainname',
                'ddns-hostname',
                'ddns-updates'
            )
    );

    private $list_pool_fields = array(
            'uuid',
            'name',
            'range_display',            
            'parent-name',
            'parent-uuid',
            'parent-type',
            'parent-args',
            'params'=>array(
                'range',
                'allow-fields',
                'deny-fields',
                'ignore-fields',
                'allow',
                'deny',
                'failover',
            // ------------ //
                'filename',
                'next-server',
                'dynamic-bootp-lease-length',
                'ddns-rev-domainname',
                'default-lease-time',
                'max-lease-time',
                'server-name',
                'dynamic-bootp-lease-cutoff',
                'ddns-domainname',
                'ddns-hostname',
                'ddns-updates'
            )
    );

    private $list_subnet_fields = array(
            'uuid',
            'address',
            'args',
            'netmask',
            'parent-name',
            'parent-type',
            'option',
            'params'=>array(
                'address',
                'netmask',
                'lastcomment',
                'parent-uuid',
                'parent-type',
                'hosts',
                'groups',
                'authoritative',
                'range',
                'allow-fields',
                'deny-fields',
                'ignore-fields',
            //----------//
                'filename',
                'next-server',
                'dynamic-bootp-lease-length',
                'ddns-rev-domainname',
                'default-lease-time',
                'max-lease-time',
                'server-name',
                'dynamic-bootp-lease-cutoff',
                'ddns-domainname',
                'ddns-hostname',
                'ddns-updates'
                )
        );


    private $list_sharednetwork_fields = array(
            'uuid',
            'name',
            'authoritative_txt',
            'lastcomment',
            'option',
            'params'=>array(
                'name',
                'authoritative',
                'lastcomment',                
                'hosts',
                'groups',
                'subnets',                                
                'allow-fields',
                'deny-fields',
                'ignore-fields',
            //----------//
                'filename',
                'next-server',
                'dynamic-bootp-lease-length',
                'ddns-rev-domainname',
                'default-lease-time',
                'max-lease-time',
                'server-name',
                'dynamic-bootp-lease-cutoff',
                'ddns-domainname',
                'ddns-hostname',
                'ddns-updates'
                )
        );
        
   

    public function ETFW_Dhcp($data,$action=null)
    {
        $this->action = $action;
        switch($action){
            case 'list_clientoptions':
                        $this->buildListClientOption($data);
                        break;
            case 'list_subnet':
                        $this->buildListNetwork($data);
                        break;
            case 'list_sharednetwork':                        
                        $this->buildListNetwork($data);
                        break;
            case 'list_pool':
                        $this->buildListPool($data);
                        break;
            case 'list_host':
                        $this->buildListHost($data);
                        break;
            case 'list_group':
                        $this->buildListGroup($data);
                        break;
            default:
                        $this->buildData($data);
                        break;
        }        
    }

    private function buildListClientOption($dhcp_data)
    {
        $record = array();
        $default_fields = $this->{$this->action.'_fields'};

        foreach($default_fields as $field_index=>$field_value){
            // if is array get associative name
            if(is_array($field_value))
                $field_value = $field_index;

            switch($field_value){
                 
                 case 'option' :                                
                                $dhcp_data_option = array();
                                if(isset($dhcp_data[$field_value])) $dhcp_data_option = $dhcp_data[$field_value];
                                $options = $this->build_options($dhcp_data_option);
                                break;
                 case 'allow-fields' :
                 case 'deny-fields' :
                 case 'ignore-fields' :
                                $field_value = str_replace('-fields','',$field_value);
                                if(isset($dhcp_data[$field_value]))
                                    $this->build_allow_deny_ignore_fields($record,$field_value,$dhcp_data);
                                break;
                     default :
                                $this->returnInnerValues($record,$dhcp_data,$field_value);
                                break;
            }

        }
               
        $this->data = array_merge($record,$options);
    }


    private function buildListGroup($dhcp_data)
    {        
        //subnets info
        $subnets = $dhcp_data['subnets'];
        $list_subnets = array();
        foreach($subnets as $data_subnet){
            $subnet_dec = (array) $data_subnet;
            $data_parent = (array) $subnet_dec['parent'];
            $data_parent['type'] = isset($data_parent['type']) ? $data_parent['type'] : '';
            switch($data_parent['type']){
                    case 'shared-network':
                                    $list_subnets[$subnet_dec['uuid']] = $subnet_dec['address'].' in '.$data_parent['name'];
                                    break;
                    case '':
                                    $list_subnets[$subnet_dec['uuid']] = $subnet_dec['address'];
                                    break;

                   default:break;

            }
        }

        $groups = $dhcp_data['groups'];
        foreach($groups as $index=>$data){
            // builds on record
            $this->data[] = $this->buildGroupData((array) $data,$list_subnets);
        }
    }

    private function buildGroupData($dhcp_data,$list_subnets)
    {
        $record = array();
        $default_fields = $this->{$this->action.'_fields'};

        $group_parent = (!empty($dhcp_data['parent'])) ? (array) $dhcp_data['parent'] : array('uuid'=>'','type'=>'');
        $hosts_data = (array) $dhcp_data['hosts'];
        //hosts
        $coll_hosts = array();
        foreach($hosts_data as $host){
            $host_dec = (array) $host;
            $parent_data = (array) $host_dec['parent'];
            if($parent_data['type']=='group') $coll_hosts[] = $host_dec['uuid'];
        }

        // get assigned element        
        $assigned = $parent = '';
        switch($group_parent['type']){
            case 'shared-network':
                        $parent = $assigned = $group_parent['name'];
                    break;
            case 'subnet':
                        $assigned = $list_subnets[$group_parent['uuid']];
                        $parent = str_replace(' netmask ','/',$group_parent['args']);
                        break;

            default:break;
        }

        foreach($default_fields as $field_index=>$field_value){
            // if is array get associative name
            if(is_array($field_value))
                $field_value = $field_index;

            switch($field_value){
                 case 'params' :
                                $param_fields = $default_fields[$field_index];
                                $record[$field_value] = $this->build_params($param_fields,$dhcp_data);
                                break;
                 case 'assigned' :
                                $record[$field_value] = $assigned;
                                break;
                 case 'group' :                                
                                $hosts_count = count($hosts_data);
                                $record[$field_value] = $hosts_count.' members';
                                break;
                 case 'parent' :
                                $record[$field_value] = $parent;
                                break;
                 case 'assigned_type' :
                                $record[$field_value] = $group_parent['type'];
                                break;
                 case 'option' :                                
                                $dhcp_data_option = array();
                                if(isset($dhcp_data[$field_value])) $dhcp_data_option = $dhcp_data[$field_value];
                                $record[$field_value] = $this->build_options($dhcp_data_option);
                                break;
                     default :
                                $this->returnInnerValues($record,$dhcp_data,$field_value);
                                break;
            }

        }
        return $record;
    }

    /*
     * return group grid fields
     */
    public function getGridListGroupFields()
    {
        $fields_names = array();
        foreach($this->list_group_fields as $k=>$v)
            if(is_array($v)) $fields_names[] = array("name"=>$k);
            else $fields_names[] = array("name"=>$v);

        return $fields_names;
    }

    private function buildListHost($dhcp_data)
    {

        $groups = $dhcp_data['groups'];
        $list_groups = $list_assigned_net = array();

        foreach($groups as $data_group){
            $group = (array) $data_group;
            $group_parent = (array) $group['parent'];
            $group_parent['type'] = isset($group_parent['type']) ? $group_parent['type'] : '';
            $hosts_data = (array) $group['hosts'];
            $hosts_count = count($hosts_data);

            switch($group_parent['type']){
                case 'subnet':
                                $list_assigned_net[$group['uuid']] = " in subnet ".str_replace(' netmask ','/',$group_parent['args']);
                                if(empty($group_parent['parent'])){
                                    $list_groups[$group['uuid']] = $hosts_count.' members in '.$group_parent['address'];
                                }else{
                                    $aux_data = (array) $group_parent['parent'];
                                    $list_groups[$group['uuid']] = $hosts_count.' members in '.$group_parent['address'].' in '.$aux_data['name'];
                                }
                                break;
                case 'shared-network':
                                $list_assigned_net[$group['uuid']] = " in shared network ".$group_parent['name'];
                                $list_groups[$group['uuid']] = $hosts_count.' members in '.$group_parent['name'];
                                break;

                case '':
                                $list_groups[$group['uuid']] = $hosts_count.' members';
                                break;
                default:break;
            }

        }
        
        //subnets info
        $subnets = $dhcp_data['subnets'];
        $list_subnets = array();
        foreach($subnets as $data_subnet){
            $subnet_dec = (array) $data_subnet;
            $data_parent = (array) $subnet_dec['parent'];
            $data_parent['type'] = isset($data_parent['type']) ? $data_parent['type'] : '';
            switch($data_parent['type']){
                    case 'shared-network':
                                    $list_subnets[$subnet_dec['uuid']] = $subnet_dec['address'].' in '.$data_parent['name'];
                                    break;
                    case '':
                                    $list_subnets[$subnet_dec['uuid']] = $subnet_dec['address'];
                                    break;

                   default:break;

            }
        }

        $hosts = $dhcp_data['hosts'];
        foreach($hosts as $index=>$data){
            // builds on record
            $this->data[] = $this->buildHostData((array) $data,$list_assigned_net,$list_groups,$list_subnets);            
        }        
    }

    private function buildHostData($dhcp_data,$list_assigned_net,$list_groups,$list_subnets)
    {
        $record = array();
        $default_fields = $this->{$this->action.'_fields'};

        $data_parent = (isset($dhcp_data['parent']) && !empty($dhcp_data['parent'])) ? (array) $dhcp_data['parent'] : array('type'=>'','uuid'=>'');
     
        // get assigned element
        $data_parent_type = $data_parent['type'];        
        $assigned = $assigned_net = '';
        switch($data_parent_type){
            case 'shared-network':
                        $assigned = $data_parent['name'];
                    break;
            case 'subnet':
                        $assigned = $list_subnets[$data_parent['uuid']];
                        $assigned_net = " in subnet ".str_replace(' netmask ','/',$data_parent['args']);
                        break;
            case 'group':
                        $assigned = $list_groups[$data_parent['uuid']];
                        $assigned_net = isset($list_assigned_net[$data_parent['uuid']]) ? $list_assigned_net[$data_parent['uuid']] : '';
                        break;
            default:break;
        }




        foreach($default_fields as $field_index=>$field_value){
            // if is array get associative name
            if(is_array($field_value))
                $field_value = $field_index;

            switch($field_value){             
                 case 'params' :
                                $param_fields = $default_fields[$field_index];
                                $record[$field_value] = $this->build_params($param_fields,$dhcp_data);
                                break;                
                 case 'assigned' :                                                                                              
                                $record[$field_value] = $assigned;
                                break;
                 case 'assigned_net' :
                                $record[$field_value] = $assigned_net;
                                break;
                 case 'assigned_type' :
                                $record[$field_value] = $data_parent_type;
                                break;
                 case 'option' :                                
                                $dhcp_data_option = array();
                                if(isset($dhcp_data[$field_value])) $dhcp_data_option = $dhcp_data[$field_value];
                                $record[$field_value] = $this->build_options($dhcp_data_option);
                                break;
                     default :
                                $this->returnInnerValues($record,$dhcp_data,$field_value);
                                break;
            }


        }
        return $record;
    }

    /*
     * return host grid fields
     */
    public function getGridListHostFields()
    {
        $fields_names = array();
        foreach($this->list_host_fields as $k=>$v)
            if(is_array($v)) $fields_names[] = array("name"=>$k);
            else $fields_names[] = array("name"=>$v);

        return $fields_names;
    }

    /*
     * return subnet grid fields
     */
    public function getGridListSubnetFields()
    {
        $fields_names = array();
        foreach($this->list_subnet_fields as $k=>$v)
            if(is_array($v)) $fields_names[] = array("name"=>$k);
            else $fields_names[] = array("name"=>$v);
        
        return $fields_names;
    }

    /*
     * return shared network grid fields
     */
    public function getGridListSharednetworkFields()
    {
        $fields_names = array();
        foreach($this->list_sharednetwork_fields as $k=>$v)
            if(is_array($v)) $fields_names[] = array("name"=>$k);
            else $fields_names[] = array("name"=>$v);

        return $fields_names;
    }

    /*
     * builds json store data for network
     */
    private function buildListNetwork($dhcp_data)
    {
        $i = 0;
        foreach($dhcp_data as $index=>$data){
            // builds on record
            $this->data[$i] = $this->buildNetworkData((array) $data);
            $i++;           
        }
    }

    /*
     * builds one network grid record
     */
    private function buildNetworkData($dhcp_data)
    {        
        $record = array();
        $default_fields = $this->{$this->action.'_fields'};
        //$default_fields = $this->list_subnet_fields;
        foreach($default_fields as $field_index=>$field_value){
            // if is array get associative name
            if(is_array($field_value))
                $field_value = $field_index;            

            switch($field_value){

               case 'option' :                                
                                $dhcp_data_option = array();
                                if(isset($dhcp_data[$field_value])) $dhcp_data_option = $dhcp_data[$field_value];
                                $record[$field_value] = $this->build_options($dhcp_data_option);                                
                                break;
               case 'params' :

                                $param_fields = $default_fields[$field_index];
                                $record[$field_value] = $this->build_params($param_fields,$dhcp_data);
                                break;
    case 'authoritative_txt' :
                                $record[$field_value] = isset($dhcp_data['authoritative']) ? 'Yes' : 'No';
                                break;
                 case 'args' :
                                $value = '';
                                if(isset($dhcp_data[$field_value]))
                                    $value = str_replace(' netmask ','/',$dhcp_data[$field_value]);
                                $record[$field_value] = $value;
                                break;                

                     default :
                                $this->returnInnerValues($record,$dhcp_data,$field_value);
                                break;
            }
                
            
        }
        return $record;                 
    }

    private function build_options($dhcp_data)
    {
        $result = array();

        foreach($dhcp_data as $index_option=>$data_option)
            $result['option_'.$index_option] = $data_option;        

        return $result;
    }

    /*
     * build params for Extjs presentation
     */
    private function build_params($params,$dhcp_data)
    {
        $result = array();

        $parent_type = '';
        switch($this->action){
            case 'list_subnet':
                $parent_type = 'subnet';
                break;
            case 'list_sharednetwork':
                $parent_type = 'shared-network';
                break;
            case 'list_group':
                $parent_type = 'group';
                break;
            default:
                break;
        }

        foreach($params as $param_name){

            switch($param_name){
                    case 'allow-fields' :
                     case 'deny-fields' :
                   case 'ignore-fields' :                       
                                    $param_name = str_replace('-fields','',$param_name);                                    
                                    if(isset($dhcp_data[$param_name]))
                                        $this->build_allow_deny_ignore_fields($result,$param_name,$dhcp_data);
                                    break;
                    case 'hosts' :
                                    $coll_hosts = array();                                   
                                    if(isset($dhcp_data[$param_name])){
                                        $hosts = $dhcp_data[$param_name];
                                        foreach($hosts as $host){
                                             //get data with type==parent type network
                                             $host_dec = (array) $host;
                                             $parent_data = (array) $host_dec['parent'];
                                             if($parent_data['type']==$parent_type) $coll_hosts[] = $host_dec['uuid'];
                                         }
                                    }
                                    $result[$param_name] = $coll_hosts;
                                    break;
                   case 'groups' :                        
                                    $coll_groups = array();                                                                       
                                    if(isset($dhcp_data[$param_name])){
                                        $groups = $dhcp_data[$param_name];
                                        foreach($groups as $group){
                                            //get data with type==parent type network
                                            $group_dec = (array) $group;
                                            $parent_data = (array) $group_dec['parent'];
                                            if($parent_data['type']==$parent_type) $coll_groups[] = $group_dec['uuid'];
                                        }
                                    }
                                    $result[$param_name] = $coll_groups;
                                    break;
                   case 'subnets' :                                    
                                    $coll_subnets = array();
                                    if(isset($dhcp_data[$param_name])){
                                        $subnets = $dhcp_data[$param_name];
                                        foreach($subnets as $subnet){
                                            //get data with type==parent type network
                                            $subnet_dec = (array) $subnet;
                                            $parent_data = (array) $subnet_dec['parent'];
                                            if($parent_data['type']==$parent_type) $coll_subnets[] = $subnet_dec['uuid'];
                                        }
                                    }
                                    $result[$param_name] = $coll_subnets;
                                    break;
                    case 'allow' :
                    case 'deny' :
                                    $param_array = array();
                                    if(isset($dhcp_data[$param_name])){
                                        $param_array = is_array($dhcp_data[$param_name]) ? $dhcp_data[$param_name] : array($dhcp_data[$param_name]);
                                    }
                                    $result[$param_name] = implode("\r\n",$param_array);
                                    break;
                    case 'range' :
                                    $range_array = array();
                                    if(isset($dhcp_data['range']))
                                        $range_array = is_array($dhcp_data['range']) ? $dhcp_data['range'] : array($dhcp_data['range']);
                                    $result[$param_name] = $range_array;
                                    
                                    break;
                         default :  $this->returnInnerValues($result,$dhcp_data,$param_name);
                                    break;
            }            
        }
        return $result;
    }


    /*
     * format data accordly
     */
    private function build_allow_deny_ignore_fields(&$record,$condition, $data)
    {
        $options = $data[$condition];

        if(is_string($options))
            $record[$options] = $condition;
        else
            foreach($options as $opt)
                $record[$opt] = $condition;
    }
    


    /*
     * used internally to get data from arrays.
     * Example: parent-type means that it maybe exists array('parent'=>array('type'=>...))
     */
    private function returnInnerValues(&$record,$dhcp_data,$field)
    {
        if(!isset($record[$field])){            
            $is_struct = strpos($field,'-');
            if(!($is_struct===false)){

                    $pieces = explode('-',$field,2);
                    $array_name = $pieces[0];
                    $array_field = $pieces[1];

                    $dhcp_array = isset($dhcp_data[$array_name]) ? (array) $dhcp_data[$array_name] : array();


                    if(isset($dhcp_array[$array_field]))
                        $record[$field] = $dhcp_array[$array_field];                        
                    else{

                        if(isset($dhcp_data[$field]))
                            $record[$field] = $dhcp_data[$field];
                    }
            }else{
                if(isset($dhcp_data[$field]))
                        $record[$field] = $dhcp_data[$field];
                    
            }
        }
    }


    /*
     * builds json store data for pool grid
     */
    private function buildListPool($dhcp_data)
    {
        $index = 1;
        foreach($dhcp_data as $data){
            // builds on record
            $this->data[] = $this->buildPoolData($index,(array) $data);
            $index++;
        }
    }

    private function buildPoolData($index,$dhcp_data)
    {
        $record = array();
        $default_fields = $this->{$this->action.'_fields'};
        
        foreach($default_fields as $field_index=>$field_value){
            // if is array get associative name
            if(is_array($field_value))
                $field_value = $field_index;

            switch($field_value){
                case 'params' :
                                $param_fields = $default_fields[$field_index];                                
                                $record[$field_value] = $this->build_params($param_fields,$dhcp_data);
                                break;
                case 'name' :
                                $record[$field_value] = 'Pool '.$index;
                                break;
                case 'parent-name' :
                                $parent = (array) $dhcp_data['parent'];
                                switch($parent['type']){
                                    case 'subnet':
                                                    $record[$field_value] = $parent['address'];
                                                    break;
                                    case 'shared-network' :
                                                    $record[$field_value] = $parent['name'];
                                                    break;
                                    default:        break;
                                }                                
                                break;
                case 'range_display' :
                                $range_init = (isset($record['range'])) ? true : false;
                                $dhcp_data['range'] = isset($dhcp_data['range']) ? $dhcp_data['range'] : '';

                                if(!$range_init)
                                    $range_array = is_array($dhcp_data['range']) ? $dhcp_data['range'] : array($dhcp_data['range']);
                                else
                                    $range_array = $record['range'];
                                                                                               
                                foreach($range_array as $range){
                                    $bootp='No';
                                    if(strpos($range,'dynamic-bootp ')!==false){
                                        $bootp='Yes';
                                        $range = str_replace('dynamic-bootp ','',$range);
                                    }
                                    $record['range_display'][] = array('address'=>$range,'bootp'=>$bootp);
                                }
                default :
                                $this->returnInnerValues($record,$dhcp_data,$field_value);
                                break;
            }


        }
        return $record;
    }

    /*
     * return pool grid fields
     */
    public function getGridListPoolFields()
    {
        $fields_names = array();
        foreach($this->list_pool_fields as $k=>$v)
            if(is_array($v)) $fields_names[] = array("name"=>$k);
            else $fields_names[] = array("name"=>$v);

        return $fields_names;
    }

    private function buildData($dhcp_data)
    {
        $data_name = 'sharednetworks';
        $data = $dhcp_data[$data_name];
        $this->build_sharednetworks($data); 

        $data_name = 'groups';
        $data = $dhcp_data[$data_name];
        $this->build_groups($data);

        $data_name = 'hosts';
        $data = $dhcp_data[$data_name];
        $this->build_hosts($data);

        $data_name = 'subnets';
        $data = $dhcp_data[$data_name];
        $this->build_subnets($data);


        while(list($g_uuid) = current($this->group_hosts_parents))
        {
            foreach($this->host_parents as $host_uuid=>$host_parent)
                if($g_uuid==$host_parent['uuid'])
                    $this->group_hosts[key($this->group_hosts_parents)]['data'][] = array('uuid'=>$host_uuid,'host'=>$host_parent['host']);

            next($this->group_hosts_parents);
        }
                   
    }    

    private function build_sharednetworks($data)
    {        
        $data_shared_networks = (array) $data;

        //shared networks....
        foreach($data_shared_networks as $data_shared_network){
            $shared_network = (array) $data_shared_network;
            $name = $shared_network['name'];
            $uuid = $shared_network['uuid'];
            $this->coll_sharednetwork[] = array('uuid'=>$uuid,'type'=>'shared-network','value'=>$name);

            $this->subnet_shared['hosts_list'][$uuid] = array('success'=>true,'data'=>array());
            $this->subnet_shared['groups_list'][$uuid] = array('success'=>true,'data'=>array());
        }
    }

    private function build_subnets($data)
    {
        $data_subnets = (array) $data;
                               
        foreach($data_subnets as $data_subnet)
        {
            $subnet_dec = (array) $data_subnet;
            $data_parent = (array) $subnet_dec['parent'];
            $this->sharednetwork['subnets_list']['data'][] = array('uuid'=>$subnet_dec['uuid'],'subnet'=>$subnet_dec['address']);
            $data_parent['type'] = isset($data_parent['type']) ? $data_parent['type'] : '';
            switch($data_parent['type'])
            {
                    case 'shared-network':
                                        $this->coll_subnet[] = array('uuid'=>$subnet_dec['uuid'],'type'=>'subnet','value'=>$subnet_dec['address'].' in '.$data_parent['name']);
                                        break;
                    case '':
                                        $this->coll_subnet[] = array('uuid'=>$subnet_dec['uuid'],'type'=>'subnet','value'=>$subnet_dec['address']);
                                        break;

                    default:            break;
           }
        }
    }

    private function build_groups($data)
    {

        //groups_info
        $data_groups = (array) $data;

        foreach($data_groups as $data_group)
        {
            $group = (array) $data_group;
            $group_parent = (!empty($group['parent'])) ? (array) $group['parent'] : array('type'=>'');
            $hosts_data = (array) $group['hosts'];
            $hosts_count = count($hosts_data);

            $this->group_hosts[$group['uuid']] = array('success'=>true,'data'=>array());

            switch($group_parent['type']){
                case 'subnet':

                             $this->group_hosts_parents[$group['uuid']][] = $group_parent['uuid'];

                             if(empty($group_parent['parent'])){
                                 $this->coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members in '.$group_parent['address']);
                                 $this->subnet['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                             }else{
                                $aux_data = (array) $group_parent['parent'];
                                $this->coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members in '.$group_parent['address'].' in '.$aux_data['name']);
                                $this->subnet_shared['groups_list'][$aux_data['uuid']]['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                             }
                              break;
                case 'shared-network':

                            $this->group_hosts_parents[$group['uuid']][] = $group_parent['uuid'];
                            $this->sharednetwork['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                            $this->subnet_shared['groups_list'][$group_parent['uuid']]['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                            $this->coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members in '.$group_parent['name']);
                            break;

                case '':
                            $this->group_hosts_parents[$group['uuid']][] = '';
                            $this->sharednetwork['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                            $this->subnet['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                            $this->coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members');
                            break;
                default:    break;
           }
        }
    }



    private function build_hosts($data)
    {
        $data_hosts = (array) $data;

        foreach($data_hosts as $data_host)
        {
            $host = (array) $data_host;
            $host_parent = (!empty($host['parent'])) ? (array) $host['parent'] : array('type'=>'');

            switch($host_parent['type']){
                case 'subnet':  $this->host_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>$host_parent['uuid']);
                                if(empty($host_parent['parent']))
                                {
                                    $this->subnet['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                }else{
                                    $aux_data = (array) $host_parent['parent'];
                                    $this->subnet_shared['hosts_list'][$aux_data['uuid']]['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                }
                                break;
                case 'shared-network':
                                $this->sharednetwork['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                $this->subnet_shared['hosts_list'][$host_parent['uuid']]['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                break;
                case 'group':
                                if(!empty($host_parent['parent']))
                                {
                                    $aux_data = (array) $host_parent['parent'];
                                    $this->host_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>$aux_data['uuid']);
                                }else{
                                    $this->host_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>'');
                                    $this->group_hosts['']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                }
                                break;
                case '':
                                $this->host_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>'');

                                $this->group_hosts['']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                $this->sharednetwork['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                $this->subnet['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                break;
                default:        break;

            }
        }
    }

    public function getCollSharednetwork(){
        return $this->coll_sharednetwork;
    }

    public function getSubnets()
    {
        return $this->subnet;
    }

    public function getSubnetshared()
    {
        return $this->subnet_shared;
    }


    public function getSharednetworks()
    {
        return $this->sharednetwork;
    }

    public function getGrouphosts()
    {
        return $this->group_hosts;
    }   

    public function getAssignedTo()
    {
        $assigned_to = array('success'=>true,
                            'data'=>array_merge($this->coll_subnet
                                                ,$this->coll_sharednetwork
                                                ,$this->coll_group));
        return $assigned_to;
    }

    public function getAllData()
    {
        return $this->data;
    }

}
?>