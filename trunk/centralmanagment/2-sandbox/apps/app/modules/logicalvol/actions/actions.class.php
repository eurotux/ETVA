<?php

/**
 * logicalvol actions.
 *
 * @package    centralM
 * @subpackage logicalvol
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * logical volume actions controller
 * @package    centralM
 * @subpackage logicalvol
 *
 */
class logicalvolActions extends sfActions
{

   /**
   * Inserts a logical volume
   *
   *
   * $request may contain the following keys:
   * - nid: node ID
   * - lv: logical volume name
   * - vg: volume group name
   * - size: logical volume size
   *
   */
    public function executeJsonCreate(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $lv = $request->getParameter('lv');
        $size = $request->getParameter('size');
        $vg = $request->getParameter('vg');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'Virt Agent not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }


        if($etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){


            $error = array('success'=>false,'error'=>'Logical volume already exists');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }


        if(!$etva_vg = $etva_node->retrieveVolumegroupByVg($vg)){

            $error = array('success'=>false,'error'=>'Volume group not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }


        $method = 'lvcreate';

        $params = array(
                        'lv'=>$lv,
                        'vg'=>$vg,
                        'size'=>$size
        );

        $response = $etva_node->soapSend($method,$params);

        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            // get some info from response...
            $lvdevice = $returned_object['lvdevice'];

            $size = $returned_object['size'];
            $freesize = $returned_object['freesize'];
            $type = $returned_object['type'];
            $writeable = $returned_object['writeable'];


            //update volume group free size info...
            $ret_vgfreesize = $returned_object['vgfreesize'];
            $etva_vg->setFreesize($ret_vgfreesize);


            // create logical volume
            $etva_lv = new EtvaLogicalvolume();

            $etva_lv->setLv($lv);
            $etva_lv->setLvdevice($lvdevice);
            $etva_lv->setSize($size);
            $etva_lv->setFreesize($freesize);
            $etva_lv->setStorageType($type);
            $etva_lv->setWriteable($writeable);

            $etva_lv->setEtvaVolumegroup($etva_vg);
            $etva_lv->setEtvaNode($etva_node);




            // update physical volume size
            $etva_volphys = $etva_vg->getEtvaVolumePhysicalsJoinEtvaPhysicalvolume();

            $ret_volumegroup = (array) $returned_object['volumegroup'];
            $ret_physicalvolumes = (array) $ret_volumegroup['physicalvolumes'];

            // for each physical volume get position in respnse array and update sizes...
            foreach($etva_volphys as $etva_volphy){

                $etva_phy = $etva_volphy->getEtvaPhysicalvolume();

                $ret_pv_data = (array) $ret_physicalvolumes[$etva_phy->getName()];

                $pvsize_new = $ret_pv_data['size'];
                $pvfree_new = $ret_pv_data['freesize'];

                $etva_phy->setPvsize($pvsize_new);
                $etva_phy->setPvfreesize($pvfree_new);


            }

            /*
            * saves logical volume
             * on save sets volume group size info...
            */
            $etva_lv->save();

            $msg = $lv.': '.$returned_status;

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
            $error = $lv.': '.$error_decoded;

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
     * - lv: logical volume name
     *
     */
    public function executeJsonRemove(sfWebRequest $request){

        // logical volume id
        $nid = $request->getParameter('nid');
        $lv = $request->getParameter('lv');


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'Virt Agent not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){

            $error = array('success'=>false,'error'=>'Logical volume not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }


        $etva_vg = $etva_lv->getEtvaVolumegroup();


        // prepare soap info....

        $lvdevice = $etva_lv->getLvdevice();

        $method = 'lvremove';

        // var params = {'lv':ctx.attributes.vg+'/'+ctx.text};

        $params = array(
                        'lv'=>$lvdevice
        );


        // send soap request
        $response = $etva_node->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];


            // update physical volume size
            $etva_volphys = $etva_vg->getEtvaVolumePhysicalsJoinEtvaPhysicalvolume();

            $ret_volumegroup = (array) $returned_object['volumegroup'];
            $ret_physicalvolumes = (array) $ret_volumegroup['physicalvolumes'];

            // for each physical volume get position in respnse array and update sizes...
            foreach($etva_volphys as $etva_volphy){

                $etva_phy = $etva_volphy->getEtvaPhysicalvolume();

                $ret_pv_data = (array) $ret_physicalvolumes[$etva_phy->getName()];

                $pvsize_new = $ret_pv_data['size'];
                $pvfree_new = $ret_pv_data['freesize'];

                $etva_phy->setPvsize($pvsize_new);
                $etva_phy->setPvfreesize($pvfree_new);

            }

            // save volume group updated...
            $etva_vg_freesize = $etva_vg->getFreesize();
            $etva_vg_freesize_new = $etva_vg_freesize+$etva_lv->getSize();
            $etva_vg->setFreesize($etva_vg_freesize_new);
            $etva_vg->save();

            // removes logical volume and updates physical volume info
            $etva_lv->delete();

            $msg = $lv.': '.$returned_status;

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
            $error = $lv.': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }


    }

    /**
     * Resizes volume group
     *
     * $request may contain the following keys:
     * - nid: node ID
     * - lv: logical volume name
     * - size: size
     *
     */
    public function executeJsonResize(sfWebRequest $request)
    {

        // logical volume id
        $nid = $request->getParameter('nid');
        $lv = $request->getParameter('lv');
        $size = $request->getParameter('size');


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'Virt Agent not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){

            $error = array('success'=>false,'error'=>'Logical volume not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }




        $etva_vg = $etva_lv->getEtvaVolumegroup();


        // prepare soap info....

        $lvdevice = $etva_lv->getLvdevice();

        $method = 'lvresize';

        // var params = {'lv':ctx.attributes.vg+'/'+ctx.text,'size':size};

        $params = array(
                        'lv'=>$lvdevice,
                        'size'=>$size
        );

        // send soap request
        $response = $etva_node->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            //update logical volume size
            $ret_size = $returned_object['size'];
            $ret_freesize = $returned_object['freesize'];

            $etva_lv->setSize($ret_size);
            $etva_lv->setFreesize($ret_freesize);

            //update volume group free size...
            $ret_vgfreesize = $returned_object['vgfreesize'];
            $etva_vg->setFreesize($ret_vgfreesize);


            // update physical volume size
            $etva_volphys = $etva_vg->getEtvaVolumePhysicalsJoinEtvaPhysicalvolume();

            $ret_volumegroup = (array) $returned_object['volumegroup'];
            $ret_physicalvolumes = (array) $ret_volumegroup['physicalvolumes'];

            // for each physical volume get position in respnse array and update sizes...
            foreach($etva_volphys as $etva_volphy){

                $etva_phy = $etva_volphy->getEtvaPhysicalvolume();

                $ret_pv_data = (array) $ret_physicalvolumes[$etva_phy->getName()];

                $pvsize_new = $ret_pv_data['size'];
                $pvfree_new = $ret_pv_data['freesize'];

                $etva_phy->setPvsize($pvsize_new);
                $etva_phy->setPvfreesize($pvfree_new);

            }


            $etva_lv->save();

            $msg = $lv.': '.$returned_status;

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
            $error = $lv.': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }


    }



    /**
     * List logical volumes
     *
     * Returns json array('id'=>id,'lv'=> lv name)
     *
     *
     */
    public function executeJsonList(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'Virt Agent not found');

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }
        
        $list = $etva_node->getEtvaLogicalvolumes();


        $lvs = array();

        foreach ($list as $elem){
            $id = $elem->getId();
            $lv = $elem->getLv();
            $lvs[$id] = array('id'=>$id,'lv'=>$lv);


        }

         $result = array('success'=>true,
                'total'=> count($lvs),
                'response'=> $lvs
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
    public function executeJsonLvsTree(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'Virt Agent not found');


            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        // retrieves array logical volumes with node ID containing vg info
        $list = $etva_node->getEtvaLogicalvolumesJoinEtvaVolumegroup();



        $lvs = array();

        foreach ($list as $elem){


            $etva_vg = $elem->getEtvaVolumegroup();
            //check data consistency....should be fine
            if($etva_vg){
                $id = $elem->getId();
                $text = $elem->getLv();
                $pretty_size = $elem->getSize();
                $size = $elem->getSize();

                $vg = $etva_vg->getVg();
                $vg_size = $etva_vg->getSize();
                $vg_freesize = $etva_vg->getFreesize();
                $cls = 'lv';
                $lvs[] = array('id'=>$id,'cls'=>$cls,'uiProvider'=>'col','iconCls'=>'devices-folder','text'=>$text,'size'=>$size,'prettysize'=>$pretty_size,'vgsize'=>$vg_size,'singleClickExpand'=>true,'type'=>'lv','vg'=>$vg,'vgfreesize'=>$vg_freesize,'leaf'=>true);
            }

        }

        $return = json_encode($lvs);
        $this->getResponse()->setHttpHeader("X-JSON", '()');


        return $this->renderText($return);

    }

  /**
   * Returns pre-formated data for Extjs combo with lvs available
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array
   */
    public function executeJsonGetAvailable(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'Virt Agent not found');


            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $criteria = new Criteria();
        $criteria->add(EtvaLogicalvolumePeer::WRITEABLE, 1);
        $criteria->add(EtvaLogicalvolumePeer::IN_USE, 0);


        $etva_lvs = $etva_node->getEtvaLogicalvolumes($criteria);

        if(!$etva_lvs){
            $info = array('success'=>false,'error'=>'No logical volumes available');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }


        // build result to feed to combo...
        $elements = array();
        foreach ($etva_lvs as $lv){

            $size = $lv->getSize();
            // $size = Etva::byte_to_MBconvert($size);
            $elements[] = array('id'=>$lv->getLvdevice(),'lv'=>$lv->getLv(),'size'=>$size);

        }


        $result = array('total' =>   count($elements),'data'  => $elements);

        $return = json_encode($result);
        $this->getResponse()->setHttpHeader("X-JSON", '()');
        return $this->renderText($return);

    }


    /*
     * export rra data in xml
     */
    public function executeXportDiskSpaceRRA(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $disk_rra = new ServerDiskSpaceRRA($etva_node->getName(),$etva_server->getName(),$etva_lv->getTarget());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($disk_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;

    }




    public function executeXportDiskRWRRA(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $disk_rra = new ServerDisk_rwspentRRA($etva_node->getName(),$etva_server->getName(),$etva_lv->getTarget());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($disk_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;

    }



    /*
     * returns logical volume disk space png image from rrd data file
     */
    public function executeGraphDiskSpacePNG(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');

        $disk_rra = new ServerDiskSpaceRRA($etva_node->getName(),$etva_server->getName(),$etva_lv->getTarget());
        $title = $etva_node->getName().'::'.$etva_server->getName().'-'.$etva_lv->getTarget();
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $disk_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;

    }


    /*
     * returns logical volume disk r/w png image from rrd data file
     */
    public function executeGraphDiskRWPNG(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');

        $disk_rra = new ServerDisk_rwspentRRA($etva_node->getName(),$etva_server->getName(),$etva_lv->getTarget());
        $title = $etva_node->getName().'::'.$etva_server->getName().'-'.$etva_lv->getTarget();
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $disk_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;
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
   * Used to process soap requests => updateVirtAgentLvs
   *
   * Updates logical volume info sent by virt Agent
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uid: uid (virtAgent sending request uid)
   * - lvs (object containing logical volumes info)
   * @return array array(success=>true)
   */
    public function executeSoapUpdate(sfWebRequest $request)
    {

        /*
        * Check if the request is made via soapapi.php interface
        */
        if(SF_ENVIRONMENT == 'soap'){

            $lvs = $request->getParameter('lvs');

            // check node ID correspondig to the uid given
            $c = new Criteria();
            $c->add(EtvaNodePeer::UID ,$request->getParameter('uid'));

            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uid'));
                $error = array('success'=>false,'error'=>$error_msg);
                return $error;
            }

            $criteriaVG = new Criteria();
            $criteriaVG->add(EtvaVolumegroupPeer::NODE_ID,$etva_node->getId());

            $criteriaLV = new Criteria();
            $criteriaLV->add(EtvaLogicalvolumePeer::NODE_ID,$etva_node->getId());

            // for each lv....
            foreach($lvs as $lv=>$lvInfo){

                // check if exists in DB
                $etva_logicalvol = EtvaLogicalvolumePeer::retrieveByLvDevice($lvInfo->lvdevice,$criteriaLV);

                // if NOT exists create new....else update som info
                if(!$etva_logicalvol) $etva_logicalvol = new EtvaLogicalvolume();


                $etva_logicalvol->setNodeId($etva_node->getId());


                // get volume group information for the lv
                $etva_vg = EtvaVolumegroupPeer::retrieveByVg($lvInfo->vg,$criteriaVG);

                if($etva_vg){

                    $etva_logicalvol->setVolumegroupId($etva_vg->getId());

                    $etva_logicalvol->setLv($lv);
                    $etva_logicalvol->setLvdevice($lvInfo->lvdevice);
                    $etva_logicalvol->setSize($lvInfo->size);
                    $etva_logicalvol->setFreesize($lvInfo->freesize);
                    //  $etva_logicalvol->setStorageType($lvInfo->type);
                    $etva_logicalvol->setWriteable($lvInfo->writeable);

                    $etva_logicalvol->save();
                }

            }// end foreach

            return array('success'=>true);

        }// end soap request


    }

}
