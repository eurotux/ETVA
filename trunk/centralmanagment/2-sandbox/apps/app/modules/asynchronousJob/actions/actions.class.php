<?php

/**
 * asynchronousJob actions.
 *
 * @package    centralM
 * @subpackage asynchronousJob
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class asynchronousJobActions extends sfActions
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

  public function executeList(sfWebRequest $request)
  {
    $requestTasks_query = EtvaAsynchronousJobQuery::create();

    // list task that not end yet
    $requestTasks_query->add(EtvaAsynchronousJobPeer::STATUS,array(EtvaAsynchronousJob::ABORTED,EtvaAsynchronousJob::INVALID,EtvaAsynchronousJob::FINISHED),Criteria::NOT_IN)  // not this cases
                        ->addOr(EtvaAsynchronousJobPeer::STATUS,null, Criteria::ISNULL);

    // filter by interval of query
    if( $interval = $request->getParameter('interval') )
    {
        //$requestTasks_query->filterByUpdatedAt(array('min'=>$interval));
        $requestTasks_query->addOr(EtvaAsynchronousJobPeer::UPDATED_AT,$interval,Criteria::GREATER_EQUAL);
    }

    //error_log("EtvaAsynchronousJob List SQL Query = ".$requestTasks_query->toString());

    // run
    $requestTasks = $requestTasks_query
                            ->orderByUpdatedAt('desc')
                            ->orderById('desc')
                            ->find();

    $elements = array();
    foreach ($requestTasks as $task){
        $task_array = $task->toArray();
        $res_str = $task->getResult();

        $task_array['type'] = ($task->getStatus()) ? $task->getStatus() : EtvaAsynchronousJob::WAITING;

        if( $task->getStatus() == EtvaAsynchronousJob::FINISHED ){
            if( $res_str ){
                $resObj = (array)json_decode($res_str);
                $task_array['type'] = 'info';
                if( $resObj['success'] ){
                    $task_array['type'] = 'success';
                } else {
                    $task_array['type'] = 'error';
                }
            }
        }
        
        $elements[] = $task_array;
    }

    $result = array('success'=>true,
                'total'=> count($elements),
                'data'=> $elements
    );


    $return = json_encode($result);

    if(sfConfig::get('sf_environment') == 'soap') return $return;
    $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    
    return $this->renderText($return);
  }

  // NEW
  public function executeNew(sfWebRequest $request)
  {
    //$this->form = new EtvaAsynchronousJobForm();

    $msg_i18n = $this->getContext()->getI18N()->__('unknown error',array());
    $result = array('success'=>false, 'error'=>$msg_i18n,'agent'=>sfConfig::get('config_acronym') );

    if ($request->isMethod('post'))
    {
        $formdata = (array)json_decode($request->getParameter('asynchronousjob'));

        $formdata['user'] = $this->getUser()->getGuardUser()->getUsername();

        $asyncjob = new EtvaAsynchronousJob();
        $asyncjob->fromArray($formdata, BasePeer::TYPE_FIELDNAME);

        try {
            $asynctask = $asyncjob->getTask($this->dispatcher);
        } catch( Exception $e ){
            $result = array('success'=>false,'error'=>$e->getMessage(),'agent'=>sfConfig::get('config_acronym'));
        }
        if( $asynctask ){

            // calc dependencies
            if( $depends = $asyncjob->calcDepends($formdata['depends'],$this->dispatcher) )
            {
                $asyncjob->setDepends( $depends );
            }

            // save
            $asyncjob->save();

            $taskdesc = $asynctask->getBriefDescription() ? $asynctask->getBriefDescription() : $asyncjob->getTasknamespace().":".$asyncjob->getTaskname();
            $taskdesc_i18n = $this->getContext()->getI18N()->__($taskdesc,array());
            $msg_i18n = $this->getContext()->getI18N()->__('Task \'%task%\' created successfully.',array('%task%'=>$taskdesc_i18n));

            //error_log("executeNew Task '$taskdesc' deps=".$asyncjob->getDepends(). " formdata=".$formdata['depends']);

            $result = array('success'=>true, 'response'=>$msg_i18n,'agent'=>sfConfig::get('config_acronym'),
                                'asynchronousjob'=> $asyncjob->toArray() );
        } else {
            $msg_i18n = $this->getContext()->getI18N()->__('Asynchronous job with invalid task!',array());
            $result = array('success'=>false, 'error'=>$msg_i18n,'agent'=>sfConfig::get('config_acronym') );
        }
    }

    return $this->returnResponse($result);
  }

  // Abort
  public function executeAbort(sfWebRequest $request)
  {
    $id = $request->getParameter('id');

    $result = array('success'=>false, 'error'=>'unknown error','agent'=>sfConfig::get('config_acronym') );

    if( !$etva_asyncjob = EtvaAsynchronousJobPeer::retrieveByPk($id) )
    {
        $result = array('success'=>false, 'error'=>'Invalid asynchronous job!','agent'=>sfConfig::get('config_acronym'));
    } else {

        // abort task
        $etva_asyncjob->abort();

        $msg_i18n = $this->getContext()->getI18N()->__('Task aborted successfully.',array());
        $result = array('success'=>true,'agent'=>sfConfig::get('config_acronym'),
                            'response'=>$msg_i18n );
    }

    return $this->returnResponse($result);
  }

  // GET
  public function executeGet(sfWebRequest $request)
  {
    $id = $request->getParameter('id');

    $result = array('success'=>false, 'error'=>'unknown error','agent'=>sfConfig::get('config_acronym') );

    if( $etva_asyncjob = EtvaAsynchronousJobPeer::retrieveByPk($id) )
    {
        // get task
        $asyncjob_arr = $etva_asyncjob->toArray();

        $result = array('success'=>true, 'response'=>"Ok",'agent'=>sfConfig::get('config_acronym'),
                                'asynchronousjob'=> $asyncjob_arr );
    } else {
        // invalid id
        $result = array('success'=>false, 'error'=>'Invalid asynchronous job!','agent'=>sfConfig::get('config_acronym'));
    }

    return $this->returnResponse($result);
  }

  /**
   * Used to return errors messages
   *
   * @param string $info error message
   * @param int $statusCode HTTP STATUS CODE
   * @return array json array
   */
  protected function setJsonError($info,$statusCode = 400)
  {
    if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
    $this->getContext()->getResponse()->setStatusCode($statusCode);
    $error = json_encode($info);
    $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    return $error;
  }

  protected function returnResponse($result)
  {
    $return = json_encode($result);

    if($result['success'])
    {
        $return = json_encode($result);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);
    } else {

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

        $return = $this->setJsonError($result);
        return  $this->renderText($return);
    }
  }
}
