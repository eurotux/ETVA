<?php

/**
 * ETFW actions.
 *
 * @package    centralM
 * @subpackage ETFW
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12479 2008-10-31 10:54:40Z fabien $
 */
class etfwActions extends sfActions
{
    public function executeView(sfWebRequest $request)
    {        
        $dispatcher_id = $request->getParameter('dispatcher_id');        

        // used to get parent id component (extjs)
        //$this->containerId = $request->getParameter('containerId');
        
        // load modules file of dispatcher
        if($dispatcher_id){

            $criteria = new Criteria();
            $criteria->add(EtvaServicePeer::ID,$dispatcher_id);
            //$criteria->add(EtvaServicePeer::NAME_TMPL,$dispatcher);

            $etva_service = EtvaServicePeer::doSelectOne($criteria);
            $dispatcher = $etva_service->getNameTmpl();
            $etva_server = $etva_service->getEtvaServer();

            $tmpl = $etva_server->getAgentTmpl().'_'.$dispatcher.'_modules';
            $directory = $this->context->getConfiguration()->getTemplateDir('etfw', '_'.$tmpl.'.php');

            if($directory)
                return $this->renderPartial($tmpl);
            else
                return $this->renderText('Template '.$tmpl.' not found');
        }else{
            $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
        }
        
    }
     
    public function executeETFW_wizard(sfWebRequest $request)
    {
        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
        $criteria = new Criteria();
        $criteria->add(EtvaServicePeer::SERVER_ID,$etva_server->getId());
        $criteria->add(EtvaServicePeer::NAME_TMPL,'wizard');
        $network_dispatcher = EtvaServicePeer::doSelectOne($criteria);
        $this->wizard_dispatcher_id = $network_dispatcher->getId();
        $this->wizard_name = $request->getParameter('tpl');
        $this->containerId = $request->getParameter('containerId');

        if($this->wizard_name=='dhcp'){
            //get active interfaces info...
            $active_ifaces = $this->ETFW_network($etva_server, 'active_interfaces', array(), '');
            if($active_ifaces['success']){
                $data = $active_ifaces['data'];
                $ifaces = array();
                foreach ($data as $key => $val) {
                    $ifaces[$val['fullname']] = $data[$key];
                }
                $this->interfaces = $ifaces;
            }

        }
        
    }

    /*
     * processes ETFW json requests and invokes dispatcher
     */
    public function executeJson(sfWebRequest $request)
    {
        $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('id'));

        if(!$etva_service){
            $msg = array('success'=>false,'error'=>'No service with specified id','info'=>'No service with specified id');
            $result = $this->setJsonError($msg);
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($result);
        }

        $etva_server = $etva_service->getEtvaServer();

        $agent_tmpl =$etva_server->getAgentTmpl();
        $service_tmpl = $etva_service->getNameTmpl();
        $method = $request->getParameter('method');
        $mode = $request->getParameter('mode');
        $params = json_decode($request->getParameter('params'),true);

        if(!$params) $params = array();


        $dispatcher_tmpl = $agent_tmpl.'_'.$service_tmpl;

        if(method_exists($this,$dispatcher_tmpl))
        {
            $ret = call_user_func_array(array($this, $dispatcher_tmpl), array($etva_server,$method,$params,$mode));

            if($ret['success'])
                $result = json_encode($ret);
                //$result = json_encode(array(utf8_encode($ret)));
            else
                $result = $this->setJsonError($ret);
        }else{
            $info = array('success'=>false,'error'=>'No method implemented! '.$dispatcher_tmpl);
            $result = $this->setJsonError($info);
        }


            // $result = json_encode($ret);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setHttpHeader("X-JSON", '()');

        return $this->renderText($result);

    }

    public function executeJsonMainETFW( sfWebRequest $request )
    {
        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));

        $method = $request->getParameter('method');
        $mode = $request->getParameter('mode');
        $params = json_decode($request->getParameter('params'),true);

        if(!$params) $params = array();

	$ret = $this->ETFW_main($etva_server, $method, $params, $mode);

	if($ret['success'])
            $result = json_encode($ret);
        else
            $result = $this->setJsonError($ret);

	$result = array();

        $this->getResponse()->setHttpHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setHttpHeader("X-JSON", '()');

        return $this->renderText($result);

    }

    /*
     * ETFW network dispatcher...
     */
    public function ETFW_network(EtvaServer $etva_server, $method, $params,$mode)
    {

        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'network'
        );

        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
                case 'get_boot_routing':
                                            $elements = array();

                                            foreach($response_decoded as $dataType=>$data){
                                                // check if is an object (array to decode)
                                                if(is_array($data)){

                                                    $array_values = array();
                                                    foreach($data as $k=>$datavalues){
                                                        $data_dec = (array) $datavalues;
                                                        $array_values[] = $data_dec;

                                                        //$elements[$dataType][] = $data_dec;
                                                    }

                                                    $elements['total'.$dataType] = count($array_values);
                                                    $elements['data'.$dataType] = $array_values;

                                                }else{


                                                    $elements[$dataType] = array(array('value'=>$data));
                                                }

                                            }

                                            $elements['success'] = true;
                                            $return = $elements;
                                            break;

                case 'boot_real_interfaces':$elements = array();

                                            foreach($response_decoded as $intf=>$data){
                                                $data_dec = (array) $data;

                                                if(!isset($data_dec['virtual']))
                                                $elements[] = $data_dec;

                                            }


                                            $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                            break;

                 case 'get_hostname_dns' :
                                            $elements = array();

                                            $order_data = (array) $response_decoded['order'];
                                            $order_array = array();
                                            foreach($order_data as $v){
                                                    $order_array[] = $v;

                                            }
                                            $elements['order0'] = isset($order_array[0]) ? $order_array[0] :'';
                                            $elements['order1'] = isset($order_array[1]) ? $order_array[1] :'';
                                            $elements['order2'] = isset($order_array[2]) ? $order_array[2] :'';
                                            $elements['order3'] = isset($order_array[3]) ? $order_array[3] :'';

                                            $nameserver_data = (array) $response_decoded['nameserver'];
                                            $nameserver_array = array();
                                            foreach($nameserver_data as $v){
                                                    $nameserver_array[] = $v;

                                            }
                                            $elements['nameserver0'] = isset($nameserver_array[0]) ? $nameserver_array[0] :'';
                                            $elements['nameserver1'] = isset($nameserver_array[1]) ? $nameserver_array[1] :'';
                                            $elements['nameserver2'] = isset($nameserver_array[2]) ? $nameserver_array[2] :'';

                                            $domain_data = (array) $response_decoded['domain'];
                                            $domain_array = array();
                                            foreach($domain_data as $v){
                                                    $domain_array[] = $v;

                                            }

                                            $string_domain = implode("\r\n",$domain_array);

                                            $elements['domain'] = $string_domain;

                                            if($elements['domain']) $elements['search'] = 1;
                                            else $elements['search'] = 0;
                                            // $elements['domain'] = 'dada';

                                            $elements['hostname'] = $response_decoded['hostname'];


                                            $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                            break;

                case 'active_interfaces' :
                case 'boot_interfaces' :
                                            $elements = array();
                                            $occur_virt = array();
                                            foreach($response_decoded as $intf=>$data){
                                                $data_dec = (array) $data;

                                                if(!isset($occur_virt[$data_dec['name']]))
                                                    $occur_virt[$data_dec['name']] = 0;

                                                $occur_virt[$data_dec['name']] +=1;

                                                $elements[] = $data_dec;

                                            }

                                            foreach($elements as &$item){
                                                $item['occur'] = $occur_virt[$item['name']];
                                            }

                                            $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                            break;
                case 'delete_routes' :
                case 'create_route'   :
                case 'activate_interface'   :
                case 'deactivate_interfaces' :
                case 'save_boot_interface' :
                case 'create_host' :
                case 'modify_host':
                case 'delete_host':
                case 'set_boot_routing' :
                case 'set_hostname_dns' :
                case 'del_boot_interfaces' :
                                            $return = array('success' => true);
                                            break;

                case 'list_hosts' :         $elements = array();

                                            foreach($response_decoded as $index=>$data){
                                                $data_dec = (array) $data;

                                                $data_hosts = (array) $data_dec['hosts'];
                                                $hosts = implode(', ',$data_hosts);
                                                $data_address = $data_dec['address'];
                                                $data_index = $data_dec['index'];



                                                $elements[] = array('address'=>$data_address,
                                                                    'id'=>$data_index,
                                                                    'hosts'=>$hosts);

                                            }

                                            $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                            break;

                case 'list_routes' :
                                            $elements = array();

                                            foreach($response_decoded as $intf=>$data){
                                                $data_dec = (array) $data;

                                                $elements[] = $data_dec;

                                            }

                                            $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                            break;

                    default: $return = array('success' => false,'error'=>'No action \''.$method.'\' defined yet',
                                            'info'=>'No action \''.$method.'\' implemented yet');

            }          

            return $return;

        }else{
            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }

    }
    

    /*
     * ETFW firewall dispatcher...
     */
    public function ETFW_firewall(EtvaServer $etva_server, $method, $params,$mode)
    {
        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'firewall'
        );

        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
                case 'get_config_rules' :
                                $rules = (array) $response_decoded['rules'];
                                $boot = (array) $response_decoded['boot'];
                                $boot_active = $boot['active'];
            
                                foreach($rules as &$table){
                                    foreach($table as $chain=>&$chain_data){
                                        $chain_data = (array) $chain_data;
                                        $elements = array();
                                        $i=0;
                                        if(isset($chain_data['rules'])){
                                            $rules_chain = (array) $chain_data['rules'];
                                            foreach($rules_chain as $rule){
                                                $rule = (array) $rule;
                                                $elements[$i]['index'] = $rule['index'];
                                                if(!isset($rule['j'])) $rule['j'] = '';
                                                $elements[$i]['action'] = ETFW_Firewall::describe_rule_action($rule['j']);
                                                $elements[$i]['condition'] = ETFW_Firewall::describe_rule_condition($rule);
                                                $i++;
                                            }
                                        }
                                        $chain_data['rules'] = array('total' =>   count($elements),'data'  => $elements);
                                        $chain_data['chain_desc'] = ETFW_Firewall::describe_rule_chain($chain);
                                    }

                                }
                                
                                $return = array('success' => true,'total' => count($rules),'boot_active'=>$boot_active, 'rules'  => $rules);
                                break;


                                 break;
                case 'get_rule': // return data for EXTJS form presentation

                                 $response_decoded['chain-desc'] = ETFW_Firewall::describe_rule_chain($response_decoded['chain']);

                                 if(isset($response_decoded['reject-with'])) $response_decoded['reject-with-src'] = 'type';

                                 if(isset($response_decoded['to-ports'])){

                                     switch($response_decoded['j']){
                                         case 'MASQUERADE': $flag = 'masq';
                                                            break;
                                           case 'REDIRECT':
                                                            $flag = 'to-ports';
                                                            break;
                                                   default: break;
                                     }

                                     $to_ports = $response_decoded['to-ports'];

                                     $split_to_ports = split('-',$to_ports);
                                     $split_size_to_ports = count($split_to_ports);

                                     if($split_size_to_ports>1 && $split_size_to_ports < 3 ){
                                        $response_decoded[$flag.'-from'] = $split_to_ports[0];
                                        $response_decoded[$flag.'-to'] = $split_to_ports[1];
                                        $response_decoded[$flag.'-src'] = 'range';
                                     }

                                 }

                                 //DNAT
                                 if(isset($response_decoded['to-destination'])){

                                     $response_decoded['to-destination-src'] = 'range';
                                     $to_destination = $response_decoded['to-destination'];
                                     $to_destination_ip = $to_destination_port = array();

                                     $split_to_destination = split(':',$to_destination);
                                     $split_size_to_destination = count($split_to_destination);
                                     if($split_size_to_destination>1 && $split_size_to_destination < 3 )
                                        $to_destination_port = $split_to_destination[1];

                                     $to_destination_ip = $split_to_destination[0];
                                     $split_to_destination_ip = split('-',$to_destination_ip);
                                     $split_size_to_destination_ip = count($split_to_destination_ip);
                                     $response_decoded['to-destination-from'] = $split_to_destination_ip[0];

                                     if($split_size_to_destination_ip>1 && $split_size_to_destination_ip < 3 )
                                        $response_decoded['to-destination-to'] = $split_to_destination_ip[1];

                                     if($to_destination_port){

                                        $split_to_destination_port = split('-',$to_destination_port);
                                        $split_size_to_destination_port = count($split_to_destination_port);
                                        $response_decoded['to-destination-port-from'] = $split_to_destination_port[0];

                                        if($split_size_to_destination_port>1 && $split_size_to_destination_port < 3 )
                                            $response_decoded['to-destination-port-to'] = $split_to_destination_port[1];

                                     }

                                 }

                                 //SNAT
                                 if(isset($response_decoded['to-source'])){

                                     $response_decoded['to-source-src'] = 'range';
                                     $to_source = $response_decoded['to-source'];
                                     $to_source_ip = $to_source_port = array();

                                     $split_to_source = split(':',$to_source);
                                     $split_size_to_source = count($split_to_source);
                                     if($split_size_to_source>1 && $split_size_to_source < 3 )
                                        $to_source_port = $split_to_source[1];

                                     $to_source_ip = $split_to_source[0];
                                     $split_to_source_ip = split('-',$to_source_ip);
                                     $split_size_to_source_ip = count($split_to_source_ip);
                                     $response_decoded['to-source-from'] = $split_to_source_ip[0];

                                     if($split_size_to_source_ip>1 && $split_size_to_source_ip < 3 )
                                        $response_decoded['to-source-to'] = $split_to_source_ip[1];

                                     if($to_source_port){

                                        $split_to_source_port = split('-',$to_source_port);
                                        $split_size_to_source_port = count($split_to_source_port);
                                        $response_decoded['to-source-port-from'] = $split_to_source_port[0];

                                        if($split_size_to_source_port>1 && $split_size_to_source_port < 3 )
                                            $response_decoded['to-source-port-to'] = $split_to_source_port[1];

                                     }


                                 }

                                 if(isset($response_decoded['s']))
                                 {

                                     $source_addr = $response_decoded['s'];

                                     $response_decoded['s-c'] = '=';
                                     $not_source = strpos($source_addr,'!');
                                     if(!(is_bool($not_source) && $not_source==false)){
                                        $response_decoded['s'] = trim($source_addr,"! ");
                                        $response_decoded['s-c'] = '!';
                                     }

                                 }

                                 if(isset($response_decoded['d']))
                                 {

                                     $dest_addr = $response_decoded['d'];

                                     $response_decoded['d-c'] = '=';
                                     $not_dest = strpos($dest_addr,'!');
                                     if(!(is_bool($not_dest) && $not_dest==false)){
                                        $response_decoded['d'] = trim($dest_addr,"! ");
                                        $response_decoded['d-c'] = '!';
                                    }

                                 }


                                 if(isset($response_decoded['p']))
                                 {

                                     $proto = $response_decoded['p'];

                                     $response_decoded['p-c'] = '=';
                                     $not_proto = strpos($proto,'!');
                                     if(!(is_bool($not_proto) && $not_proto==false)){
                                        $response_decoded['p'] = trim($proto,"! ");
                                        $response_decoded['p-c'] = '!';
                                     }

                                 }

                                 //incoming interface
                                 if(isset($response_decoded['i']))
                                 {

                                     $in_intf = $response_decoded['i'];

                                     $response_decoded['i-c'] = '=';
                                     $not_in_intf = strpos($in_intf,'!');
                                     if(!(is_bool($not_in_intf) && $$not_in_intf==false)){
                                        $response_decoded['i'] = trim($in_intf,"! ");
                                        $response_decoded['i-c'] = '!';
                                     }

                                 }

                                 //outgoing interface
                                 if(isset($response_decoded['o']))
                                 {

                                     $out_intf = $response_decoded['o'];

                                     $response_decoded['o-c'] = '=';
                                     $not_out_intf = strpos($out_intf,'!');
                                     if(!(is_bool($not_out_intf) && $$not_out_intf==false)){
                                        $response_decoded['o'] = trim($out_intf,"! ");
                                        $response_decoded['o-c'] = '!';
                                     }

                                 }

                                 if(!isset($response_decoded['f']))
                                    $response_decoded['f'] = 'ignored';


                                 // source ports comma separated
                                 if(isset($response_decoded['sports']))
                                 {

                                    $sports = $response_decoded['sports'];

                                    $response_decoded['sport-c'] = '=';
                                    $not_sports = strpos($sports,'!');
                                    if(!(is_bool($not_sports) && $not_sports==false)){
                                       $response_decoded['sports'] = trim($sports,"! ");
                                       $response_decoded['sport-c'] = '!';
                                    }

                                    $response_decoded['sport-src'] = '';

                                 }

                                 //source port
                                 if(isset($response_decoded['sport'])){

                                    $sport = $response_decoded['sport'];

                                    $response_decoded['sport-c'] = '=';
                                    $not_sport = strpos($sport,'!');
                                    if(!(is_bool($not_sport) && $not_sport==false)){
                                       $sport = trim($sport,"! ");
                                       $response_decoded['sport-c'] = '!';
                                    }

                                    $response_decoded['sport-src'] = '';

                                    $split_sport = split(':',$sport);
                                    $split_size_sport = count($split_sport);

                                    if($split_size_sport>1 && $split_size_sport < 3 ){
                                        $response_decoded['sport-from'] = $split_sport[0];
                                        $response_decoded['sport-to'] = $split_sport[1];
                                        $response_decoded['sport-src'] = 'range';

                                    }else $response_decoded['sports'] = $sport;

                                 }

                                 // destination ports comma separated
                                 if(isset($response_decoded['dports']))
                                 {

                                    $dports = $response_decoded['dports'];

                                    $response_decoded['dport-c'] = '=';
                                    $not_dports = strpos($dports,'!');
                                    if(!(is_bool($not_dports) && $not_dports==false)){
                                       $response_decoded['dports'] = trim($dports,"! ");
                                       $response_decoded['dport-c'] = '!';
                                    }

                                    $response_decoded['dport-src'] = '';

                                 }
                                 // destination port
                                 if(isset($response_decoded['dport'])){

                                    $dport = $response_decoded['dport'];

                                    $response_decoded['dport-c'] = '=';
                                    $not_dport = strpos($dport,'!');
                                    if(!(is_bool($not_dport) && $not_dport==false)){
                                       $dport = trim($dport,"! ");
                                       $response_decoded['dport-c'] = '!';
                                    }

                                    $response_decoded['dport-src'] = '';

                                    $split_dport = split(':',$dport);
                                    $split_size_dport = count($split_dport);

                                    if($split_size_dport>1 && $split_size_dport < 3 ){
                                        $response_decoded['dport-from'] = $split_dport[0];
                                        $response_decoded['dport-to'] = $split_dport[1];
                                        $response_decoded['dport-src'] = 'range';
                                    }else $response_decoded['dports'] = $dport;

                                 }


                                 if(isset($response_decoded['ports'])){
                                     $ports = $response_decoded['ports'];

                                     $response_decoded['ports-c'] = '=';
                                     $not_ports = strpos($ports,'!');
                                     if(!(is_bool($not_ports) && $not_ports==false)){
                                        $response_decoded['ports'] = trim($ports,"! ");
                                        $response_decoded['ports-c'] = '!';
                                     }

                                 }


                                 if(isset($response_decoded['tcp-flags'])){
                                    $tcp_flags = $response_decoded['tcp-flags'];
                                    $response_decoded['tcp-flags-c'] = '=';
                                    $not_tcp_flags = strpos($tcp_flags,'!');
                                    if(!(is_bool($not_tcp_flags) && $not_tcp_flags==false)){
                                        $tcp_flags = trim($tcp_flags,"! ");
                                        $response_decoded['tcp-flags-c'] = '!';
                                    }

                                    $split_tcp_flags = split(' ',$tcp_flags);
                                    $split_size_tcp_flags = count($split_tcp_flags);

                                    if($split_size_tcp_flags>1 && $split_size_tcp_flags < 3 ){
                                        $flags_set = $split_tcp_flags[0];
                                        $flags = $split_tcp_flags[1];

                                        $split_flags = split(',',$flags);
                                        $split_flags_set = split(',',$flags_set);

                                        foreach($split_flags as $flag){
                                                $response_decoded[strtolower($flag)] = 1;
                                        }

                                        foreach($split_flags_set as $flag_set){
                                                $response_decoded[strtolower($flag_set).'-set'] = 1;
                                        }


                                    }


                                 }

                                if(isset($response_decoded['tcp-option']))
                                 {

                                     $tcp_option = $response_decoded['tcp-option'];

                                     $response_decoded['tcp-option-c'] = '=';
                                     $not_tcp_option = strpos($tcp_option,'!');
                                     if(!(is_bool($not_tcp_option) && $not_tcp_option==false)){
                                        $response_decoded['tcp-option'] = trim($tcp_option,"! ");
                                        $response_decoded['tcp-option-c'] = '!';
                                     }

                                 }


                                 if(isset($response_decoded['icmp-type']))
                                 {

                                     $icmp_type = $response_decoded['icmp-type'];

                                     $response_decoded['icmp-type-c'] = '=';
                                     $not_icmp_type = strpos($icmp_type,'!');
                                     if(!(is_bool($not_icmp_type) && $not_icmp_type==false)){
                                        $response_decoded['icmp-type'] = trim($icmp_type,"! ");
                                        $response_decoded['icmp-type-c'] = '!';
                                     }

                                 }

                                 if(isset($response_decoded['mac-source']))
                                 {

                                     $mac_source = $response_decoded['mac-source'];

                                     $response_decoded['mac-source-c'] = '=';
                                     $not_mac_source = strpos($mac_source,'!');
                                     if(!(is_bool($not_mac_source) && $not_mac_source==false)){
                                        $response_decoded['mac-source'] = trim($mac_source,"! ");
                                        $response_decoded['mac-source-c'] = '!';
                                     }

                                 }

                                 if(isset($response_decoded['limit']))
                                 {

                                     $limit = $response_decoded['limit'];

                                     $response_decoded['limit-c'] = '=';
                                     $not_limit = strpos($limit,'!');
                                     if(!(is_bool($not_limit) && $not_limit==false)){
                                        $limit = trim($limit,"! ");
                                      //  $response_decoded['limit'] = $limit;
                                        $response_decoded['limit-c'] = '!';
                                     }


                                    $split_limit = split('\/',$limit);
                                    $split_size_limit = count($split_limit);

                                    if($split_size_limit>1 && $split_size_limit < 3 ){
                                        $response_decoded['limit'] = $split_limit[0];
                                        $response_decoded['limit-time'] = $split_limit[1];

                                    }



                                 }

                                if(isset($response_decoded['limit-burst']))
                                 {

                                     $limit_burst = $response_decoded['limit-burst'];

                                     $response_decoded['limit-burst-c'] = '=';
                                     $not_limit_burst = strpos($limit_burst,'!');
                                     if(!(is_bool($not_limit_burst) && $not_limit_burst==false)){
                                        $response_decoded['limit-burst'] = trim($limit_burst,"! ");
                                        $response_decoded['limit-burst-c'] = '!';
                                     }

                                 }

                                 if(isset($response_decoded['state']))
                                 {

                                     $state = $response_decoded['state'];

                                     $response_decoded['state-c'] = '=';
                                     $not_state = strpos($state,'!');
                                     if(!(is_bool($not_state) && $not_state==false)){
                                        $response_decoded['state'] = trim($state,"! ");
                                        $response_decoded['state-c'] = '!';
                                     }

                                 }

                                 if(isset($response_decoded['tos']))
                                 {

                                     $tos = $response_decoded['tos'];

                                     $response_decoded['tos-c'] = '=';
                                     $not_tos = strpos($tos,'!');
                                     if(!(is_bool($not_tos) && $not_tos==false)){
                                        $response_decoded['tos'] = trim($tos,"! ");
                                        $response_decoded['tos-c'] = '!';
                                     }

                                 }

                                 $elements = array($response_decoded);
                                 $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                 break;
              case 'list_rules': // data for EXTJS grid
                                 $elements = array();
                                 $default = $response_decoded['default'];
                                 $rules = isset($response_decoded['rules']) ? (array) $response_decoded['rules'] : array();
                                 $i=0;

                                 foreach($rules as $rule){
                                     $rule = (array) $rule;
                                     $elements[$i]['index'] = $rule['index'];
                                     $elements[$i]['action'] = ETFW_Firewall::describe_rule_action($rule['j']);
                                     $elements[$i]['condition'] = ETFW_Firewall::describe_rule_condition($rule);
                                     $i++;
                                 }

                                 $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                 break;

              case 'move_rule' :
               case 'set_rule' :
               case 'add_rule' :
              case 'del_rules' :
              case 'add_chain' :
              case 'del_chain' :
           case 'apply_config' :
          case 'revert_config' :
           case 'reset_config' :
        case 'activate_onboot' :
      case 'deactivate_onboot' :
             case 'set_policy' :
                                 $return = array('success' => true);
                                 break;
                       default : $return = array('success' => false,'error'=>'No action \''.$method.'\' defined yet',
                                            'info'=>'No action \''.$method.'\' implemented yet');

            }

            return $return;

        }else{
            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];
            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }

    }


    /*
     * ETFW dhcp dispatcher...
     */
    public function ETFW_dhcp(EtvaServer $etva_server, $method, $params,$mode)
    {

        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'dhcp'                        
        );

        $call_params = array_merge($initial_params,$params);
        
        $action = $method;
        if($mode) $action = $mode;

        $validate_fields = $this->validateFields('Dhcp',$action,$params);

        if(!$validate_fields['success'])
            return $validate_fields;

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);


        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];
            
            switch($action){
                case 'list_key':
                                $response_decoded = $response_decoded['list'];
                                $elements = array();
                                foreach($response_decoded as $data){

                                    $data_dec = (array) $data;
                                    $parent = $data_dec['parent'];
                                    if(empty($parent)){
                                        $data_pass = array('uuid'=>$data_dec['uuid'],
                                                           'key'=>$data_dec['key'],
                                                           'algorithm'=>$data_dec['algorithm'],
                                                           'secret'=>$data_dec['secret']
                                                            );
                                        $elements[] = $data_pass;
                                    }
                                }
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                case 'get_interface':
                                $response_decoded = $response_decoded['ifaces'];
                                $elements = array();
                                $elements['ifaces'] = $response_decoded;

                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                case 'get_configfile_content':
                                $response_decoded = $response_decoded['content'];
                                $elements = array();
                                $elements['content'] = html_entity_decode($response_decoded);

                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                case 'list_zone':
                                $response_decoded = $response_decoded['list'];
                                $elements = array();
                                foreach($response_decoded as $data){
                                    $data_pass = array();
                                    $data_dec = (array) $data;
                                    $data_pass['uuid'] = $data_dec['uuid'];
                                    $data_pass['name'] = $data_dec['name'];
                                    $data_pass['lastcomment'] = $data_dec['lastcomment'];
                                    $data_pass['primary'] = $data_dec['primary'];
                                    $data_pass['key'] = $data_dec['key'];

                                    $elements[] = $data_pass;
                                }

                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                case 'list_leases':
                                $response_decoded = $response_decoded['list'];
                                $elements = array();
                                foreach($response_decoded as $data){
                                    $data_pass = array();
                                    $data_dec = (array) $data;

                                    $data_dec['client-hostname'] = (isset($data_dec['client-hostname'])) ? $data_dec['client-hostname'] : '';

                                    $data_pass['index'] = $data_dec['index'];
                                    $data_pass['ipaddr'] = $data_dec['ipaddr'];
                                    $data_pass['client-hostname'] = $data_dec['client-hostname'];
                                    $hardware = $data_dec['hardware'];
                                    if((strpos('ethernet ')!==false)) $data_pass['ethernet'] = str_replace('ethernet','',$hardware);

                                    $stime = $data_dec['stime'];
                                    $etime = $data_dec['etime'];
                                    $data_pass['sdate'] = gmdate("d/m/Y H:i:s",$stime);
                                    $data_pass['edate'] = gmdate("d/m/Y H:i:s",$etime);
                                    $data_pass['expired'] = $data_dec['expired'];

                                    $elements[] = $data_pass;
                                }
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;                
                case 'list_clientoptions':
                                $response_option = (array) $response_decoded['option'];
                                $cliOption = new ETFW_Dhcp($response_decoded,'list_clientoptions');                                                                                             
                                $elements = $cliOption->getAllData();
                                $return = array('success' => true,'total' => count($elements),'data'  => $elements);
                                break;                
                case 'list_all':
                                $listAll = new ETFW_Dhcp((array) $response_decoded);
                              
                                $sharednetwork = $listAll->getSharednetworks();
                                $subnets = $listAll->getSubnets();
                                $subnet_shared = $listAll->getSubnetshared();
                                $assigned_to = $listAll->getAssignedTo();
                                $group_hosts = $listAll->getGrouphosts();
                                $subnet_shared = $listAll->getSubnetshared();
                                $coll_shared = $listAll->getCollSharednetwork();
                                $shared_networks_cmb = array('success' => true,'total' =>   count($coll_shared),
                                                                        'data'  => $coll_shared);
                                
                                $return = array('success' => true,
                                                'data'=>array('shared_networks'=>$shared_networks_cmb,
                                                                'subnet'=>$subnets,
                                                                'subnet_shared'=>$subnet_shared,
                                                                'sharednetwork'=>$sharednetwork,
                                                                'assigned_to'=>$assigned_to,
                                                                'group_hosts'=>$group_hosts)
                                             );

                                break;

                case 'list_pool':

                                $response_decoded = $response_decoded['list'];
                                $listPool = new ETFW_Dhcp($response_decoded,'list_pool');
                                $elements = $listPool->getAllData();
                                $fields = $listPool->getGridListPoolFields();
                                $return = array('success' => true,'total' =>   count($elements),
                                    'metaData'=>array("totalProperty"=>"total","root"=>"data","fields"=>$fields),"data"=>$elements);
                                break;
                case 'list_group':
                                $listGroup = new ETFW_Dhcp($response_decoded,'list_group');
                                $elements = $listGroup->getAllData();
                                $fields = $listGroup->getGridListGroupFields();
                                $return = array('success' => true,'total' =>   count($elements),
                                    'metaData'=>array("totalProperty"=>"total","root"=>"data","fields"=>$fields),"data"=>$elements);
                                break;
                
                case 'list_host':                                    
                                $listHost = new ETFW_Dhcp($response_decoded,'list_host');
                                $elements = $listHost->getAllData();
                                $fields = $listHost->getGridListHostFields();

                                $return = array('success' => true,'total' =>   count($elements),
                                    'metaData'=>array("totalProperty"=>"total","root"=>"data","fields"=>$fields),"data"=>$elements);
                                break;
                case 'list_subnet':
                                $response_decoded = $response_decoded['list'];
                                $listSubnet = new ETFW_Dhcp((array) $response_decoded,'list_subnet');
                                $elements = $listSubnet->getAllData();
                                $fields = $listSubnet->getGridListSubnetFields();
                         
                                $return = array('success' => true,'total' =>   count($elements),
                                    'metaData'=>array("totalProperty"=>"total","root"=>"data","fields"=>$fields),"data"=>$elements);
                                break;
                case 'list_sharednetwork':
                                $response_decoded = $response_decoded['list'];
                                $listShared = new ETFW_Dhcp($response_decoded,'list_sharednetwork');
                                $elements = $listShared->getAllData();
                                $fields = $listShared->getGridListSharednetworkFields();
                                $return = array('success' => true,'total' =>   count($elements),
                                    'metaData'=>array("totalProperty"=>"total","root"=>"data","fields"=>$fields),"data"=>$elements);
                                break;
                case 'set_pool' :
                case 'set_zone' :
                case 'set_clientoptions' :
                case 'set_interface' :
                case 'set_subnet' :
                case 'set_sharednetwork' :
                case 'set_group' :
                case 'set_host' :
                case 'set_options' :
                case 'add_host' :
                case 'add_subnet' :
                case 'add_sharednetwork' :
                case 'add_group' :
                case 'add_zone' :
                case 'add_pool' :
                case 'del_pool' :
                case 'del_leases' :
                case 'del_declarations' :
                case 'apply_config' :
                case 'start_service' :
                case 'stop_service' :
                case 'save_configfile_content':
                                $return = array('success' => true);
                                break;
                default :       $return = array('success' => false,'error'=>'No action \''.$action.'\' defined yet',
                                    'info'=>'No action \''.$action.'\' implemented yet');

            }
            return $return;
        }
        else{                        
            return $response;
        }


    }



    


    /*
     * ETFW squid dispatcher...
     */
    public function ETFW_squid(EtvaServer $etva_server, $method, $params,$mode)
    {

        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'squid','force'=>1
        );

        $call_params = array_merge($initial_params,$params);
        $action = $method;
        if($mode) $action = $mode;

        $validate_fields = $this->validateFields('Squid',$action,$params);
        
        if(!$validate_fields['success'])
            return $validate_fields;        

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);


        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];
                        
            switch($action){
               case 'get_http_port':
                                $squid = new ETFW_Squid($response_decoded);                                                                
                                $elements = $squid->getHttpPort();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_https_port':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getHttpsPort();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_http_access':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getHttpAccessData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_uniq_acl':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = (array) $response_decoded['uniq_acl'];
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_icp_access':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getIcpAccessData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_always_direct':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getAlwaysDirectData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_never_direct':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getNeverDirectData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_http_reply_access':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getHttpReplyAccessData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_cache_peer':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getCachePeerData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_external_acl_type':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getExternalAclData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
              case 'get_external_acl_combo':                                
                                $external_data = (array) $response_decoded['external_acl_type'];
                                $elements = array();
                                foreach($external_data as $data){
                                    $data_dec = (array) $data;
                                    $pass_data = array('name'=>$data_dec['name'],'value'=>$data_dec['index']);
                                    $elements[] = $pass_data;
                                }
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                     case 'get_acl':
                                $squid = new ETFW_Squid($response_decoded);
                                $elements = $squid->getAclData();
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
             case 'get_network':
                                $elements = $response_decoded;
                                $squid = new ETFW_Squid($response_decoded);

                                // setting http_port data...
                                $http_port = $squid->getHttpPort();
                                $elements['http_port'] = array('success'=>true,'total'=>count($http_port),'data'=>$http_port);

                                // setting https_port data...
                                $https_port = $squid->getHttpsPort();
                                $elements['https_port'] = array('success'=>true,'total'=>count($https_port),'data'=>$https_port);
                                
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
             case 'get_othercaches_options':
                                $elements = $response_decoded;                                
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);

    
                                // called in etfw/squid othercaches
//                                $other_params = isset($response_decoded['auth_param']) ? $response_decoded['auth_param'] : array();
//                                if(!is_array($auth_params) && !empty($auth_params)) $auth_params = array($auth_params);
//
//                                $ip_ttl = isset($response_decoded['authenticate_ip_ttl']) ? $response_decoded['authenticate_ip_ttl'] : '';
//
//                                $elements = array();
//                                $time_pieces = explode(' ',$ip_ttl,2);
//                                if(count($time_pieces)>1){
//                                    $elements['authenticate_ip_ttl'] = $time_pieces[0];
//                                    $elements['authenticate_ip_ttl-time'] = $time_pieces[1];
//                                }
//
//
//                                foreach($auth_params as $data){
//
//                                    $pieces = explode(' ',$data,3);
//                                    $field = $pieces[0];
//                                    $param = $pieces[1];
//                                    $value = $pieces[2];
//                                    $pass_field = $field.'_'.$param;
//                                    switch($param){
//                                        case 'credentialsttl':
//                                        case 'max_challenge_lifetime':
//                                                                $time_pieces = explode(' ',$value,2);
//                                                                $elements[$pass_field.'-time'] = $time_pieces[1];
//                                                                $elements[$pass_field] = $time_pieces[0];
//                                                                break;
//
//                                                      default:
//                                                                $elements[$pass_field] = $value;
//                                                                break;
//                                    }
//
//                                }                               
                                break;
                case 'get_auth_program':
                                // called in etfw/squid authentication
                                $auth_params = isset($response_decoded['auth_param']) ? $response_decoded['auth_param'] : array();
                                if(!is_array($auth_params) && !empty($auth_params)) $auth_params = array($auth_params);

                                $ip_ttl = isset($response_decoded['authenticate_ip_ttl']) ? $response_decoded['authenticate_ip_ttl'] : '';
                                
                                $elements = array();
                                $time_pieces = explode(' ',$ip_ttl,2);
                                if(count($time_pieces)>1){
                                    $elements['authenticate_ip_ttl'] = $time_pieces[0];
                                    $elements['authenticate_ip_ttl-time'] = $time_pieces[1];
                                }
                                
                                
                                foreach($auth_params as $data){

                                    $pieces = explode(' ',$data,3);
                                    $field = $pieces[0];
                                    $param = $pieces[1];
                                    $value = $pieces[2];
                                    $pass_field = $field.'_'.$param;
                                    switch($param){
                                        case 'credentialsttl':
                                        case 'max_challenge_lifetime':
                                                                $time_pieces = explode(' ',$value,2);
                                                                $elements[$pass_field.'-time'] = $time_pieces[1];
                                                                $elements[$pass_field] = $time_pieces[0];
                                                                break;

                                                      default:
                                                                $elements[$pass_field] = $value;
                                                                break;
                                    }                                    
                                    
                                }      
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                    case 'set_cache_options':
                    case 'set_config':
                    case 'add_external_acl_type':
                    case 'set_external_acl_type':
                    case 'del_external_acl_type':
                    case 'add_icp_access':
                    case 'set_icp_access':
                    case 'del_icp_access':
                    case 'move_icp_access':
                    case 'add_http_access':
                    case 'set_http_access':
                    case 'del_http_access':
                    case 'move_http_access':
                    case 'add_http_reply_access':
                    case 'set_http_reply_access':
                    case 'del_http_reply_access':
                    case 'move_http_reply_access':
                    case 'add_always_direct':
                    case 'set_always_direct':
                    case 'del_always_direct':
                    case 'move_always_direct':
                    case 'add_never_direct':
                    case 'set_never_direct':
                    case 'del_never_direct':
                    case 'move_never_direct':
                    case 'add_cache_peer':
                    case 'set_cache_peer':
                    case 'del_cache_peer':
                    case 'add_acl':
                    case 'del_acl':
                    case 'set_acl':
                                $return = array('success' => true);
                                break;
                      default        :
                                $return = array('success' => false,
                                                'error'=>'No action \''.$action.'\' defined yet',
                                                'info'=>'No action \''.$action.'\' implemented yet');
        
            }
            
            return $return;

        }else{
            
            $error_details = $response['info'];           
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);            
            return $result;
        }
    }



    /*
     * ETFW snmp dispatcher...
     */
    public function ETFW_snmp(EtvaServer $etva_server, $method, $params,$mode)
    {

        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'snmp','force'=>1
        );

        $call_params = array_merge($initial_params,$params);
        $action = $method;
        if($mode) $action = $mode;

        $validate_fields = $this->validateFields('Snmp',$action,$params);        

        if(!$validate_fields['success'])
            return $validate_fields;


        switch($action){
            case 'set_config':
                                $snmp = new ETFW_Snmp();
                                $snmp->createConfig($params);
                                $va_data = $snmp->_VA();

                                $call_params = array_merge($call_params,$va_data);
                                
                                                            

                                break;
            default:
                                break;
        }

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);


        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];            
            switch($action){
               case 'get_config':
               case 'set_config':
                                
                                $snmp = new ETFW_Snmp($response_decoded);
                                
                                $security = $snmp->getSecurityInfo();
                                $directives = $snmp->getDirectivesInfo();
                                
                                $merged_dir = array();
                                foreach($directives as $dir)
                                    $merged_dir = array_merge($merged_dir,$dir);
                                                                                                
                                $security_data = array('security' => array('total' =>   count($security),'data'  => $security));

                                $all_data = array_merge($merged_dir,$security_data);
                                
                                $return = array('success' => true,'total' =>   count($all_data),'data'  => $all_data);
                                
                                //$elements =
                      //          print_r($squid->getData());
                                
                             //   $elements = $squid->getHttpPort();
                                //$return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;                
            }

            return $return;

        }else{

            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }

    }


    /*
     * ETFW network wizard...
     */
    public function ETFW_wizard(EtvaServer $etva_server, $method, $params,$mode)
    {

        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'wizard'
        );

        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);        

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;

            switch($method){
                    case 'submit':
                                $return = $response;
                                break;
                    default:
                                $return = array('success' => false,
                                                'error'=>'No action \''.$method.'\' defined yet',
                                                'info'=>'No action \''.$method.'\' implemented yet');
            }
            return $return;

        }else{

            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }
    }

    public function ETFW_main(EtvaServer $etva_server, $method, $params,$mode)
    {

        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'ETFW'
        );

        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);        

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;

            switch($method){
                    case 'etfw_save':
                                $return = $response;
                                break;
                    default:
                                $return = array('success' => false,
                                                'error'=>'No action \''.$method.'\' defined yet',
                                                'info'=>'No action \''.$method.'\' implemented yet');
            }
            return $return;

        }else{

            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }
    }

    public function validateFields($tmpl,$action,$params){

        /*
         * check fields validation before send set_ or add_....
         */
        $action_set = preg_match("/^set_/",$action);
        $action_add = preg_match("/^add_/",$action);

        if($this->getRequest()->isMethod('post') && ($action_set || $action_add)){

            if($action_add) $toCamel = preg_replace("/^add_/","",$action);
            if($action_set) $toCamel = preg_replace("/^set_/","",$action);

            $formCamel = $tmpl.sfInflector::camelize($toCamel).'Form';

            if(class_exists($formCamel)){

                $this->form = new $formCamel();
                $this->form->bind($params);
                $errors = array();
                if (!$this->form->isValid())
                {
                    foreach ($this->form->getFormattedErrors() as $error) {
                            $errors[] = $error;
                    }

//                    foreach ($this->getFormattedErrors($this->form,$this->form->getErrorSchema()) as $error) {
//                            $errors[] = $error;
//                    }





//                    foreach ($this->form->getErrorSchema() as $field => $error) {
//
//                            //if(is_string($field)) $errors[] = $field.':'.$error->getMessage();
//                            //else
//                            $nestedErrorSchema = $error->getErrors();
//
//                          if  ($this->form->offsetExists($field)){
//                                $txt = $this->form[$field]->renderLabel();
//                                $errors[] = $txt.':'.$error->getMessage();
//                          }
//                          else $errors[] = $field.'--'.$error->getMessage();
//
//                    }

                    $error_msg = implode($errors);
                    $info = implode('<br>',$errors);
                    $response = array('success' => false,
                                'error' => $error_msg,
                                'info' => $info);
                    return $response;
                }
            }

        }

        $response = array('success'=>true);
        return $response;
    }


    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }

}
