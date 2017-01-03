<?php

/**
 * mac actions.
 *
 * @package    centralM
 * @subpackage mac
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * MAC Addresses actions controller
 * @package    centralM
 * @subpackage mac
 *
 */
class macActions extends sfActions
{

    // in this case we want only to show createwin
    // no action to be performed
    public function executeCreatewin(sfWebRequest $request)
    {
        $sid = $request->getParameter('sid');
        if($sid){
            $server = EtvaServerPeer::retrieveByPK($sid);
            $this->cid = $server->getClusterId();
        }else{
            $this->cid = $request->getParameter('cid');
        }

    }



    /**
   * generate one unused mac address
   *
   * returns mac address
   *
   * @return string mac
   */
    /*
     * The return result is used to in server creation mac addresses step (wizard)
     */
    public function executeGenerateUnused()
    {
        $criteria = new Criteria();

        // get SESSION macs
        $session_macs = $this->getUser()->getAttribute('macs_in_wizard', array());       

        $criteria->add(EtvaMacPeer::ID, $session_macs, Criteria::NOT_IN);
        $criteria->add(EtvaMacPeer::IN_USE, 0);

        $etva_mac = EtvaMacPeer::doSelectOne($criteria);
        if(!$etva_mac) return false;

        $mac = $etva_mac->getId();


        // add the current mac at the beginning of the array
        array_unshift($session_macs, $mac);

        // store the new mac back into the session
        $this->getUser()->setAttribute('macs_in_wizard', $session_macs);
        return $etva_mac->toArray();

    }

   /**
   * Returns pre-formated data for Extjs grid with one unused mac address
   *
   * Returns info of one single unused mac
   *
   * @return array json array(mac address info))
   */
    /*
     * The return result is used to in server creation mac addresses step (wizard)
     */
    public function executeJsonGetUnused()
    {
        $mac = $this->executeGenerateUnused();

        if($mac === false){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaMacPeer::_ERR_NOMACS_);
            $info = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $result = json_encode($mac);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);


    }

   /**
   * Creates a mac pool and store it in DB
   *
   * Request must be Ajax
   *
   * $request may contain the following keys:
   * - size: pool size
   * @return array json array('success'=>true)
   */
    public function executeJsonGeneratePool(sfWebRequest $request)
    {


        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $macs = $this->generateMacPool($request->getParameter('size'),$request->getParameter('octects'));

        if(!$macs){
            $result = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>'No MACS generated!','info'=>'No MACS generated!');
            $return = $this->setJsonError($result);
            return  $this->renderText($return);
        }
        
        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

    /**
     * Used to generate random macs
     * @return
     *  1 if successfully generated all pool size elements
     *  0 if not
     */
    protected function generateMacPool($pool_size,$octects)
    {
        $octects = json_decode($octects,true);
        $oct4 = hexdec($octects['oct4']);
        $oct5 = hexdec($octects['oct5']);
        $oct6 = hexdec($octects['oct6']);

        $rand4 = $oct4 ? 0 : 1;
        $rand5 = $oct5 ? 0 : 1;
        $rand6 = $oct6 ? 0 : 1;    

        $macs = array();        
        for($i=0;$i<$pool_size;$i++){
            
            $repeated = 1;
            $tries = 0;

            while($repeated && ($rand4 || $rand5 || $rand6)){

                $oct4 = $rand4 ? mt_rand(1,127) : $oct4;
                $oct5 = $rand5 ? mt_rand(1,255) : $oct5;
                $oct6 = $rand6 ? mt_rand(1,255) : $oct6;

                $rmac = join(":",array(

                    sprintf('%02x',sfConfig::get('app_mac_default_first_octect')),
                    sprintf('%02x',sfConfig::get('app_mac_default_second_octect')),
                    sprintf('%02x',sfConfig::get('app_mac_default_third_octect')),
                    sprintf('%02x',$oct4),
                    sprintf('%02x',$oct5),
                    sprintf('%02x',$oct6)
                ));

                $etva_mac = EtvaMacPeer::retrieveByMac($rmac);
                if(!$etva_mac){
                    $repeated = 0;                  
                    $etva_mac = new EtvaMac();
                    $etva_mac->setMac($rmac);
                    $etva_mac->save();


                }
                $tries++;
                if($tries>$pool_size) return 0;

            }
            
        }

        return 1;

    }


    /**
     * Returns pre-formated data for Extjs grid with mac information
     *
     * Request must be Ajax
     *
     * $request may contain the following keys:
     * - query: json array (field name => value)
     * @return array json array('total'=>num elems, 'data'=>array(mac))
     */
    public function executeJsonGridAll(sfWebRequest $request)
    {

        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');
        $macs = array();

        $query = ($this->getRequestParameter('query'))? json_decode($this->getRequestParameter('query'),true) : array();


        $criteria = new Criteria();

        foreach($query as $key=>$val){

            $column = EtvaMacPeer::translateFieldName(sfInflector::camelize($key), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
            $criteria->add($column, $val);
        }

        $this->etva_mac_list = EtvaMacPeer::doSelect($criteria);

        foreach ($this->etva_mac_list as $etva_mac)
        {
            $macs[] = $etva_mac->toArray();
        }


        $final = array(
                    'total' =>   count($macs),
                    'data'  => $macs
        );

        $result = json_encode($final);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

    /**
     * Returns pre-formated data for Extjs grid with mac information
     *
     * Request must be Ajax
     *
     * $request may contain the following keys:
     * - query: json array (field name => value)
     * - mac: string mac
     * @return array json array('total'=>num elems, 'data'=>array(mac))
     */
    public function executeJsonGridQueryAll(sfWebRequest $request)
    {

        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');
        $macs = array();

        $query = ($this->getRequestParameter('query'))? json_decode($this->getRequestParameter('query'),true) : array();


        $criteria = new Criteria();

        foreach($query as $key=>$val){

            $column = EtvaMacPeer::translateFieldName(sfInflector::camelize($key), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
            $criteria->add($column, $val);
        }

        // filter by mac
        if( $this->getRequestParameter('mac') ){
            $mac = $this->getRequestParameter('mac');
            $newCriterion = $criteria->getNewCriterion(EtvaMacPeer::MAC,$mac.'%',Criteria::LIKE);
            $criteria->add($newCriterion);
        }


        $mac_list = EtvaMacPeer::doSelect($criteria);

        if(!$mac_list){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaMacPeer::_ERR_NOMACS_);
            $info = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }


        foreach ($mac_list as $etva_mac)
        {
            $macs[] = $etva_mac->toArray();
        }


        $final = array(
                    'total' =>   count($macs),
                    'data'  => $macs
        );

        $result = json_encode($final);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
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

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;
    }
}
