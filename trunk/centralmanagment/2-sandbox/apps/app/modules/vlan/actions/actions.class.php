<?php

/**
 * vlan actions.
 *
 * @package    centralM
 * @subpackage vlan
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * vlan actions controller
 * @package    centralM
 * @subpackage vlan
 *
 */
class vlanActions extends sfActions
{
    /**
     * Creates vlan
     *
     * Issues soap request to all nodes
     *
     * @return json array string
     * 
     */
    public function executeJsonCreate(sfWebRequest $request)
    {
        $netname = $request->getParameter('name');
        
        $etva_nodes = EtvaNodePeer::doSelect(new Criteria());

        $oks = array();
        $errors = array();
        $method = 'create_network';
        $params = array(
                        'name'=>$netname
                       );
                       
        // if network exists stop
        if($etva_vlan = EtvaVlanPeer::retrieveByName($netname)){

            $error = array('success'=>false,'error'=>array($netname.': Network already exist'));

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);


        }

        // send soap request to all nodes (agents)
        foreach($etva_nodes as $etva_node){

            // send soap request
            $response = $etva_node->soapSend($method,$params);

            if($response['success']){

                $response_decoded = (array) $response['response'];
                $returned_status = $response_decoded['_okmsg_'];                                

                $oks[] =  $etva_node->getName().': VLAN '.$netname.' - '.$returned_status;
                
            }else
                // soap response error....
                {

                $error_decoded = $response['error'];
                $errors[] = $etva_node->getName().': VLAN '.$netname.' - '.$error_decoded;

            }

        }

        if(!empty($errors)){

            $result = array('success'=>false,'error'=>$errors);
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }

        // create DB vlan and store
        $etva_vlan = new EtvaVlan();
        $etva_vlan->setName($netname);
        $etva_vlan->save();


        $result = array('success'=>true,'response'=>$oks);

        $return = json_encode($result);

        // if the request is made throught soap request...
        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
        // if is browser request return text renderer
        return  $this->renderText($return);
                  
    }
    
    /**
     * Removes vlan
     *
     * Issues soap request to all nodes
     *
     * @return json array string
     *
     */
    public function executeJsonRemove(sfWebRequest $request)
    {
        $netname = $request->getParameter('name');

        $etva_nodes = EtvaNodePeer::doSelect(new Criteria());

        $oks = array();
        $errors = array();
        $method = 'destroy_network';
        $params = array(
                        'name'=>$netname
                       );

        if(!$etva_vlan = EtvaVlanPeer::retrieveByName($netname)){

            $error = array('success'=>false,'error'=>array($netname.': Network not found'));

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        // check if is in use....
        $c = new Criteria();
        $c->add(EtvaNetworkPeer::VLAN,$etva_vlan->getName());

        if($etva_network = EtvaNetworkPeer::doSelectOne($c)){

            $error = array('success'=>false,'error'=>array($netname.': Network in use'));

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        // send soap request to all nodes (agents)
        foreach($etva_nodes as $etva_node){

            // send soap request
            $response = $etva_node->soapSend($method,$params);

            if($response['success']){

                $response_decoded = (array) $response['response'];
                $returned_status = $response_decoded['_okmsg_'];

                $oks[] =  $etva_node->getName().': VLAN '.$netname.' - '.$returned_status;

            }else
                // soap response error....
                {

                $error_decoded = $response['error'];
                $errors[] = $etva_node->getName().': VLAN '.$netname.' - '.$error_decoded;

            }

        }
       // $errors[] = 'da';
        if(!empty($errors)){

            $result = array('success'=>false,'error'=>$errors);
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }
    
        $etva_vlan->delete();

        $result = array('success'=>true,'response'=>$oks);

        $return = json_encode($result);

        // if the request is made throught soap request...
        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
        // if is browser request return text renderer
        return  $this->renderText($return);


    }
    

    
    /**
     * returns json array encoded list of vlans
     * 
     */
    public function executeJsonList(sfWebRequest $request)
    {

        $c = new Criteria();

        $etva_vlan_list = EtvaVlanPeer::doSelect($c);

        $list = array();
        foreach($etva_vlan_list as $vlan)
                    $list[] = $vlan->toArray();


        $result = array('total' =>   count($list),'data'  => $list);

        $result = json_encode($result);

        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap'){
            $soap_result = array( success=>true, 'total' =>   count($list), 'response' => $list);
            return json_encode($soap_result);
        }


        $this->getResponse()->setHttpHeader("X-JSON", '()');
        return $this->renderText($result);
    }
    

  public function executeJsonTree(sfWebRequest $request)
  {
    $etva_vlan_list = EtvaVlanPeer::doSelect(new Criteria());

    $tree = array();
    foreach($etva_vlan_list as $vlan)
                $tree[] = array('id'=>$vlan->getId(),'uiProvider'=>'col','iconCls'=>'devices-folder','text'=> 'Vlan '.$vlan->getId(),'name'=>$vlan->getName(),'singleClickExpand'=>true,'leaf'=>true);
                                    
                
    
    $result = json_encode($tree);

    $this->getResponse()->setHttpHeader("X-JSON", '()');
    return $this->renderText($result);
  }


  /**
   * Used to return errors messages
   *
   * @param string $info error message
   * @param int $statusCode HTTP STATUS CODE
   * @return array json array
   */
  protected function setJsonError($info,$statusCode = 400){

      $this->getContext()->getResponse()->setStatusCode($statusCode);
      $error = json_encode($info);
      $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
      return $error;

  }

  /**
   * Used to process soap requests => updateVirtAgentVlans
   *
   * Updates logical volume info sent by virt Agent
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uid: uid (virtAgent sending request uid)
   * - vlans (object containing vlans info)
   * @return array array(new vlans)
   */
  public function executeSoapUpdate(sfWebRequest $request)
  {

  

       
    if(SF_ENVIRONMENT == 'soap'){

        $vlans = $request->getParameter('vlans');


        
        $vlans_names = array();
        foreach($vlans as $name=>$vlanInfo){
            $vlans_names[] = $name;
        }



        $c = new Criteria();
        $c->add(EtvaNodePeer::UID ,$request->getParameter('uid'));



        if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
            $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uid'));
            $error = array('success'=>false,'error'=>$error_msg);            
            return $error;
        }

       

        $etva_vlans = EtvaVlanPeer::doSelect(new Criteria());
        // $node_vlans = EtvaVlanPeer::doSelect(new Criteria());


       
        // return $node_vlans;
        // $result = 'Success';
        $new_vlans =array();
        foreach($etva_vlans as $etva_vlan){
    
            if(!in_array($etva_vlan->getName(),$vlans_names)){


                    $new_vlans[$etva_vlan->getName()] = array('name'=>$etva_vlan->getName());

            
            }
            
        
            
        }

        
        return $new_vlans;

     }// end soap request
       
  }  
    
  
}
