<?php

/**
 * volgroup actions.
 *
 * @package    centralM
 * @subpackage volgroup
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * volume group actions controller
 * @package    centralM
 * @subpackage volgroup
 *
 */
class volgroupActions extends sfActions
{    


   /**
   * Updates a volume group
   *
   * If volume group is new it will be created
   * else it will be updated
   *
   * $request may contain the following keys:
   * - nid: node ID
   * - vg: volume group name
   * - pvs: json encoded array with physical volumes
   * @return json array('success'=>true,'response'=>$data)
   * @return json array('success'=>false,'error'=>$errordata)
   */

    public function executeJsonUpdate(sfWebRequest $request){

        $nid = $request->getParameter('nid');
        // volume group name to create...
        $vg = $request->getParameter('vg');                
        
        // physical volume id to be inserted into new volume group
        //TODO : Currently only accept one physicalvolume!!!

        // string containing ids (1,2,3)
        $pvs = json_decode($request->getParameter('pvs'),true);
        

        $params = array();
        $etva_pvs = array();
        $i = 0;
        
        
        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                $error = array('success'=>false,'error'=>'No node exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }


        // check if physical volumes exist in DB and are allocatable
        foreach($pvs as $pv){

            // get DB info by primary key
            if(!$etva_pvs[$i] = $etva_node->retrievePhysicalvolumeByPv($pv)){

                $error = array('success'=>false,'error'=>'No physical volume exist on this node');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }

            if(!$etva_pvs[$i]->getAllocatable()){

                $error = array('success'=>false,'error'=> $etva_pvs[$i]->getPv().' : Physical volume not available');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }


            $pvIndex = 'pv'.$etva_pvs[$i]->getId();
            $params[$pvIndex] = $etva_pvs[$i]->getPv();

            $i++;

        }
                                        
        
      // prepare soap info....                

        $method = 'vgextend';

        // if volgroup doesnt existe then method is to create
        if(!$etva_volgroup = $etva_node->retrieveVolumegroupByVg($vg)){
            $method = 'vgcreate';
            $etva_volgroup = new EtvaVolumegroup();
        }

        // expects: pv1 = /dev/physicalvol
        //          vgname = name
        $params['vgname'] = $vg;
      

        // send soap request
        $response = $etva_node->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){            

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];            

            // set volume group info
            $etva_volgroup->setEtvaNode($etva_node);
            $etva_volgroup->setVg($vg);

            // set volume group size....
            $vgsize_new = $returned_object['size'];
            $vgfree_new = $returned_object['freesize'];

            $etva_volgroup->setSize($vgsize_new);
            $etva_volgroup->setFreesize($vgfree_new);


            // update physical volume size

            // get new size from response data
            $ret_physicalvolumes = (array) $returned_object['physicalvolumes'];

            foreach($etva_pvs as $etva_pv){

                if(array_key_exists($etva_pv->getName(),$ret_physicalvolumes)){

                    $ret_pv_data = (array) $ret_physicalvolumes[$etva_pv->getName()];

                    $pvsize_new = $ret_pv_data['size'];
                    $pvfree_new = $ret_pv_data['freesize'];
                    //update values....
                    $etva_pv->setPvsize($pvsize_new);
                    $etva_pv->setPvfreesize($pvfree_new);

                    // add physical volume to volume group
                    $etva_volpvs = new EtvaVolumePhysical();
                    $etva_volpvs->setEtvaPhysicalvolume($etva_pv);
                    $etva_volpvs->setEtvaVolumegroup($etva_volgroup);

                    $etva_volpvs->save();

                }


            }                                          
            
            $msg = $vg.': '.$returned_status;

            $result = array('success'=>true,'response'=>$msg);

            $return = json_encode($result);

            // if the request is made throught soap request...
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
            // if is browser request return text renderer
            return  $this->renderText($return);
            
        }else
            // soap response error....
            // DB information not updated
            {
            $error_decoded = $response['error'];
            $error = $vg.': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);
            
           
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);
            
        }

    }


    /**
     * Removes volume group
     * 
     * $request may contain the following keys:
     * - nid: node ID
     * - vg: volume group name
     *
     */
    public function executeJsonRemove(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $vg = $request->getParameter('vg');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                $error = array('success'=>false,'error'=>'No node exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }


        if(!$etva_vg = $etva_node->retrieveVolumegroupByVg($vg)){

                $error = array('success'=>false,'error'=>'Non existent volume group');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }        
        

      // prepare soap info....

        $vgname = $etva_vg->getVg();

        $method = 'vgremove';

        // expects: vgname = name

        $params = array(
                        'vgname'=>$vgname                        
                       );

        
        // send soap request
        $response = $etva_node->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            // removes volgroup and updates physical volume info
            $etva_vg->delete();

            $msg = $vgname.': '.$returned_status;

            $result = array('success'=>true,'response'=>$msg);

            $return = json_encode($result);

            // if the request is made throught soap request...
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
            // if is browser request return text renderer
            return  $this->renderText($return);
                        
        }else
            // soap response error....
            // DB information not updated
            {


            $error_decoded = $response['error'];
            $error = $vgname.': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

            
        }


    }
    

    /**
    * Reduces the volume group by n physical volumes
    *
    * $request may contain the following keys:    
    * - nid: node ID
    * - vg: volume group name
    * - pvs: json array with physical volumes
    *
    */
    public function executeJsonReduce(sfWebRequest $request)
    {
        
        $nid = $request->getParameter('nid');

        $vg = $request->getParameter('vg');

        // physical volume id
        $pvs = json_decode($request->getParameter('pvs'),true);

       // $pvs = explode(',',$pvs);


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                $error = array('success'=>false,'error'=>'No node exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }

        $etva_pvs = array();
        $etva_volpvs = array();
        $params = array();
        $i = 0;
        
        foreach($pvs as $pv){

            if(!$etva_pvs[$i] = $etva_node->retrievePhysicalvolumeByPv($pv)){

                $error = array('success'=>false,'error'=>'Non existent physical volume');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }
            


            // get DB info
            if(!$etva_volpvs[$i] = EtvaVolumePhysicalPeer::retrieveByEtvaPhysicalvolumeId($etva_pvs[$i]->getId())){


                $error = array('success'=>false,'error'=>$etva_pvs[$i]->getPv().' : Non existent physical volume in volume group');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }
            

            $etva_vol = $etva_volpvs[$i]->getEtvaVolumegroup();
            
            $vgname = $etva_vol->getVg();
            

            if($vgname<>$vg){

                $error = array('success'=>false,'error'=>'Volume group not match');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);
            }



            

            $pvIndex = 'pv'.$etva_pvs[$i]->getId();
            $params[$pvIndex] = $etva_pvs[$i]->getPv();

            $i++;

            

        }// end foreach               

      // prepare soap info....
        

        $method = 'vgreduce';

        // expects: pv1 = /dev/physicalvol
        //          vgname = name

        $params['vgname'] = $vg;
                                               
        
        // send soap request
        $response = $etva_node->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

           
            // update volume group size....
            $vgsize_new = $returned_object['size'];
            $vgfree_new = $returned_object['freesize'];

            $etva_vol->setSize($vgsize_new);
            $etva_vol->setFreesize($vgfree_new);
            
            foreach($etva_volpvs as $etva_volpv){
                $etva_volpv->delete();
            }
            $etva_vol->save();

            $msg = $etva_vol->getVg().': '.$returned_status;

            $result = array('success'=>true,'response'=>$msg);

            $return = json_encode($result);

            // if the request is made throught soap request...
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
            // if is browser request return text renderer
            return  $this->renderText($return);
            
        }else
            // soap response error....
            // DB information not updated
            {

            $error_decoded = $response['error'];
            $error = $etva_vol->getVg().': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);


            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }


    }
   
  
  /**
   * Returns pre-formated data for Extjs combo box with volume group free size available
   *  
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array('total'=>num elems, 'data'=>array('value'=>value,'name'=>name))
   */

  /*
   * Used in logical volume create window
   * Used in server creation wizard window
   */
    public function executeJsonListFree(sfWebRequest $request)
    {

        $elements = array();

        $criteria = new Criteria();
        $criteria->add(EtvaVolumegroupPeer::NODE_ID,$request->getParameter('nid'));

        $criteria->add(EtvaVolumegroupPeer::FREESIZE,0,Criteria::NOT_EQUAL);

        $etva_vgs = EtvaVolumegroupPeer::doSelect($criteria);

        if(!$etva_vgs){
            $info = array('success'=>false,'error'=>'No volume groups available');
            $error = $this->setJsonError($info,204); // 204 => no content
            return $this->renderText($error);
        }

        foreach ($etva_vgs as $elem){
            $id = $elem->getId();
            $size = $elem->getFreesize();
            $txt = $elem->getVg();
            // $size = Etva::byte_to_MBconvert($size);
            $elements[] = array('id'=>$id,'name'=>$txt,'value'=>$size);

        }

        $result = array('total' =>   count($elements),'data'  => $elements);

        $return = json_encode($result);
        $this->getResponse()->setHttpHeader("X-JSON", '()');
        //
        //
        return $this->renderText($return);


    }

    /**
     * List volume groups
     *
     * Returns json array('id'=>id,'vg'=> vg name)
     *
     *
     */
    public function executeJsonList(sfWebRequest $request)
    {

        $elements = array();

        $nid = $request->getParameter('nid');

        $etva_node = EtvaNodePeer::retrieveByPK($nid);
        
        $etva_vgs = $etva_node->getEtvaVolumegroups();

        if(!$etva_vgs){

            $info = array('success'=>false,'error'=>'No volume groups available');

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($info);

            $error = $this->setJsonError($info,204); // 204 => no content
            return $this->renderText($error);

            
        }

        foreach ($etva_vgs as $elem){
            $id = $elem->getId();            
            $vg = $elem->getVg();
            // $size = Etva::byte_to_MBconvert($size);
            $elements[$id] = array('id'=>$id,'vg'=>$vg);

        }
        
         $result = array('success'=>true,
                    'total'=> count($elements),
                    'response'=> $elements
                   );


        $return = json_encode($result);

        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;

        $this->getResponse()->setHttpHeader("X-JSON", '()');
        return $this->renderText($return);
       

    }


  /**
   * Return pre-formatted data for tree-column extjs
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array
   */
    public function executeJsonVgsTree(sfWebRequest $request)
    {

        $criteria = new Criteria();
        $criteria->add(EtvaVolumegroupPeer::NODE_ID,$request->getParameter('nid'));

        $vgs_list = EtvaVolumegroupPeer::doSelect($criteria);

        $volumes = array();

        foreach ($vgs_list as $vg){
            $etva_vp = $vg->getEtvaVolumePhysicals();


            $pvs_tree = array();


            foreach($etva_vp as $vp){

                $pv = $vp->getEtvaPhysicalvolume();

                $id = $pv->getId();
                $elem = $pv->getName();
                $pvdevice = $pv->getPv();
                $pretty_size = $size = $pv->getPvsize();
                $qtip = '';
                $cls = 'dev-pv';

                $pvs_tree[] = array('id'=>$id,'cls'=>$cls,'uiProvider'=>'col','iconCls'=>'task','text'=>$elem,'pv'=>$pvdevice,'size'=>$size,'prettysize'=>$pretty_size,'singleClickExpand'=>true,'type'=>'dev-pv','qtip'=>$qtip,'leaf'=>true);


            }


            $id = $vg->getVg();
            $vgid = $vg->getId();
            $qtip = '';
            $cls = 'vg';
            $pretty_size = $size = $vg->getSize();
           
            $volumes[] = array('id'=>$id,'vgid'=>$vgid,'uiProvider'=>'col','iconCls'=>'devices-folder','cls'=>$cls,'text'=>$id,'type'=>'vg','size'=>$size,'prettysize'=>$pretty_size, 'singleClickExpand'=>true,'qtip'=>$qtip,'children'=>$pvs_tree);


        }

        $return = json_encode($volumes);
        $this->getResponse()->setHttpHeader("X-JSON", '()');


        return $this->renderText($return);
      

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
   * Used to process soap requests => updateVirtAgentVgs
   *
   * Updates volume group info sent by virt Agent
   * The request should be made throught soapapi
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uid: uid (virtAgent sending request uid)
   * - vgs (object containing volumes info)
   * @return array array(success=>true)
   */
    public function executeSoapUpdate(sfWebRequest $request)
    {

      /*
       * Check if the request is made via soapapi.php interface
       */
        if(SF_ENVIRONMENT == 'soap'){

            $vgs = $request->getParameter('vgs');

            $c = new Criteria();
            $c->add(EtvaNodePeer::UID ,$request->getParameter('uid'));



            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uid'));
                $error = array('success'=>false,'error'=>$error_msg);
                return $error;
            }



            $criteriaVG = new Criteria();
            $criteriaVG->add(EtvaVolumegroupPeer::NODE_ID,$etva_node->getId());

            $criteriaPV = new Criteria();
            $criteriaPV->add(EtvaPhysicalvolumePeer::NODE_ID,$etva_node->getId());

            // for each volume group recieved by virt agent....check if exists in DB
            foreach($vgs as $vg=>$vgInfo){

                $etva_volgroup = EtvaVolumegroupPeer::retrieveByVg($vg,$criteriaVG);



                if(!$etva_volgroup) $etva_volgroup = new EtvaVolumegroup();


                $etva_volgroup->setNodeId($etva_node->getId());
                $etva_volgroup->setVg($vg);
                $etva_volgroup->setSize($vgInfo->size);
                $etva_volgroup->setFreesize($vgInfo->freesize);

                $vg_devs = $vgInfo->physicalvolumes;

                foreach($vg_devs as $dev=>$devInfo){

                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByPV($devInfo->pv,$criteriaPV);
                    if(!$etva_physicalvol) $etva_physicalvol = new EtvaPhysicalvolume();


                    $etva_physicalvol->setNodeId($etva_node->getId());
                    $etva_physicalvol->setName($dev);
                    $etva_physicalvol->setDevice($devInfo->device);

                    $etva_physicalvol->setPv($devInfo->pv);
                    $etva_physicalvol->setPvsize($devInfo->size);
                    $etva_physicalvol->setPvfreesize($devInfo->freesize);


                    $etva_vg_phy = EtvaVolumePhysicalPeer::retrieveByVGPV($etva_volgroup->getId(),$etva_physicalvol->getId());

                    if(!$etva_vg_phy) $etva_vg_phy = new EtvaVolumePhysical();

                    $etva_vg_phy->setEtvaPhysicalvolume($etva_physicalvol);
                    $etva_vg_phy->setEtvaVolumegroup($etva_volgroup);
                    $etva_vg_phy->save();


                }


            }

            return array('success'=>true);
        }

    }

}
