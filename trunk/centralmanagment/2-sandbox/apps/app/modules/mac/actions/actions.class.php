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

        $criteria = new Criteria();

        // get SESSION macs 
        $session_macs = $this->getUser()->getAttribute('macs_in_wizard', array());
        
        $macs_in_use = array();
        foreach($session_macs as $index=>$item){            
            $macs_in_use[] = $item['Id'];
        }

        $criteria->add(EtvaMacPeer::ID, $macs_in_use, Criteria::NOT_IN);
        $criteria->add(EtvaMacPeer::IN_USE, 0);

        $etva_mac = EtvaMacPeer::doSelectOne($criteria);

        if(!$etva_mac){
            $info = array('success'=>false,'error'=>'No macs available in pool');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $mac = $etva_mac->toArray();


        // add the current mac at the beginning of the array
        array_unshift($session_macs, $mac);

        // store the new mac back into the session
        $this->getUser()->setAttribute('macs_in_wizard', $session_macs);

        $result = json_encode($mac);

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
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

        $macs = $this->generateMacPool($request->getParameter('size'));

        foreach($macs as $mac){
            $etva_mac = new EtvaMac();
            $etva_mac->setMac($mac);
            $etva_mac->save();

        }
        $result = array('success'=>true);
        $result = json_encode($result);

        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).

        return $this->renderText($result);

    }

    /**
     * Used to generate random macs
     * @return array
     */
    protected function generateMacPool($pool_size)
    {

        $macs = array();

        for($i=0;$i<$pool_size;$i++){
            $rmac = join(":",array(
                    sprintf('%02x',0x00),
                    sprintf('%02x',0x16),
                    sprintf('%02x',0x3e),
                    sprintf('%02x',mt_rand(1,127)),
                    sprintf('%02x',mt_rand(1,255)),
                    sprintf('%02x',mt_rand(1,255))
                ));
            $macs[] = $rmac;
        }



        return $macs;

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

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
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

}
