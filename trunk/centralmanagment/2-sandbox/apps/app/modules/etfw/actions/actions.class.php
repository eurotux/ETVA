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
        $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));

        $dispatcher = $request->getParameter('dispatcher');

        // used to get parent id component (extjs)
        $this->containerId = $request->getParameter('containerId');
        
        // load modules file of dispatcher
        if($dispatcher){

            $criteria = new Criteria();
            $criteria->add(EtvaServicePeer::SERVER_ID,$this->etva_server->getId());
            $criteria->add(EtvaServicePeer::NAME_TMPL,$dispatcher);

            $this->etva_service = EtvaServicePeer::doSelectOne($criteria);

            $tmpl = $this->etva_server->getAgentTmpl().'_'.$dispatcher.'_modules';
            $directory = $this->context->getConfiguration()->getTemplateDir('etfw', '_'.$tmpl.'.php');

            if($directory)
                return $this->renderPartial($tmpl);
            else
                return $this->renderText('Template '.$tmpl.' not found');
        }
        
    }

     // called by 'Add Server Wizard' button
    public function executeETFW_network_wizard(sfWebRequest $request)
    {
//        $this->etva_node = EtvaNodePeer::retrieveByPk(1);
//        // remove session macs for cleanup the wizard
//        $this->getUser()->getAttributeHolder()->remove('macs_in_wizard');
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
            else
                $result = $this->setJsonError($ret);
        }else{
            $info = array('success'=>false,'error'=>'No method implemented! '.$dispatcher_tmpl);
            $result = $this->setJsonError($info);
        }


            // $result = json_encode($ret);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

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
                case 'get_rule': // return data for EXTJS form presentation

                                 $response_decoded['chain-desc'] = ETFW_firewall::describe_rule_chain($response_decoded['chain']);

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
                                     $elements[$i]['action'] = ETFW_firewall::describe_rule_action($rule['j']);
                                     $elements[$i]['condition'] = ETFW_firewall::describe_rule_condition($rule);
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

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);


        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
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
                                //unset($response_decoded['option']);
                                $elements = array();
                                $data_dec = $response_decoded;
                                $data_pass = array();

                                foreach($response_option as $index_option=>$data_option)
                                    $data_pass['option_'.$index_option] = $data_option;                                

                                if(isset($data_dec['filename'])) $data_pass['filename'] = $data_dec['filename'];
                                if(isset($data_dec['next-server'])) $data_pass['next-server'] = $data_dec['next-server'];
                                if(isset($data_dec['dynamic-bootp-lease-length'])) $data_pass['dynamic-bootp-lease-length'] = $data_dec['dynamic-bootp-lease-length'];
                                if(isset($data_dec['ddns-rev-domainname'])) $data_pass['ddns-rev-domainname'] = $data_dec['ddns-rev-domainname'];
                                if(isset($data_dec['default-lease-time'])) $data_pass['default-lease-time'] = $data_dec['default-lease-time'];
                                if(isset($data_dec['max-lease-time'])) $data_pass['max-lease-time'] = $data_dec['max-lease-time'];
                                if(isset($data_dec['server-name'])) $data_pass['server-name'] = $data_dec['server-name'];
                                if(isset($data_dec['dynamic-bootp-lease-cutoff'])) $data_pass['dynamic-bootp-lease-cutoff'] = $data_dec['dynamic-bootp-lease-cutoff'];
                                if(isset($data_dec['ddns-domainname'])) $data_pass['ddns-domainname'] = $data_dec['ddns-domainname'];
                                if(isset($data_dec['ddns-hostname'])) $data_pass['ddns-hostname'] = $data_dec['ddns-hostname'];
                                if(isset($data_dec['ddns-updates'])) $data_pass['ddns-updates'] = $data_dec['ddns-updates'];
                                if(isset($data_dec['use-host-decl-names'])) $data_pass['use-host-decl-names'] = $data_dec['use-host-decl-names'];
                                if(isset($data_dec['ddns-update-style'])) $data_pass['ddns-update-style'] = $data_dec['ddns-update-style'];

                                $data_pass['authoritative'] = isset($data_dec['authoritative']) ? 1:0;


                                $deny = $data_dec['deny'];
                                if($deny)
                                if(!is_array($deny))
                                    $data_pass[$deny] = 'deny';
                                else
                                    foreach($deny as $data_deny)
                                        $data_pass[$data_deny] = 'deny';

                                $allow = $data_dec['allow'];
                                if($allow)
                                if(!is_array($allow))
                                    $data_pass[$allow] = 'allow';
                                else
                                    foreach($allow as $data_allow)
                                        $data_pass[$data_allow] = 'allow';


                                if(isset($data_dec['ignore'])){
                                    $ignore = $data_dec['ignore'];
                                
                                    if(!is_array($ignore))
                                        $data_pass[$ignore] = 'ignore';
                                    else
                                        foreach($ignore as $data_ignore)
                                            $data_pass[$data_ignore] = 'ignore';
                                }

                                $elements = $data_pass;

                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                case 'list_all':
                                //build shared network combo store
                                $shared_network_elements = array();
                                $data_shared_networks = (array) $response_decoded['sharednetworks'];



                                $subnet_shared = array();
                                $coll_sharednetwork = array();
                                //shared networks....
                                foreach($data_shared_networks as $data_shared_network){
                                    $shared_network = (array) $data_shared_network;
                                    $name = $shared_network['name'];
                                    $uuid = $shared_network['uuid'];
                                    $shared_network_elements[] = array('name'=>$name,'value'=>$uuid);
                                    $coll_sharednetwork[] = array('uuid'=>$uuid,'type'=>'shared-network','value'=>$name);

                                    $subnet_shared['hosts_list'][$uuid] = array('success'=>true,'data'=>array());
                                    $subnet_shared['groups_list'][$uuid] = array('success'=>true,'data'=>array());

                                }


                                $shared_networks_cmb = array('success' => true,'total' =>   count($shared_network_elements),
                                                                        'data'  => $shared_network_elements);


                               $data_subnets = (array) $response_decoded['subnets'];
                               $sharednetwork = array('subnets_list'=>array('success'=>true,'data'=>array()),
                                                      'groups_list'=>array('success'=>true,'data'=>array()),
                                                      'hosts_list'=>array('success'=>true,'data'=>array())
                                                     );


                               $subnet = array('groups_list'=>array('success'=>true,'data'=>array()),
                                                      'hosts_list'=>array('success'=>true,'data'=>array())
                                              );
                               //subnets info
                               $coll_subnet = array();
                               foreach($data_subnets as $data_subnet){
                                   $subnet_dec = (array) $data_subnet;
                                   $data_parent = (array) $subnet_dec['parent'];
                                   $sharednetwork['subnets_list']['data'][] = array('uuid'=>$subnet_dec['uuid'],'subnet'=>$subnet_dec['address']);
                                   $data_parent['type'] = isset($data_parent['type']) ? $data_parent['type'] : '';
                                   switch($data_parent['type']){
                                            case 'shared-network':
                                                                $coll_subnet[] = array('uuid'=>$subnet_dec['uuid'],'type'=>'subnet','value'=>$subnet_dec['address'].' in '.$data_parent['name']);
                                                                break;
                                            case '':
                                                                $coll_subnet[] = array('uuid'=>$subnet_dec['uuid'],'type'=>'subnet','value'=>$subnet_dec['address']);
                                                                break;

                                            default:break;
                                   }
                               }



                               //groups_info
                               $data_groups = (array) $response_decoded['groups'];
                               $coll_group = $group_hosts = $group_hosts_info_parents = array();

                               foreach($data_groups as $data_group){
                                   $group = (array) $data_group;
                                   $group_parent = (!empty($group['parent'])) ? (array) $group['parent'] : array('type'=>'');
                                   $hosts_data = (array) $group['hosts'];
                                   $hosts_count = count($hosts_data);

                                   $group_hosts[$group['uuid']] = array('success'=>true,'data'=>array());


                                   switch($group_parent['type']){
                                       case 'subnet':

                                                     $group_hosts_info_parents[$group['uuid']][] = $group_parent['uuid'];



                                                     if(empty($group_parent['parent'])){
                                                         $coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members in '.$group_parent['address']);
                                                         $subnet['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                                                     }else{
                                                     $aux_data = (array) $group_parent['parent'];
                                                     $coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members in '.$group_parent['address'].' in '.$aux_data['name']);
                                                     $subnet_shared['groups_list'][$aux_data['uuid']]['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');

                                                     }
                                                      break;
                                        case 'shared-network':

                                                    $group_hosts_info_parents[$group['uuid']][] = $group_parent['uuid'];

                                                    $sharednetwork['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                                                    $subnet_shared['groups_list'][$group_parent['uuid']]['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                                                    $coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members in '.$group_parent['name']);
                                                    break;

                                       case '':
                                                $group_hosts_info_parents[$group['uuid']][] = '';

                                                $sharednetwork['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                                                $subnet['groups_list']['data'][] = array('uuid'=>$group['uuid'],'hosts_count'=>$hosts_count.' members');
                                                $coll_group[] = array('uuid'=>$group['uuid'],'type'=>'group','value'=>$hosts_count.' members');
                                                break;
                                        default:break;
                                   }

                               }

                               //hosts_info
                               $data_hosts = (array) $response_decoded['hosts'];
                               $host_info_parents = array();
                               $hosts_empty_parent = array();

                               foreach($data_hosts as $data_host){
                                   $host = (array) $data_host;
                                   $host_parent = (!empty($host['parent'])) ? (array) $host['parent'] : array('type'=>'');
                                   switch($host_parent['type']){
                                       case 'subnet':
 
                                                     $host_info_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>$host_parent['uuid']);

                                                     if(empty($host_parent['parent'])){
                                                        $subnet['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                                     }else{
                                                         $aux_data = (array) $host_parent['parent'];
                                                         $subnet_shared['hosts_list'][$aux_data['uuid']]['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                                     }
                                                     break;
                               case 'shared-network':
                                                    $sharednetwork['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                                    $subnet_shared['hosts_list'][$host_parent['uuid']]['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                                    break;
                                        case 'group':

                                                   if(!empty($host_parent['parent'])){
                                                        $aux_data = (array) $host_parent['parent'];
                                                        $host_info_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>$aux_data['uuid']);
                                                    }else{
                                                        $host_info_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>'');                                                        
                                                        $group_hosts['']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                                    }

                                                    break;
                                            case '':
                                                    
                                                    $host_info_parents[$host['uuid']] = array('host'=>$host['host'],'uuid'=>'');
                                                    $group_hosts['']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);

                                                    $sharednetwork['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                                    $subnet['hosts_list']['data'][] = array('uuid'=>$host['uuid'],'host'=>$host['host']);
                                                    break;
                                            default:break;
                                   }
                               }

                                 while(list($uuid) = current($group_hosts_info_parents)){

                                        foreach($host_info_parents as $host_uuid=>$host_parent_uuid){

                                            if($uuid==$host_parent_uuid['uuid']){


                                                $group_hosts[key($group_hosts_info_parents)]['data'][] = array('uuid'=>$host_uuid,'host'=>$host_parent_uuid['host']);
                                            }

                                            if($uuid==''){

                                            }

                                        }

                                        next($group_hosts_info_parents);
                                  }


                              $assigned_to = array('success'=>true,
                                                    'data'=>array_merge($coll_subnet,$coll_sharednetwork,$coll_group)
                              );                           

                              $return = array('success' => true,
                                                'data'=>array('shared_networks'=>$shared_networks_cmb,
                                                                'subnet'=>$subnet,
                                                                'subnet_shared'=>$subnet_shared,
                                                                'sharednetwork'=>$sharednetwork,
                                                                'assigned_to'=>$assigned_to,
                                                                'group_hosts'=>$group_hosts)
                                             );

                                break;


                case 'list_pool':
                                $elements = array();
                                $index = 1;
                                $response_decoded = $response_decoded['list'];
                                foreach($response_decoded as $index=>$data){
                                    $data_dec = (array) $data;
                                    $data_pass = array();
                                    $data_parent = (array) $data_dec['parent'];
                                    //if($data_parent['type']=='shared-network'){
                                    $data_pass['uuid'] = $data_dec['uuid'];
                                    $data_pass['parent-uuid'] = $data_parent['uuid'];
                                    $data_pass['parent-type'] = $data_parent['type'];
                                    $data_pass['parent-args'] = str_replace(' netmask ','/',$data_parent['args']);
                                    switch($data_pass['parent-type']){
                                        case 'subnet': $data_pass['parent'] = $data_parent['address'];
                                                        break;
                                        case 'shared-network' :
                                                         $data_pass['parent'] = $data_parent['name'];
                                                        break;
                                                        default:break;
                                    }

                                    $data_pass['name'] = 'Pool '.$index;
                                    $range_array = is_array($data_dec['range']) ? $data_dec['range']: array($data_dec['range']);

                                    foreach($range_array as $range){

                                    $bootp='No';
                                    if(strpos($range,'dynamic-bootp ')!==false){
                                        $bootp='Yes';
                                        $range = str_replace('dynamic-bootp ','',$range);
                                    }


                                        $data_pass['range_display'][] = array('address'=>$range,'bootp'=>$bootp);
                                    }



                                    $allow_data = (array) $data_dec['allow'];
                                    $deny_data = (array) $data_dec['deny'];



                                     $data_pass['params'] = array(
                                                             'range' => $range_array,
                                                             'failover' => $data_dec['failover'],
                                                             'allow' => implode("\r\n",$allow_data),
                                                             'deny' => implode("\r\n",$deny_data),

                                                              /*
                                                               *
                                                               *
                                                               * default params
                                                               */
                                                             'filename'=>$data_dec['filename'],
                                                             'next-server' => $data_dec['next-server'],
                                                             'dynamic-bootp-lease-length' => $data_dec['dynamic-bootp-lease-length'],
                                                             'ddns-rev-domainname' => $data_dec['ddns-rev-domainname'],
                                                             'default-lease-time' => $data_dec['default-lease-time'],
                                                             'max-lease-time' => $data_dec['max-lease-time'],
                                                             'server-name' => $data_dec['server-name'],
                                                             'dynamic-bootp-lease-cutoff' => $data_dec['dynamic-bootp-lease-cutoff'],
                                                             'ddns-domainname' => $data_dec['ddns-domainname'],
                                                             'ddns-hostname' => $data_dec['ddns-hostname'],
                                                             'ddns-updates' => $data_dec['ddns-updates']
                                                             // end
                                    );


                                    $deny = $data_dec['deny'];
                                    if(!is_array($deny))
                                        $data_pass['params'][$deny] = 'deny';
                                    else
                                        foreach($deny as $data_deny)
                                            $data_pass['params'][$data_deny] = 'deny';

                                    $allow = $data_dec['allow'];
                                    if(!is_array($allow))
                                        $data_pass['params'][$allow] = 'allow';
                                    else
                                        foreach($allow as $data_allow)
                                            $data_pass['params'][$data_allow] = 'allow';


                                    $ignore = $data_dec['ignore'];
                                    if(!is_array($ignore))
                                        $data_pass['params'][$ignore] = 'ignore';
                                    else
                                        foreach($ignore as $data_ignore)
                                            $data_pass['params'][$data_ignore] = 'ignore';



                                    $elements[] = $data_pass;
                                    $index++;
                                }

                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                case 'list_group':
                                $elements = array();
                                $groups_response = $response_decoded['groups'];

                               //subnets info
                               $subnets_response = $response_decoded['subnets'];
                               $list_subnets = array();
                               foreach($subnets_response as $data_subnet){
                                   $subnet_dec = (array) $data_subnet;
                                   $data_parent = (array) $subnet_dec['parent'];
                                   $data_parent['type'] = isset($data_parent['type']) ? $data_parent['type'] : '' ;
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


                                foreach($groups_response as $data_group){
                                        $data_pass = array();
                                        $data_dec = (array) $data_group;
                                        $group_parent = (!empty($data_dec['parent'])) ? (array) $data_dec['parent'] : array('uuid'=>'','type'=>'');

                                        $hosts_data = (array) $data_dec['hosts'];
                                        $hosts_count = count($hosts_data);

                                        $data_pass['group'] = $hosts_count.' members';

                                        //hosts
                                         $coll_hosts = array();
                                         foreach($hosts_data as $host){
                                             //get data with type==subnet
                                             $host_dec = (array) $host;
                                             $parent_data = (array) $host_dec['parent'];
                                             if($parent_data['type']=='group') $coll_hosts[] = $host_dec['uuid'];
                                         }
                                         
                                        // get assigned element
                                        $assigned_data = $group_parent['uuid'];
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


                                        $data_pass['uuid'] = $data_dec['uuid'];
                                        $data_pass['assigned'] = $assigned;
                                        $data_pass['parent'] = $parent;

                                        $data_dec['filename'] = (isset($data_dec['filename'])) ? $data_dec['filename'] : '';
                                        $data_dec['next-server'] = (isset($data_dec['next-server'])) ? $data_dec['next-server'] : '';
                                        $data_dec['dynamic-bootp-lease-length'] = (isset($data_dec['dynamic-bootp-lease-length'])) ? $data_dec['dynamic-bootp-lease-length'] : '';
                                        $data_dec['ddns-rev-domainname'] = (isset($data_dec['ddns-rev-domainname'])) ? $data_dec['ddns-rev-domainname'] : '';
                                        $data_dec['default-lease-time'] = (isset($data_dec['default-lease-time'])) ? $data_dec['default-lease-time'] : '';
                                        $data_dec['max-lease-time'] = (isset($data_dec['max-lease-time'])) ? $data_dec['max-lease-time'] : '';
                                        $data_dec['server-name'] = (isset($data_dec['server-name'])) ? $data_dec['server-name'] : '';
                                        $data_dec['dynamic-bootp-lease-cutoff'] = (isset($data_dec['dynamic-bootp-lease-cutoff'])) ? $data_dec['dynamic-bootp-lease-cutoff'] : '';
                                        $data_dec['ddns-domainname'] = (isset($data_dec['ddns-domainname'])) ? $data_dec['ddns-domainname'] : '';
                                        $data_dec['ddns-hostname'] = (isset($data_dec['ddns-hostname'])) ? $data_dec['ddns-hostname'] : '';
                                        $data_dec['ddns-updates'] = (isset($data_dec['ddns-updates'])) ? $data_dec['ddns-updates'] : '';


                                        $data_pass['assigned_type']= $group_parent['type'];

                                        $data_options = array();
                                        if(isset($data_dec['option']))
                                            foreach($data_dec['option'] as $index_option=>$data_option)
                                                $data_options['option_'.$index_option] = $data_option;
                                        
                                        $data_pass['option'] = $data_options;

                                        $data_pass['params'] = array(
                                                                     'lastcomment' => $data_dec['lastcomment'],                                                                                                  
                                                                     'parent-type' => $group_parent['type'],
                                                                     'assigned_data' => $assigned_data,
                                                                     'hosts' => $coll_hosts,
                                                                     'use-host-decl-names'=>$data_dec['use-host-decl-names'],

                                                                      /*
                                                                       *
                                                                       *
                                                                       * default params
                                                                       */
                                                                     'filename'=>$data_dec['filename'],
                                                                     'next-server' => $data_dec['next-server'],
                                                                     'dynamic-bootp-lease-length' => $data_dec['dynamic-bootp-lease-length'],
                                                                     'ddns-rev-domainname' => $data_dec['ddns-rev-domainname'],
                                                                     'default-lease-time' => $data_dec['default-lease-time'],
                                                                     'max-lease-time' => $data_dec['max-lease-time'],
                                                                     'server-name' => $data_dec['server-name'],
                                                                     'dynamic-bootp-lease-cutoff' => $data_dec['dynamic-bootp-lease-cutoff'],
                                                                     'ddns-domainname' => $data_dec['ddns-domainname'],
                                                                     'ddns-hostname' => $data_dec['ddns-hostname'],
                                                                     'ddns-updates' => $data_dec['ddns-updates']
                                                                     // end
                                        );

                                        if(isset($data_dec['deny'])){
                                            $deny = $data_dec['deny'];
                                            if(!is_array($deny))
                                                $data_pass['params'][$deny] = 'deny';
                                            else
                                                foreach($deny as $data_deny)
                                                    $data_pass['params'][$data_deny] = 'deny';
                                        }


                                        if(isset($data_dec['allow'])){
                                            $allow = $data_dec['allow'];
                                            if(!is_array($allow))
                                                $data_pass['params'][$allow] = 'allow';
                                            else
                                                foreach($allow as $data_allow)
                                                    $data_pass['params'][$data_allow] = 'allow';
                                        }


                                        if(isset($data_dec['ignore'])){
                                            $ignore = $data_dec['ignore'];
                                            if(!is_array($ignore))
                                                $data_pass['params'][$ignore] = 'ignore';
                                            else
                                                foreach($ignore as $data_ignore)
                                                    $data_pass['params'][$data_ignore] = 'ignore';
                                        }


                                        $elements[] = $data_pass;
                                    }

                                    $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                    break;
                case 'list_host'  :
                                    $elements = array();
                                    $hosts_response = $response_decoded['hosts'];
                                    $groups_response = $response_decoded['groups'];

                                    $list_groups = $list_assigned_net = array();
                                    foreach($groups_response as $data_group){
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
                                    $subnets_response = $response_decoded['subnets'];
                                    $list_subnets = array();
                                    foreach($subnets_response as $data_subnet){
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


                                    foreach($hosts_response as $index=>$data){
                                        $data_dec = (array) $data;
                                        $data_pass = array();
                                        $data_pass['host'] = $data_dec['host'];

                                        $data_parent = (isset($data_dec['parent']) && !empty($data_dec['parent'])) ? (array) $data_dec['parent'] : array('type'=>'','uuid'=>'');

                                        // get assigned element
                                        $data_parent_type = $data_parent['type'];
                                        $assigned_data = $data_parent['uuid'];
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
                                                        $assigned_net = $list_assigned_net[$data_parent['uuid']];
                                                        break;
                                            default:break;
                                        }

                                        $data_pass['uuid'] = $data_dec['uuid'];
                                        $data_pass['assigned'] = $assigned;
                                        $data_pass['assigned_net'] = $assigned_net;

                                        $data_dec['hardware'] = (isset($data_dec['hardware'])) ? $data_dec['hardware']: '';

                                        $data_dec['filename'] = (isset($data_dec['filename'])) ? $data_dec['filename'] : '';
                                        $data_dec['next-server'] = (isset($data_dec['next-server'])) ? $data_dec['next-server'] : '';
                                        $data_dec['dynamic-bootp-lease-length'] = (isset($data_dec['dynamic-bootp-lease-length'])) ? $data_dec['dynamic-bootp-lease-length'] : '';
                                        $data_dec['ddns-rev-domainname'] = (isset($data_dec['ddns-rev-domainname'])) ? $data_dec['ddns-rev-domainname'] : '';
                                        $data_dec['default-lease-time'] = (isset($data_dec['default-lease-time'])) ? $data_dec['default-lease-time'] : '';
                                        $data_dec['max-lease-time'] = (isset($data_dec['max-lease-time'])) ? $data_dec['max-lease-time'] : '';
                                        $data_dec['server-name'] = (isset($data_dec['server-name'])) ? $data_dec['server-name'] : '';
                                        $data_dec['dynamic-bootp-lease-cutoff'] = (isset($data_dec['dynamic-bootp-lease-cutoff'])) ? $data_dec['dynamic-bootp-lease-cutoff'] : '';
                                        $data_dec['ddns-domainname'] = (isset($data_dec['ddns-domainname'])) ? $data_dec['ddns-domainname'] : '';
                                        $data_dec['ddns-hostname'] = (isset($data_dec['ddns-hostname'])) ? $data_dec['ddns-hostname'] : '';
                                        $data_dec['ddns-updates'] = (isset($data_dec['ddns-updates'])) ? $data_dec['ddns-updates'] : '';


                                        $data_options = array();
                                        if(isset($data_dec['option']))
                                            foreach($data_dec['option'] as $index_option=>$data_option)
                                                $data_options['option_'.$index_option] = $data_option;

                                        $data_pass['option'] = $data_options;
                                        
                                        $data_pass['assigned_type']=$data_parent_type;
                                        $data_pass['params'] = array(
                                                                     'host' => $data_dec['host'],
                                                                     'lastcomment' => $data_dec['lastcomment'],
                                                                     'hardware' => $data_dec['hardware'],
                                                                     'fixed-address' => $data_dec['fixed-address'],
                                                                     'parent-type' => $data_parent_type,
                                                                     'assigned_data' => $assigned_data,
                                                                      /*
                                                                       * default params
                                                                       */
                                                                     'filename'=>$data_dec['filename'],
                                                                     'next-server' => $data_dec['next-server'],
                                                                     'dynamic-bootp-lease-length' => $data_dec['dynamic-bootp-lease-length'],
                                                                     'ddns-rev-domainname' => $data_dec['ddns-rev-domainname'],
                                                                     'default-lease-time' => $data_dec['default-lease-time'],
                                                                     'max-lease-time' => $data_dec['max-lease-time'],
                                                                     'server-name' => $data_dec['server-name'],
                                                                     'dynamic-bootp-lease-cutoff' => $data_dec['dynamic-bootp-lease-cutoff'],
                                                                     'ddns-domainname' => $data_dec['ddns-domainname'],
                                                                     'ddns-hostname' => $data_dec['ddns-hostname'],
                                                                     'ddns-updates' => $data_dec['ddns-updates']
                                                                     // end
                                        );

                                        if(isset($data_dec['deny'])){
                                            $deny = $data_dec['deny'];
                                            if(!is_array($deny))
                                                $data_pass['params'][$deny] = 'deny';
                                            else
                                                foreach($deny as $data_deny)
                                                    $data_pass['params'][$data_deny] = 'deny';
                                        }
                                        
                                        
                                        if(isset($data_dec['allow'])){
                                            $allow = $data_dec['allow'];
                                            if(!is_array($allow))
                                                $data_pass['params'][$allow] = 'allow';
                                            else
                                                foreach($allow as $data_allow)
                                                    $data_pass['params'][$data_allow] = 'allow';
                                        }

                                        
                                        if(isset($data_dec['ignore'])){
                                            $ignore = $data_dec['ignore'];
                                            if(!is_array($ignore))
                                                $data_pass['params'][$ignore] = 'ignore';
                                            else
                                                foreach($ignore as $data_ignore)
                                                    $data_pass['params'][$data_ignore] = 'ignore';
                                        }
                                        

                                        $elements[] = $data_pass;
                                    }

                                    $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                    break;
                case 'list_subnet': // return data for EXTJS form presentation
                                    $elements = array();
                                    $response_decoded = $response_decoded['list'];

                                    foreach($response_decoded as $index=>$data){
                                        $data_dec = (array) $data;
                                        $data_pass = array();
                                        $data_pass['uuid'] = $data_dec['uuid'];
                                        $data_pass['args'] = str_replace(' netmask ','/',$data_dec['args']);
                                        $data_pass['address'] = $data_dec['address'];

                                        $data_pass['netmask'] = $data_dec['netmask'];
                                        if($data_dec['range'])
                                            $range = is_array($data_dec['range']) ? $data_dec['range']: array($data_dec['range']);
                                        else $range = array();

                                        $data_parent = (array) $data_dec['parent'];
                                        $data_pass['parent'] = $data_pass['parent-type'] = '';
                                        
                                        if($data_dec['parent']){
                                            // subnet has parent
                                            if($data_parent['type']=='shared-network')
                                                $data_pass['parent'] = $data_parent['name'];
                                        
                                            $data_pass['parent-type'] = $data_parent['type'];
                                        
                                        }

                                        $parent_uuid = (isset($data_parent['uuid'])) ? $data_parent['uuid'] : '';
                                        $parent_type = (isset($data_parent['type'])) ? $data_parent['type'] : '';

                                        $data_dec['filename'] = (isset($data_dec['filename'])) ? $data_dec['filename'] : '';
                                        $data_dec['next-server'] = (isset($data_dec['next-server'])) ? $data_dec['next-server'] : '';
                                        $data_dec['dynamic-bootp-lease-length'] = (isset($data_dec['dynamic-bootp-lease-length'])) ? $data_dec['dynamic-bootp-lease-length'] : '';
                                        $data_dec['ddns-rev-domainname'] = (isset($data_dec['ddns-rev-domainname'])) ? $data_dec['ddns-rev-domainname'] : '';
                                        $data_dec['default-lease-time'] = (isset($data_dec['default-lease-time'])) ? $data_dec['default-lease-time'] : '';
                                        $data_dec['max-lease-time'] = (isset($data_dec['max-lease-time'])) ? $data_dec['max-lease-time'] : '';
                                        $data_dec['server-name'] = (isset($data_dec['server-name'])) ? $data_dec['server-name'] : '';
                                        $data_dec['dynamic-bootp-lease-cutoff'] = (isset($data_dec['dynamic-bootp-lease-cutoff'])) ? $data_dec['dynamic-bootp-lease-cutoff'] : '';
                                        $data_dec['ddns-domainname'] = (isset($data_dec['ddns-domainname'])) ? $data_dec['ddns-domainname'] : '';
                                        $data_dec['ddns-hostname'] = (isset($data_dec['ddns-hostname'])) ? $data_dec['ddns-hostname'] : '';
                                        $data_dec['ddns-updates'] = (isset($data_dec['ddns-updates'])) ? $data_dec['ddns-updates'] : '';
                                                                     
                                         //hosts
                                         $coll_hosts = array();
                                         $hosts = $data_dec['hosts'];
                                         foreach($hosts as $host){
                                             //get data with type==subnet
                                             $host_dec = (array) $host;
                                             $parent_data = (array) $host_dec['parent'];
                                             if($parent_data['type']=='subnet') $coll_hosts[] = $host_dec['uuid'];
                                         }



                                         //groups
                                         $coll_groups = array();
                                         $groups = $data_dec['groups'];
                                         foreach($groups as $group){
                                             //get data with type==subnet
                                             $group_dec = (array) $group;
                                             $parent_data = (array) $group_dec['parent'];
                                             if($parent_data['type']=='subnet') $coll_groups[] = $group_dec['uuid'];
                                         }


                                         $data_options = array();
                                         if(isset($data_dec['option']))
                                            foreach($data_dec['option'] as $index_option=>$data_option)
                                                $data_options['option_'.$index_option] = $data_option;

                                         $data_pass['option'] = $data_options;


                                         $data_pass['params'] = array(
                                                                     'address' => $data_dec['address'],
                                                                     'netmask' => $data_dec['netmask'],
                                                                     'lastcomment' => $data_dec['lastcomment'],
                                                                     'parent-uuid' => $parent_uuid,
                                                                     'parent-type' => $parent_type,
                                                                     'hosts' => $coll_hosts,
                                                                     'groups' => $coll_groups,
                                                                     'authoritative' => isset($data_dec['authoritative']) ? 1:0,
                                                                     'range' => $range,
                                                                      /*
                                                                       *
                                                                       *
                                                                       * default params
                                                                       */
                                                                     'filename'=>$data_dec['filename'],
                                                                     'next-server' => $data_dec['next-server'],
                                                                     'dynamic-bootp-lease-length' => $data_dec['dynamic-bootp-lease-length'],
                                                                     'ddns-rev-domainname' => $data_dec['ddns-rev-domainname'],
                                                                     'default-lease-time' => $data_dec['default-lease-time'],
                                                                     'max-lease-time' => $data_dec['max-lease-time'],
                                                                     'server-name' => $data_dec['server-name'],
                                                                     'dynamic-bootp-lease-cutoff' => $data_dec['dynamic-bootp-lease-cutoff'],
                                                                     'ddns-domainname' => $data_dec['ddns-domainname'],
                                                                     'ddns-hostname' => $data_dec['ddns-hostname'],
                                                                     'ddns-updates' => $data_dec['ddns-updates']
                                                                     // end
                                       );

                                       if(isset($data_dec['deny'])){
                                            $deny = $data_dec['deny'];
                                            if(!is_array($deny))
                                                $data_pass['params'][$deny] = 'deny';
                                            else
                                                foreach($deny as $data_deny){
                                                    $data_pass['params'][$data_deny] = 'deny';
                                                }
                                       }

                                       if(isset($data_dec['allow'])){
                                            $allow = $data_dec['allow'];
                                            if(!is_array($allow))
                                                $data_pass['params'][$allow] = 'allow';
                                            else
                                                foreach($allow as $data_allow)
                                                    $data_pass['params'][$data_allow] = 'allow';
                                       }


                                       if(isset($data_dec['ignore'])){
                                            $ignore = $data_dec['ignore'];
                                            if(!is_array($ignore))
                                                $data_pass['params'][$ignore] = 'ignore';
                                            else
                                                foreach($ignore as $data_ignore)
                                                    $data_pass['params'][$data_ignore] = 'ignore';
                                       }
                                        
                                       $elements[] = $data_pass;

                                    }
                                    $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                    break;
                   case 'list_sharednetwork': // return data for EXTJS form presentation
                                    $elements = array();
                                    $response_decoded = $response_decoded['list'];


                                    foreach($response_decoded as $index=>$data){
                                        $data_dec = (array) $data;
                                        $data_pass = array();
                                        $data_pass['uuid'] = $data_dec['uuid'];
                                        $data_pass['name'] = $data_dec['name'];
                                        $data_pass['lastcomment'] = $data_dec['lastcomment'];
                                        $authoritative = isset($data_dec['authoritative']) ? 1:0;
                                        if($authoritative==1)
                                            $data_pass['authoritative_txt'] = 'Yes';
                                        else $data_pass['authoritative_txt'] = 'No';


                                         //hosts
                                         $coll_hosts = array();
                                         $hosts = $data_dec['hosts'];
                                         foreach($hosts as $host){
                                             //get data with type==subnet
                                             $host_dec = (array) $host;
                                             $parent_data = (array) $host_dec['parent'];
                                             if($parent_data['type']=='shared-network') $coll_hosts[] = $host_dec['uuid'];
                                         }


                                         //groups
                                         $coll_groups = array();
                                         $groups = $data_dec['groups'];
                                         foreach($groups as $group){
                                             //get data with type==subnet
                                             $group_dec = (array) $group;
                                             $parent_data = (array) $group_dec['parent'];
                                             if($parent_data['type']=='shared-network') $coll_groups[] = $group_dec['uuid'];
                                         }



                                         //subnets
                                         $coll_subnets = array();
                                         $subnets = $data_dec['subnets'];
                                         foreach($subnets as $subnet){
                                             //get data with type==subnet
                                             $subnet_dec = (array) $subnet;
                                             $parent_data = (array) $subnet_dec['parent'];
                                             if($parent_data['type']=='shared-network') $coll_subnets[] = $subnet_dec['uuid'];
                                         }


                                         $data_options = array();
                                         if(isset($data_dec['option']))
                                            foreach($data_dec['option'] as $index_option=>$data_option)
                                                $data_options['option_'.$index_option] = $data_option;

                                         $data_pass['option'] = $data_options;

                                         $data_dec['filename'] = (isset($data_dec['filename'])) ? $data_dec['filename'] : '';
                                         $data_dec['next-server'] = (isset($data_dec['next-server'])) ? $data_dec['next-server'] : '';
                                         $data_dec['dynamic-bootp-lease-length'] = (isset($data_dec['dynamic-bootp-lease-length'])) ? $data_dec['dynamic-bootp-lease-length'] : '';
                                         $data_dec['ddns-rev-domainname'] = (isset($data_dec['ddns-rev-domainname'])) ? $data_dec['ddns-rev-domainname'] : '';
                                         $data_dec['default-lease-time'] = (isset($data_dec['default-lease-time'])) ? $data_dec['default-lease-time'] : '';
                                         $data_dec['max-lease-time'] = (isset($data_dec['max-lease-time'])) ? $data_dec['max-lease-time'] : '';
                                         $data_dec['server-name'] = (isset($data_dec['server-name'])) ? $data_dec['server-name'] : '';
                                         $data_dec['dynamic-bootp-lease-cutoff'] = (isset($data_dec['dynamic-bootp-lease-cutoff'])) ? $data_dec['dynamic-bootp-lease-cutoff'] : '';
                                         $data_dec['ddns-domainname'] = (isset($data_dec['ddns-domainname'])) ? $data_dec['ddns-domainname'] : '';
                                         $data_dec['ddns-hostname'] = (isset($data_dec['ddns-hostname'])) ? $data_dec['ddns-hostname'] : '';
                                         $data_dec['ddns-updates'] = (isset($data_dec['ddns-updates'])) ? $data_dec['ddns-updates'] : '';


                                         $data_pass['params'] = array(
                                                                     'name' => $data_dec['name'],
                                                                     'authoritative' => $authoritative,
                                                                     'lastcomment' => $data_dec['lastcomment'],
                                                                     'hosts' => $coll_hosts,
                                                                     'groups' => $coll_groups,
                                                                     'subnets' => $coll_subnets,
                                                                      /*
                                                                       *
                                                                       *
                                                                       * default params
                                                                       */
                                                                     'filename'=>$data_dec['filename'],
                                                                     'next-server' => $data_dec['next-server'],
                                                                     'dynamic-bootp-lease-length' => $data_dec['dynamic-bootp-lease-length'],
                                                                     'ddns-rev-domainname' => $data_dec['ddns-rev-domainname'],
                                                                     'default-lease-time' => $data_dec['default-lease-time'],
                                                                     'max-lease-time' => $data_dec['max-lease-time'],
                                                                     'server-name' => $data_dec['server-name'],
                                                                     'dynamic-bootp-lease-cutoff' => $data_dec['dynamic-bootp-lease-cutoff'],
                                                                     'ddns-domainname' => $data_dec['ddns-domainname'],
                                                                     'ddns-hostname' => $data_dec['ddns-hostname'],
                                                                     'ddns-updates' => $data_dec['ddns-updates']
                                                                     // end
                                        );
                                       
                                       if(isset($data_dec['deny'])){
                                            $deny = $data_dec['deny'];
                                            if(!is_array($deny))
                                                $data_pass['params'][$deny] = 'deny';
                                            else
                                                foreach($deny as $data_deny){
                                                    $data_pass['params'][$data_deny] = 'deny';
                                                }
                                       }

                                       if(isset($data_dec['allow'])){
                                            $allow = $data_dec['allow'];
                                            if(!is_array($allow))
                                                $data_pass['params'][$allow] = 'allow';
                                            else
                                                foreach($allow as $data_allow)
                                                    $data_pass['params'][$data_allow] = 'allow';
                                       }


                                       if(isset($data_dec['ignore'])){
                                            $ignore = $data_dec['ignore'];
                                            if(!is_array($ignore))
                                                $data_pass['params'][$ignore] = 'ignore';
                                            else
                                                foreach($ignore as $data_ignore)
                                                    $data_pass['params'][$data_ignore] = 'ignore';
                                       }


                                       $elements[] = $data_pass;

                                    }
                                    $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
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
                        case 'save_configfile_content':
                                 $return = array('success' => true);
                                 break;
                        default : $return = array('success' => false,'error'=>'No action \''.$method.'\' defined yet',
                                            'info'=>'No action \''.$method.'\' implemented yet');

            }
            return $return;
        }
        else{
            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];
            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }


    }


    /*
     * ETFW squid dispatcher...
     */
    public function ETFW_squid(EtvaServer $etva_server, $method, $params,$mode)
    {

        // prepare soap info....
        $initial_params = array(
                        'dispatcher'=>'squid'
        );

        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);


        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
                case 'get_proxy_ports':
                                $proxy_ports = (array) $response_decoded['http_port'];
                                $elements = array();
                                foreach($proxy_ports as $index=>$data){
                                    $port = $data;
                                    $ip = $options = '';
                                    if(!(strpos($data,':')===false)){
                                        $pieces = explode(':',$data);
                                        $ip = $pieces[0];
                                        $port_options = $pieces[1];
                                        if(!(strpos($port_options,' ')===false)){
                                            $port_options_pieces = explode(' ',$port_options);
                                            $port = $port_options_pieces[0];
                                            $options = $port_options_pieces[1];
                                        }

                                        
                                    }

                                    
                                    
                                    $elements[] = array('port'=>$port,'ip_address'=>$ip,'options'=>$options);

                                }
                                $return = array('success' => true,'total' =>   count($elements),'data'  => $elements);
                                break;
                default        :
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

    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);        
        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
        return $error;

    }

}
