<?php


class etfwComponents extends sfComponents
{
    public function executeETFW_webmin_iframe()
    {
        $params = $this->etva_service->getParams();

        $params_decoded = json_decode($params,true);

        $this->url = $params_decoded['url'];

    }
    /*
     * get data to build initial firewall rules layout.... used by ETFW_firewall_rules template
     */
    public function executeETFW_firewall_rules()
    {
        $initial_params = array(
                        'dispatcher'=>'firewall'
        );

        $method = 'get_config_rules';
        $response = $this->etva_server->soapSend($method,$initial_params);

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];
            $rules = (array) $response_decoded['rules'];
            $boot = (array) $response_decoded['boot'];
            $boot_active = $boot['active'];

            if($rules)
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
                                $elements[$i]['action'] = ETFW_firewall::describe_rule_action($rule['j']);
                                $elements[$i]['condition'] = ETFW_firewall::describe_rule_condition($rule);
                                $i++;
                            }
                        }
                        $chain_data['rules'] = array('total' =>   count($elements),'data'  => $elements);
                        $chain_data['chain_desc'] = ETFW_firewall::describe_rule_chain($chain);                        
                    }

                }

            $criteria = new Criteria();
            $criteria->add(EtvaServicePeer::SERVER_ID,$this->etva_server->getId());
            $criteria->add(EtvaServicePeer::NAME_TMPL,'network');
            $network_dispatcher = EtvaServicePeer::doSelectOne($criteria);
            $this->network_dispatcher_id = $network_dispatcher->getId();
            $this->rules = $rules;
            $this->boot_active = $boot_active;
        }else{
            $this->network_dispatcher_id = 0;
            $this->rules = array();            
        }                
    }
    
    //component for dhcp interfaces listener
    public function executeETFW_dhcp_networkinterface()
    {

        $criteria = new Criteria();
        $criteria->add(EtvaServicePeer::SERVER_ID,$this->etva_server->getId());
        $criteria->add(EtvaServicePeer::NAME_TMPL,'network');
        $network_dispatcher = EtvaServicePeer::doSelectOne($criteria);
        $this->network_dispatcher_id = $network_dispatcher->getId();        
    }





}
