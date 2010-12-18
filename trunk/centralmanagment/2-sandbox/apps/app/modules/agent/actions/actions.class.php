<?php

/**
 * agent actions.
 *
 * @package    centralM
 * @subpackage agent
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z fabien $
 */
class agentActions extends sfActions
{

    public function executeView(sfWebRequest $request){
        $this->tabId = $request->getParameter('tabId');
    }

  
  /**
   * Creates new Agent Object
   *
   * @param  sfWebRequest  The current context request
   *
   * @return json encode
   */
  public function executeJsonCreate(sfWebRequest $request)
  {
     $isAjax = $request->isXmlHttpRequest();

     if(!$isAjax) return $this->redirect('@homepage');

     if(!$request->isMethod('post')){
         $info = array('success'=>false,'error'=>'Wrong parameters');
         $error = $this->setJsonError($info);
         return $this->renderText($error);
     }
        
     $this->form = new EtvaAgentForm();
       
     $result = $this->processJsonForm($request, $this->form);

     if(!$result['success']){
         $error = $this->setJsonError($result);
         return $this->renderText($error);
     }
        
     $result = json_encode($result);

     $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
     //    sfConfig::set('sf_web_debug', false); // set to false for speed-up (done automatically for production-environment)
     return $this->renderText($result);      

  }


  public function executeSoapJsonCreate(sfWebRequest $request)
  {
 
    if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap'){
        $this->form = new EtvaAgentForm();
        $result = $this->processJsonForm($request, $this->form);
        return $result;
    }
  }



  public function executeJsonUpdate(sfWebRequest $request)
  {
    $isAjax = $request->isXmlHttpRequest();

    if(!$isAjax) return $this->redirect('@homepage');

    if(!$request->isMethod('post') && !$request->isMethod('put')){
         $info = array('success'=>false,'error'=>'Wrong parameters');
         $error = $this->setJsonError($info);
         return $this->renderText($error);
    }
        
    if(!$etva_agent = EtvaAgentPeer::retrieveByPk($request->getParameter('id'))){
        $error_msg = sprintf('Object etva_agent does not exist (%s).', $request->getParameter('id'));
        $info = array('success'=>false,'error'=>$error_msg);
        $error = $this->setJsonError($info);
        return $this->renderText($error);
    }

    $etva_agent->setByName($request->getParameter('field'), $request->getParameter('value'));
    $etva_agent->save();

    $result = array('success'=>true);
    $result = json_encode($result);
    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
    //    sfConfig::set('sf_web_debug', false); // set to false for speed-up (done automatically for production-environment)
    return $this->renderText($result);

  }

 

  public function executeJsonDelete(sfWebRequest $request)
  {
    $isAjax = $request->isXmlHttpRequest();

    if(!$isAjax) return $this->redirect('@homepage');
    
    if(!$etva_agent = EtvaAgentPeer::retrieveByPk($request->getParameter('id'))){
        $error_msg = sprintf('Object etva_agent does not exist (%s).', $request->getParameter('id'));
        $info = array('success'=>false,'error'=>$error_msg);
        $error = $this->setJsonError($info);
        return $this->renderText($error);
    }

    $etva_agent->delete();

    $result = array('success'=>true);
    $result = json_encode($result);
    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
    //    sfConfig::set('sf_web_debug', false); // set to false for speed-up (done automatically for production-environment)
    return $this->renderText($result);
  }



  public function executeJsonGrid($request)
  {
    $isAjax = $request->isXmlHttpRequest();

    if(!$isAjax) return $this->redirect('@homepage');

    $limit = $this->getRequestParameter('limit', 10);
    $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

    // pager
    $this->pager = new sfPropelPager('EtvaAgent', $limit);
    $c = new Criteria();

    $this->addSortCriteria($c);
    $this->addServerCriteria($c);
    
    $this->pager->setCriteria($c);
    $this->pager->setPage($page);

    $this->pager->setPeerMethod('doSelectJoinAll');
    $this->pager->setPeerCountMethod('doCountJoinAll');

    $this->pager->init();


    $elements = array();

    # Get data from Pager
    foreach($this->pager->getResults() as $item)
                $elements[] = $item->toArray();

    $final = array(
      'total' =>   $this->pager->getNbResults(),
      'data'  => $elements
    );

    $result = json_encode($final);

    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
    return $this->renderText($result);
      
  }


  protected function addSortCriteria($criteria)
  {
    if ($this->getRequestParameter('sort')=='') return;

    $column = EtvaAgentPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

    if ('asc' == strtolower($this->getRequestParameter('dir')))
      $criteria->addAscendingOrderByColumn($column);
    else
      $criteria->addDescendingOrderByColumn($column);
  }


  protected function addServerCriteria($criteria)
  {
        $serverID = $this->getRequestParameter("sid");
        $criteria->add(EtvaAgentPeer::SERVER_ID, $serverID);        
  }



  protected function processJsonForm(sfWebRequest $request, sfForm $form)
  {
    //  return "1";
    //return $form->getName()." ".$request->getParameter('etva_agent');
     // return $request->getFiles($form->getName());

    $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));

    if ($form->isValid())
    {
       
          $etva_agent = $form->save();

          $result = array('success'=>true,'insert_id'=>$etva_agent->getId());
          return $result;


    }
    else{
        
        $errors = array();
        foreach ($form->getErrorSchema() as $field => $error)
                        $errors[$field] = $error->getMessage();
          $result = array('success'=>false,'error'=>$errors);
          
          return $result;
    }


  }


   /*
   * SOAP
   */
    public function executeSoapInit(sfWebRequest $request)
    {


        if(SF_ENVIRONMENT == 'soap'){


            $services = $request->getParameter('services');
            $params = $request->getParameter('params');
            $uid = $request->getParameter('uid');
            
            $c = new Criteria();
            $c->add(EtvaAgentPeer::UID ,$uid);
            
            $etva_agent = EtvaAgentPeer::doSelectOne($c);

            if(!$etva_agent){

                $etva_server = EtvaServerPeer::retrieveByPK(1);
                $etva_agent = new EtvaAgent();
                $etva_agent->setName($params->name);
                $etva_agent->setUid($uid);
                $etva_agent->setEtvaServer($etva_server);
                $etva_agent->save();

                //$error_msg = sprintf('Object etva_agent does not exist (%s).', $request->getParameter('uid'));
                //$error = array('success'=>false,'error'=>$error_msg);

                
            }
            
            foreach($services as $service){

                $c = new Criteria();
                $c->add(EtvaServicePeer::NAME ,$service->name);
                $c->add(EtvaServicePeer::AGENT_ID ,$etva_agent->getId());

                $etva_service = EtvaServicePeer::doSelectOne($c);
                if(!$etva_service){

                    $etva_service = new EtvaService();
                    $etva_service->setName($service->name);
                    $etva_service->setEtvaAgent($etva_agent);

                }

                if(isset($service->description))
                    $etva_service->setDescription($service->description);

                if(isset($service->params)){

                    $params = $service->params;
                    $encoded_json = json_encode($params);

                    $etva_service->setParams($encoded_json);

                }


                $etva_service->save();



            }

            $result = array('success'=>true,'response'=>'1');

            return $result;


        }
    }

  protected function setJsonError($info,$statusCode = 400){

      $this->getContext()->getResponse()->setStatusCode($statusCode);
      $error = json_encode($info);
      $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
      return $error;

  }
  
}
