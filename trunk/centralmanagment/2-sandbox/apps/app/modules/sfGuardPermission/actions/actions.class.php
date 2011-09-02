<?php
require_once(sfConfig::get('sf_plugins_dir').'/sfGuardPlugin/modules/sfGuardPermission/lib/BasesfGuardPermissionActions.class.php');

/**
 * sfGuardPermission actions.
 *
 * @package    sfGuardPlugin
 * @subpackage sfGuardPermission
 * @author     Fabien Potencier
 * @version    SVN: $Id: actions.class.php 12965 2008-11-13 06:02:38Z fabien $
 */
class sfGuardPermissionActions extends BasesfGuardPermissionActions
{

    /*
     *
     * list all permissions
     * 
     * return json array response
     *
     */
    public function executeJsonList(sfWebRequest $request)
    {
      

      $c = new Criteria();    
      $groups = sfGuardPermissionPeer::doSelect($c);
      $elements = array();
      foreach ($groups as $group){
           $elements[] = $group->toArray();
      }

      $return = array(
      'data'  => $elements
        );

      $result=json_encode($return);
      $this->getResponse()->setHttpHeader('Content-type', 'application/json');
      return $this->renderText($result);
    }


    public function executeJsonUpdate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');


        $id = $request->getParameter('id');

        $permission = sfGuardPermissionPeer::retrieveByPK($id);
        if(!$permission) $permission_form = new sfGuardPermissionForm();
        else $permission_form = new sfGuardPermissionForm($permission);

        $result = $this->processForm($request, $permission_form);


        if(!$result['success']){
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }    

    
    /*
     * updates a specified field
     *
     */
    public function executeJsonUpdateField(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$request->isMethod('post') && !$request->isMethod('put')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        if(!$sf_permission = sfGuardPermissionPeer::retrieveByPk($request->getParameter('id'))){
            $msg_i18n = $this->getContext()->getI18N()->__(sfGuardPermissionPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            
            $info = array('success'=>false,'error'=>$msg_i18n);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $sf_permission->setByName($request->getParameter('field'), $request->getParameter('value'));
        $sf_permission->save();

        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

    public function executeJsonDelete(sfWebRequest $request)
    {        
        $request->checkCSRFProtection();

        $id = $request->getParameter('id');        

        if(!$sf_permission = sfGuardPermissionPeer::retrieveByPK($id)){
                $msg_i18n = $this->getContext()->getI18N()->__(sfGuardPermissionPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
                $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);
        }

        $sf_permission->delete();
        $result = array('success'=>true);
        $return = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);        
    }

    /*
     * list permissions with matching groups
     */
    public function executeJsonGridWithGroups($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('sfGuardPermission', $limit);

        $c = new Criteria();

        $this->addSortCriteria($c);


        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelect');
        $this->pager->setPeerCountMethod('doCount');

        $this->pager->init();


        $elements = array();
        $i = 0;
        # Get data from Pager
        foreach($this->pager->getResults() as $item){
            $elements[$i] = $item->toArray();

            foreach ($item->getsfGuardGroupPermissions() as $pg)
            {
                $group = $pg->getsfGuardGroup();
                $elements[$i]['sf_guard_group_permission_list'][] = $group->getId();
            }
            $i++;
        }

        $final = array(
                        'total' => $this->pager->getNbResults(),
                        'data'  => $elements);

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }
    
    public function executeJsonGrid($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('sfGuardPermission', $limit);
        $c = new Criteria();

        $this->addSortCriteria($c);
    

        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelect');
        $this->pager->setPeerCountMethod('doCount');

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

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        
        return $this->renderText($result);
    }

    protected function addSortCriteria($criteria)
    {
        if ($this->getRequestParameter('sort')=='') return;

        $column = sfGuardPermissionPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

        if ('asc' == strtolower($this->getRequestParameter('dir')))
            $criteria->addAscendingOrderByColumn($column);
        else
            $criteria->addDescendingOrderByColumn($column);
    }

    protected function processForm(sfWebRequest $request, sfForm $form)
    {
        $fieldSc = $form->getFormFieldSchema();
        $widget = $fieldSc->getWidget();
        $params = array();

        foreach($widget->getFields() as $key => $object){
            $data = $request->getParameter($key);
            $data_dec = json_decode($data);
            $params[$key] = is_array($data_dec) ? $data_dec : $data;
        }

        $form->bind($params);

        if($form->isValid())
        {
            try{
                $form->save();
            }catch(Exception $e){
                $response = array('success' => false,
                              'error'   => 'Could not perform operation',
                              'agent'   =>'ETVA',
                              'info'    => 'Could not perform operation');
                return $response;
            }

            return array('success'=>true);

        }
        else
        {
            $errors = array();
            foreach ($form->getFormattedErrors() as $error) $errors[] = $error;

            $error_msg = implode($errors);
            $info = implode('<br>',$errors);
            $response = array('success' => false,
                              'error'   => $error_msg,
                              'agent'   =>'ETVA',
                              'info'    => $info);
            return $response;
        }
    }

    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }
   
    
}
