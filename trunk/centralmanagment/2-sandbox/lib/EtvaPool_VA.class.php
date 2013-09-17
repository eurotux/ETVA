<?php
/*
 * class to perform storage pool operations with VA
 */

class EtvaPool_VA
{
    private $etva_pool; // EtvaPool object

    const POOL_CREATE = 'create_storage_pool';
    const POOL_DESTROY = 'destroy_storage_pool';
    const POOL_RELOAD = 'reload_storage_pool';

    public function EtvaPool_VA(EtvaPool $etva_pool = null)
    {
        if($etva_pool) $this->etva_pool = $etva_pool;
        else $this->etva_pool = new EtvaPool();
    }

    /*
     * send create pool
     */
    public function send_create(EtvaNode $etva_node, $pool_data)
    {
        $method = self::POOL_CREATE;
        
        $etva_pool = $this->etva_pool;
        $etva_pool->fromArray($pool_data,BasePeer::TYPE_FIELDNAME);

        $etva_pool->setUuid(EtvaPoolPeer::generateUUID());

        $params = $etva_pool->_VA();

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method, $params);
        return $result;
    }

    /*
     * send destroy pool
     */
    public function send_destroy(EtvaNode $etva_node)
    {
        $method = self::POOL_DESTROY;
        
        $etva_pool = $this->etva_pool;

        //$params = array( 'name'=>$etva_pool->getName(), 'uuid'=>$etva_pool->getUuid() );
        $params = array( 'name'=>$etva_pool->getName() );

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method,$params);
        return $result;
    }

    /*
     * send reload pool
     */
    public function send_reload(EtvaNode $etva_node)
    {
        $method = self::POOL_RELOAD;
        
        $etva_pool = $this->etva_pool;
        $params = $etva_pool->_VA();

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method, $params);
        return $result;
    }

    /*
     * process response
     */
    public function processResponse($etva_node,$response, $method, $params)
    {
        $etva_pool = $this->etva_pool;
        $pool_name = $etva_pool->getName();
       
        switch($method){
            case self::POOL_CREATE :
                                $msg_ok_type = EtvaPoolPeer::_OK_CREATE_;
                                $msg_err_type = EtvaPoolPeer::_ERR_CREATE_;
                                break;
            case self::POOL_DESTROY :
                                $msg_ok_type = EtvaPoolPeer::_OK_REMOVE_;
                                $msg_err_type = EtvaPoolPeer::_ERR_REMOVE_;
                                break;
            case self::POOL_RELOAD :
                                $msg_ok_type = EtvaPoolPeer::_OK_RELOAD_;
                                $msg_err_type = EtvaPoolPeer::_ERR_RELOAD_;
                                break;
        }
                        
        if(!$response['success'])
        {
            $result = $response;
            
            $message = Etva::getLogMessage(array('name'=>$pool_name,'info'=>$response['info']), $msg_err_type);

            $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$pool_name,'%info%'=>$response['info']));
            $result['error'] = $msg_i18n;

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;
        }
        
        if( $method == self::POOL_CREATE ){
            // save object
            $etva_pool->save();

            // create relation to node
            $etva_nodepool = new EtvaNodePool();
            $etva_nodepool->setNodeId($etva_node->getId());
            $etva_nodepool->setPoolId($etva_pool->getId());
            $etva_nodepool->save();
        }

        $pool_id = $etva_pool->getId();

        $etva_cluster = $etva_node->getEtvaCluster();

        $error_messages = array();
        if( $etva_pool->getShared() ){
            // send call to all nodes
            $bulk_responses = $etva_cluster->soapSend($method,$params,$etva_node);
            //error_log(print_r($bulk_responses,true));
            foreach($bulk_responses as $node_id =>$node_response)
            {
                $node = EtvaNodePeer::retrieveByPK($node_id);
                if($node_response['success'])
                {
                    if( ($method == self::POOL_CREATE) ||
                            ($method == self::POOL_RELOAD) ){

                        if( !EtvaNodePoolPeer::retrieveByPK($node_id,$pool_id) ){
                            // create relation to node
                            $etva_nodepool = new EtvaNodePool();
                            $etva_nodepool->setNodeId($node_id);
                            $etva_nodepool->setPoolId($pool_id);
                            $etva_nodepool->save();

                            $node->clearErrorMessage(self::POOL_CREATE);
                            if( $method == self::POOL_RELOAD ){
                                // clear error message for reload
                                $node->clearErrorMessage($method);
                            }
                        }
                    }
                } else {
                    $message = Etva::getLogMessage(array('name'=>$pool_name,'info'=>$node_response['info']), $msg_err_type );
                    sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                    $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$pool_name,'%info%'=>$node_response['info']));
                    array_push($error_messages,array( 'success'=>false, 'agent'=>$node_response['agent'], 'response'=>$msg_i18n ) );

                    // mark node with fail
                    $node->setErrorMessage($method,$msg_i18n);
                }
            }
        }

        if( $method == self::POOL_DESTROY ){
            // remove object
            $etva_pool->delete();
        }
        $response_decoded = (array) $response['response'];
        $returned_object = (array) $response_decoded['_obj_'];
                   
        $message = Etva::getLogMessage(array('name'=>$pool_name), $msg_ok_type);
        $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$pool_name));
        sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message)));

        $result = array('success'=>true, 'agent'=>$etva_node->getName(), 'response'=>$msg_i18n, 'errors'=>$error_messages);
        return $result;                      

    }
}

?>

