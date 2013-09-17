<?php

/**
 * pool actions.
 *
 * @package    centralM
 * @subpackage pool
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class poolActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  public function executeIndex(sfWebRequest $request)
  {
    $this->forward('default', 'module');
  }
  */
  public function executeJsonList(sfWebRequest $request)
  {
    $elements = array();

    // get node id from cluster context
    $etva_node = EtvaNodePeer::getOrElectNode($request);

    if(!$etva_node){
        $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        
        $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);

        //notify system log
        $this->dispatcher->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $node_log,'priority'=>EtvaEventLogger::ERR)));

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

        // if is browser request return text renderer
        $error = $this->setJsonError($error);
        return $this->renderText($error);
    }

    
    $pools = EtvaPoolQuery::create()
                ->filterByClusterId($etva_node->getClusterId())
                ->find();

    foreach ($pools as $p){
        $elements[] = array( 'id'=>$p->getId(),
                                'uuid'=>$p->getUuid(),
                                'name'=>$p->getName(),
                                'type'=>$p->getPoolType(),
                                'source_host'=>$p->getSourceHost(),
                                'source_device'=>$p->getSourceDevice(),
                                'shared'=> ($p->getShared() ? true : false),
                                'capacity'=>$p->getCapacity()
                            );
    }

    $result = array('success'=>true,
                    'total'=> count($elements),
                    'data'=> $elements,
                    'agent'=>$etva_node->getName()
    );

    $return = json_encode($result);

    if(sfConfig::get('sf_environment') == 'soap') return $return;

    $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    return $this->renderText($return);
  }
  public function executeJsonCreate(sfWebRequest $request)
  {
    $msg_ok_type = EtvaPoolPeer::_OK_CREATE_;
    $msg_err_type = EtvaPoolPeer::_ERR_CREATE_;

    // get node id from cluster context
    $etva_node = EtvaNodePeer::getOrElectNode($request);

    if(!$etva_node){
        $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        
        $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);

        //notify system log
        $this->dispatcher->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $node_log,'priority'=>EtvaEventLogger::ERR)));

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

        // if is browser request return text renderer
        $error = $this->setJsonError($error);
        return $this->renderText($error);
    }
    $pool_data = json_decode($request->getParameter('pool'),true);
    $etva_pool = new EtvaPool();
    $etva_pool->setClusterId($etva_node->getClusterId());
    $etva_pool_va = new EtvaPool_VA($etva_pool);
    $response = $etva_pool_va->send_create($etva_node,$pool_data);

    if($response['success']){
        $return = json_encode($response);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);
    } else {
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);
        $return = $this->setJsonError($response);
        return  $this->renderText($return);
    }
  }
  public function executeJsonRemove(sfWebRequest $request)
  {

    $msg_ok_type = EtvaPoolPeer::_OK_REMOVE_;
    $msg_err_type = EtvaPoolPeer::_ERR_REMOVE_;

    // get node id from cluster context
    $etva_node = EtvaNodePeer::getOrElectNode($request);

    if(!$etva_node){
        $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        
        $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);

        //notify system log
        $this->dispatcher->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $node_log,'priority'=>EtvaEventLogger::ERR)));

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

        // if is browser request return text renderer
        $error = $this->setJsonError($error);
        return $this->renderText($error);
    }

    $id = $request->getParameter('id');
    $uuid = $request->getParameter('uuid');
    $name = $request->getParameter('name');

    if( $id ){
        $etva_pool = EtvaPoolPeer::retrieveByPK($id);
    } else if( $uuid ){
        $etva_pool = EtvaPoolPeer::retrieveByUUID($uuid);
    } else if( $name ){
        $etva_pool = EtvaPoolPeer::retrieveByName($name);
    }

    if( !$etva_pool ){
        $msg = Etva::getLogMessage(array('name'=>$name), EtvaPoolPeer::_ERR_NOTFOUND_);
        $msg_i18n = $this->getContext()->getI18N()->__(EtvaPoolPeer::_ERR_NOTFOUND_,array('%name%'=>$name));
        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);

        //notify system log            
        $message = Etva::getLogMessage(array('name'=>$name,'info'=>$msg), $msg_err_type);
        $this->dispatcher->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

        $error = $this->setJsonError($error);
        return $this->renderText($error);
    }

    $name = $etva_pool->getName();

    // do remove
    $etva_pool_va = new EtvaPool_VA($etva_pool);
    $response = $etva_pool_va->send_destroy($etva_node);

    if($response['success']){
        $return = json_encode($response);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);
    } else {
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);
        $return = $this->setJsonError($response);
        return  $this->renderText($return);
    }
  }
  public function executeJsonReload(sfWebRequest $request)
  {
    $msg_ok_type = EtvaPoolPeer::_OK_RELOAD_;
    $msg_err_type = EtvaPoolPeer::_ERR_RELOAD_;

    // get node id from cluster context
    $etva_node = EtvaNodePeer::getOrElectNode($request);

    if(!$etva_node){
        $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        
        $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);

        //notify system log
        $this->dispatcher->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $node_log,'priority'=>EtvaEventLogger::ERR)));

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

        // if is browser request return text renderer
        $error = $this->setJsonError($error);
        return $this->renderText($error);
    }

    $id = $request->getParameter('id');
    $uuid = $request->getParameter('uuid');
    $name = $request->getParameter('name');

    if( $id ){
        $etva_pool = EtvaPoolPeer::retrieveByPK($id);
    } else if( $uuid ){
        $etva_pool = EtvaPoolPeer::retrieveByUUID($uuid);
    } else if( $name ){
        $etva_pool = EtvaPoolPeer::retrieveByName($name);
    }

    if( !$etva_pool ){
        $msg = Etva::getLogMessage(array('name'=>$name), EtvaPoolPeer::_ERR_NOTFOUND_);
        $msg_i18n = $this->getContext()->getI18N()->__(EtvaPoolPeer::_ERR_NOTFOUND_,array('%name%'=>$name));
        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);

        //notify system log            
        $message = Etva::getLogMessage(array('name'=>$name,'info'=>$msg), $msg_err_type);
        $this->dispatcher->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

        $error = $this->setJsonError($error);
        return $this->renderText($error);
    }

    $name = $etva_pool->getName();

    // do reload
    $etva_pool_va = new EtvaPool_VA($etva_pool);
    $response = $etva_pool_va->send_reload($etva_node);

    if($response['success']){
        $return = json_encode($response);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);
    } else {
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);
        $return = $this->setJsonError($response);
        return  $this->renderText($return);
    }
  }
  public function executeJsonFindSource(sfWebRequest $request)
  {
    /*$msg_ok_type = EtvaPoolPeer::_OK_FIND_SOURCE_;
    $msg_err_type = EtvaPoolPeer::_ERR_FIND_SOURCE_;*/

    // get node id from cluster context
    $etva_node = EtvaNodePeer::getOrElectNode($request);

    if(!$etva_node){
        $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        
        $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);

        //notify system log
        $this->dispatcher->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $node_log,'priority'=>EtvaEventLogger::ERR)));

        // if is a CLI soap request return json encoded data
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

        // if is browser request return text renderer
        $error = $this->setJsonError($error);
        return $this->renderText($error);
    }

    $type = $request->getParameter('type');
    $source_host = $request->getParameter('source_host');

    // call find storage pool source
    $response = $etva_node->soapSend('find_storage_pool_source',array('type'=>$type, 'source_host'=>$source_host)); 

    if($response['success']){
        $return = json_encode($response);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);
    } else {
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);
        $return = $this->setJsonError($response);
        return  $this->renderText($return);
    }
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
