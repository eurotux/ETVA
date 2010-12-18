<?php
/**
 * physicalvol actions.
 *
 * @package    centralM
 * @subpackage physicalvol
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * physical volume actions controller
 * @package    centralM
 * @subpackage physicalvol
 *
 */
class physicalvolActions extends sfActions
{
   /**
   * Initializes a physical device
   *
   * $request may contain the following keys:
   * - nid: virt agent node ID
   * - dev: device name Ex: /dev/sdb1
   *
   * Returns json array('success'=>true,'response'=>$resp) on success
   * <br>or<br>
   * json array('success'=>false,'error'=>$error) on error
   *
   */
    // sends soap request and store info in DB after receiving SOAP response
    public function executeJsonInit(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');
        $dev = $request->getParameter('dev');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                $error = array('success'=>false,'error'=>'No node exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }

        // get DB info
        if(!$etva_pv = $etva_node->retrievePhysicalvolumeByDevice($dev)){

            $error = array('success'=>false,'error'=>'Non existent device');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        // prepare soap info....        

        $method = 'pvcreate';
        // pvcreate expects: device = /dev/device

        $params = array(
                        'device'=>$dev
        );
        
        // send soap request
        $response = $etva_node->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            // update DB info
            $pv = $returned_object['pv'];
            $size = $returned_object['size'];
            $freesize = $returned_object['freesize'];
            $pvinit = $returned_object['pvinit'];

            $etva_pv->setPv($pv);
            $etva_pv->setPvsize($size);
            $etva_pv->setPvfreesize($freesize);
            $etva_pv->setPvinit($pvinit);
            $etva_pv->setAllocatable(1);

            $etva_pv->save();

            $msg = $etva_pv->getName().': '.$returned_status;

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
            $error = $etva_pv->getName().': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);


            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }



    }

    /**
   * Uninitializes the physical volume
   *
   * Unsets the physical volume info.
   *
   * $request may contain the following keys:
   * - nid: virt agent node ID
   * - dev: device name Ex: /dev/sdb1
   *
   * Returns json array('success'=>true,'response'=>$resp) on success
   *  <br>or<br>
   * json array('success'=>false,'error'=>$error) on error
   *
   */
    public function executeJsonUninit(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $dev = $request->getParameter('dev');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                $error = array('success'=>false,'error'=>'No node exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }        

        // get DB info
        if(!$etva_pv = $etva_node->retrievePhysicalvolumeByDevice($dev)){

            $error = array('success'=>false,'error'=>'Non existent device');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        // prepare soap info....

        $method = 'pvremove';
        // expects: device = /dev/device

        $params = array(
                        'device'=>$dev
        );
        
        // send soap request
        $response = $etva_node->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];

            // unset physical volume information
            $etva_pv->setPvinit('');
            $etva_pv->setPv('');
            $etva_pv->setPvsize('');
            $etva_pv->setPvfreesize('');
            $etva_pv->setAllocatable(0);

            $etva_pv->save();

            $msg = $etva_pv->getName().': '.$returned_status;

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
            $error = $etva_pv->getName().': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }

    }






  /**
   * Return pre-formatted data for tree-column extjs
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array
   */
    public function executeJsonPhydiskTree(sfWebRequest $request)
    {
      /* criteria to select only node ID column matching with nid
      */
        $criteria = new Criteria();
        $criteria->clearSelectColumns();
        $criteria->add(EtvaPhysicalvolumePeer::NODE_ID,$request->getParameter('nid'));

        $criteria->addSelectColumn(EtvaPhysicalvolumePeer::STORAGE_TYPE);
        $criteria->setDistinct();

        // select distinct storage types
        $stmt = EtvaPhysicalvolumePeer::doSelectStmt($criteria);

        $storages = array();
        // foreach storage type build criteria....
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {

            $storage_type = $row[0]; // the storage type
            $criteria = new Criteria();
            $criteria->add(EtvaPhysicalvolumePeer::NODE_ID,$request->getParameter('nid'));
            $criteria->add(EtvaPhysicalvolumePeer::STORAGE_TYPE,$storage_type);

            $list = EtvaPhysicalvolumePeer::doSelect($criteria);
            $children = array();
            // foreach physical volume matchs storage_type....build children
            foreach ($list as $elem){
                $id = $elem->getId();
                $device = $elem->getDevice();
                $qtip = 'Physical volume NOT initialized';
                $type = $cls = 'dev-pd';
                $tag = $elem->getName();

                $pvsize = $elem->getPvsize();
                $pretty_pvsize = $elem->getPvsize();

                $pretty_size = $elem->getDevsize();
                $size = $elem->getDevsize();



                if($elem->getPvinit()){ $qtip = 'Physical volume initialized';
                    $type= 'dev-pv';
                    $cls = 'dev-pv';
                }

                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap')
                $children[$id] = array('device'=>$device,'uiProvider'=>'col','iconCls'=>'task','cls'=>$cls,'text'=>$tag,'size'=>$size,'prettysize'=>$pretty_size,'pvsize'=>$pvsize,'pretty-pvsize'=>$pretty_pvsize, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'leaf'=>true);
                else  $children[] = array('id'=>$id,'device'=>$device,'uiProvider'=>'col','iconCls'=>'task','cls'=>$cls,'text'=>$tag,'size'=>$size,'prettysize'=>$pretty_size,'pvsize'=>$pvsize,'pretty-pvsize'=>$pretty_pvsize, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'leaf'=>true);
            }

            $storages[] = array('id'=>$storage_type,'uiProvider'=>'col','iconCls'=>'devices-folder','text'=>$storage_type, 'singleClickExpand'=>true,'children'=>$children);


        }

        $return = json_encode($storages);

        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
        else{

            $this->getResponse()->setHttpHeader("X-JSON", '()');

            return $this->renderText($return);

        }

    }


  /**
   * Returns pre-formated data with physical volumes allocatable
   *   
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array('total'=>num elems, 'data'=>array('id'=>pvdevice,'name'=>name))
   */

  /*
   * Used in volume group create window and to list allocatable pv
   */

    public function executeJsonListAllocatable(sfWebRequest $request)
    {

        $elements = array();

        $nid = $request->getParameter('nid');

        $etva_node = EtvaNodePeer::retrieveByPK($nid);

        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::ALLOCATABLE, 1);
        $criteria->add(EtvaPhysicalvolumePeer::PVINIT, 1);

        $etva_pvs = $etva_node->getEtvaPhysicalvolumes($criteria);

        if(!$etva_pvs){

            $info = array('success'=>false,'error'=>'No free physical volumes available');

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($info);

            $error = $this->setJsonError($info,204); // 204 => no content
            return $this->renderText($error);


        }

        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap'){
            foreach ($etva_pvs as $elem){
                $id = $elem->getId();
                $pv = $elem->getPv();
                // $size = Etva::byte_to_MBconvert($size);
                $elements[$id] = array('id'=>$id,'pv'=>$pv);
            }

        }else{

            foreach ($etva_pvs as $elem){
                // $id = $elem->getId();
                $name = $elem->getName();
                $pv = $elem->getPv();
                // $size = Etva::byte_to_MBconvert($size);
                $elements[] = array('id'=>$pv,'name'=>$name);
            }

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


    public function executeJsonList(sfWebRequest $request)
    {

        $elements = array();

        $nid = $request->getParameter('nid');

        $etva_node = EtvaNodePeer::retrieveByPK($nid);

        $criteria = new Criteria();        

        $etva_pvs = $etva_node->getEtvaPhysicalvolumes($criteria);

        if(!$etva_pvs){

            $info = array('success'=>false,'error'=>'No physical volumes found');

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($info);

            $error = $this->setJsonError($info,204); // 204 => no content
            return $this->renderText($error);
        }
        
        foreach ($etva_pvs as $elem){
            $id = $elem->getId();
            $pv = $elem->getPv();
            // $size = Etva::byte_to_MBconvert($size);
            $elements[$id] = array('id'=>$id,'pv'=>$pv);
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
   * Used to return errors messages
   *
   * @param string $info error message
   * @param int $statusCode HTTP STATUS CODE
   * @return array json array
   */
    protected function setJsonError($info,$statusCode = 400){

        $error = json_encode($info);
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
        return $error;

    }

    

  /**
   * Used to process soap requests => updateVirtAgentDevices
   *
   * Updates physical volume/device info sent by virt Agent
   * The request should be made throught soapapi
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uid: uid (virtAgent sending request uid)
   * - devs (object containing devices info)
   * @return array array(success=>true)
   */


    public function executeSoapUpdate(sfWebRequest $request)
    {
      /*
       * Check if the request is made via soapapi.php interface
       */
        if(SF_ENVIRONMENT == 'soap'){

            $devs = $request->getParameter('devs');

            // check node ID correspondig to the uid given
            $c = new Criteria();
            $c->add(EtvaNodePeer::UID ,$request->getParameter('uid'));


            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uid'));
                $error = array('success'=>false,'error'=>$error_msg);
                return $error;
            }

            //check physical volumes of the agent
            $criteriaPV = new Criteria();
            $criteriaPV->add(EtvaPhysicalvolumePeer::NODE_ID,$etva_node->getId());


            foreach($devs as $dev=>$devInfo){

                /*
                 * check if already in DB the data
                 * if not, insert it, otherwise update
                 */
                $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByDevice($devInfo->device,$criteriaPV);
                if(!$etva_physicalvol) $etva_physicalvol = new EtvaPhysicalvolume();


                $etva_physicalvol->setNodeId($etva_node->getId());
                $etva_physicalvol->setName($dev);
                $etva_physicalvol->setDevice($devInfo->device);
                $etva_physicalvol->setDevsize($devInfo->size);
                $etva_physicalvol->setPv($devInfo->pv);
                $etva_physicalvol->setPvsize($devInfo->pvsize);
                $etva_physicalvol->setPvfreesize($devInfo->pvfreesize);
                $etva_physicalvol->setPvinit($devInfo->pvinit);
                $etva_physicalvol->setStorageType($devInfo->type);

                // set allocatable flag
                //if has vg associated it cannot be allocatable to another vg
                if(empty($devInfo->vg)) $etva_physicalvol->setAllocatable(1);
                else $etva_physicalvol->setAllocatable(0);


                $etva_physicalvol->save();

            }

            return array('success'=>true);
        }// end soap request

    }




}
