<?php
require_once(sfConfig::get('sf_plugins_dir').'/sfGuardPlugin/modules/sfGuardUser/lib/BasesfGuardUserActions.class.php');

/**
 * sfGuardUser actions.
 *
 * @package    sfGuardPlugin
 * @subpackage sfGuardUser
 * @author     Fabien Potencier
 * @version    SVN: $Id: actions.class.php 12965 2008-11-13 06:02:38Z fabien $
 */
class sfGuardUserActions extends basesfGuardUserActions
{
    public function executeView(sfWebRequest $request)
    {

    }

    public function executeJsonDelete(sfWebRequest $request)
    {
        $request->checkCSRFProtection();

        $id = $request->getParameter('id');
        $cur_user = $this->getUser();                

        if($id==1 || $cur_user->getId() == $id)
        {
            $msg_i18n = $this->getContext()->getI18N()->__(sfGuardUserPeer::_ERR_CANNOT_DELETE_,array('%id%'=>$id));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);            
        }

        if(!$sf_user = sfGuardUserPeer::retrieveByPK($id)){
            $msg_i18n = $this->getContext()->getI18N()->__(sfGuardUserPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        $sf_user->delete();
        $result = array('success'=>true);
        $return = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);
    }
    
    public function executeJsonUpdate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');


        $id = $request->getParameter('id');

        $user = sfGuardUserPeer::retrieveByPK($id);
        if(!$user) $user_form = new sfGuardUserAdminForm();
        else $user_form = new sfGuardUserAdminForm($user);
                
        $result = $this->processForm($request, $user_form);


        if(!$result['success']){
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }



  public function executeJsonGridInfo(sfWebRequest $request)
  {
    $isAjax = $request->isXmlHttpRequest();

    if(!$isAjax) return $this->redirect('@homepage');
    
    $this->sfGuardUser = sfGuardUserPeer::retrieveByPk($request->getParameter('id'));

    $user_info = $this->sfGuardUser->toArray();

    // Get profile.
    $profile = $this->sfGuardUser->getProfile();
    $profile_info  = $profile->toArray();

    //user groups    
    $groups = $this->sfGuardUser->getGroups();
    $group_ids = array();
    foreach($groups as $group)
        $group_ids[] = $group->getId();

    //user permissions
    $permissions = $this->sfGuardUser->getPermissions();
    $permission_ids = array();
    foreach($permissions as $permission)
        $permission_ids[] = $permission->getId();
    
    
    $elements = array_merge($user_info,$profile_info,
                            array('sf_guard_user_group_list'=>$group_ids),
                            array('sf_guard_user_permission_list'=>$permission_ids));
    
    $final = array('success' => true, 'data'  => $elements);
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
    $this->pager = new sfPropelPager('sfGuardUser', $limit);
    $c = new Criteria();
    // $c->addSelectColumn(sfGuardUserPeer::ALGORITHM);
    $this->addSortCriteria($c);
    $this->addFilterCriteria($c);    

    $this->pager->setCriteria($c);
    $this->pager->setPage($page);

    $this->pager->setPeerMethod('doSelect');
    $this->pager->setPeerCountMethod('doCount');

    $this->pager->init();


    $elements = array();


    # Get data from Pager
    foreach($this->pager->getResults() as $item){
                $item->setAlgorithm(''); // prevent algorithm value from being passed
                $elements[] = $item->toArray();
              // $elements[] = $item;
    }
   

    $final = array(
      'total' =>   $this->pager->getNbResults(),
      'data'  => $elements
    );


   $result = $final;
   $result = json_encode($final);
 // $result = '{"metaData":{"totalProperty":"totalCount","root":"results","id":"id","fields":[{"name":"Username"},{"name":"IsActive"},{"name":"updateTime"}]},"totalCount":1,"results":[{"id":160,"class":"TutorAccount","active":"Yes","createTime":new Date(1240424045000),"email":"wilt@moore.com","Username":"Wsda","IsActive":"Moore","note":"ssdf","password":"tota","updateTime":new Date(1240559517000),"username":"wilt"}]}';

   $this->getResponse()->setHttpHeader('Content-type', 'application/json');
   return $this->renderText($result);

  }

    protected function addSortCriteria($criteria)
    {
        if ($this->getRequestParameter('sort')=='') return;

        $column = sfGuardUserPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

        if('asc' == strtolower($this->getRequestParameter('dir')))
            $criteria->addAscendingOrderByColumn($column);
        else
            $criteria->addDescendingOrderByColumn($column);

        $criteria->setIgnoreCase(true);
    }

    protected function addFilterCriteria($criteria)
    {
        $filters = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : null;
        if(!$filters) return;

        // GridFilters sends filters as an Array if not json encoded
        if(is_array($filters))
        {
            $encoded = false;
        }else
        {
            $encoded = true;
            $filters = json_decode($filters);
        }

        // loop through filters sent by client
        if (is_array($filters)) {
            for ($i=0;$i<count($filters);$i++){
                $filter = $filters[$i];

                // assign filter data (location depends if encoded or not)
                if($encoded){
                    $field = $filter->field;
                    $value = $filter->value;
                    $compare = isset($filter->comparison) ? $filter->comparison : null;
                    $filterType = $filter->type;
                }else{
                    $field = $filter['field'];
                    $value = $filter['data']['value'];
                    $compare = isset($filter['data']['comparison']) ? $filter['data']['comparison'] : null;
                    $filterType = $filter['data']['type'];
                }

                switch($filterType){
                    case 'string' :
                        $column = sfGuardUserPeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
                        $criteria->add($column, "%${value}%",Criteria::LIKE);
                        break;
                    default:
                        break;
                }
           }
        }
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
