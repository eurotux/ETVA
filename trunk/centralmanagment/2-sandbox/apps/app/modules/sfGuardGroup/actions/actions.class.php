<?php
require_once(sfConfig::get('sf_plugins_dir').'/sfGuardPlugin/modules/sfGuardGroup/lib/BasesfGuardGroupActions.class.php');

/**
 * sfGuardGroup actions.
 *
 * @package    sfGuardPlugin
 * @subpackage sfGuardGroup
 * @author     Fabien Potencier
 * @version    SVN: $Id: actions.class.php 12965 2008-11-13 06:02:38Z fabien $
 */
class sfGuardGroupActions extends BasesfGuardGroupActions
{

   public function executeView(sfWebRequest $request)
   {
      
        $this->sfGuardGroup_tableMap = sfGuardGroupPeer::getTableMap();
        $this->sfGuardGroup_form = new sfGuardGroupForm();
   }

   public function executeRefresh(sfWebRequest $request)
   {


   }

   public function executeJsonNothing(sfWebRequest $request){

    $result = array('success'=>true);
    $result = json_encode($result);

    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
    return $this->renderText('{"metaData":{"totalProperty":"totalCount","root":"results","id":"id","fields":[{"name":"id","type":"int"},{"name":"firstname"},{"name":"lastname"},{"name":"username"},{"name":"email"},{"name":"active"},{"name":"updateTime"}]},"totalCount":1,"results":[{"id":160,"class":"TutorAccount","active":"Yes","createTime":new Date(1240424045000),"email":"wilt@moore.com","firstname":"Wsda","lastname":"Moore","note":"ssdf","password":"tota","updateTime":new Date(1240559517000),"username":"wilt"}]}');
   }

    /*
     * Used in server grid to list permissions group available
     * return json array response
     *
     */
    public function executeJson(sfWebRequest $request)
    {
      

      $c = new Criteria();    
      $groups = sfGuardGroupPeer::doSelect($c);
      $elements = array();
      foreach ($groups as $group){
           $elements[] = $group->toArray();
      }

      $return = array(
      'data'  => $elements
        );

      $result=json_encode($return);
      return $this->renderText($result);
    }
   
  public function executeJsonCreate(sfWebRequest $request)
  {
     $isAjax = $request->isXmlHttpRequest();

     if(!$isAjax) return $this->redirect('@homepage');

     if(!$request->isMethod('post')){
         $info = array('success'=>false,'error'=>'Wrong parameters');
         $error = $this->setJsonError($info);
         return $this->renderText($error);
     }

     $this->form = new sfGuardGroupForm();

     $result = $this->processJsonForm($request, $this->form);

     if(!$result['success']){
         $error = $this->setJsonError($result);
         return $this->renderText($error);
     }

     $result = json_encode($result);

     $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).

     return $this->renderText($result);

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

    if(!$sfGuardGroup = sfGuardGroupPeer::retrieveByPk($request->getParameter('id'))){
        $error_msg = sprintf('Object sfGuardGroup does not exist (%s).', $request->getParameter('id'));
        $info = array('success'=>false,'error'=>$error_msg);
        $error = $this->setJsonError($info);
        return $this->renderText($error);
    }

    $sfGuardGroup->setByName($request->getParameter('field'), $request->getParameter('value'));
    $sfGuardGroup->save();

    $result = array('success'=>true);
    $result = json_encode($result);
    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
    return $this->renderText($result);

  }

  public function executeJsonDelete(sfWebRequest $request)
  {
    $isAjax = $request->isXmlHttpRequest();

    if(!$isAjax) return $this->redirect('@homepage');

    if(!$sfGuardGroup = sfGuardGroupPeer::retrieveByPk($request->getParameter('id'))){
        $error_msg = sprintf('Object sfGuardGroup does not exist (%s).', $request->getParameter('id'));
        $info = array('success'=>false,'error'=>$error_msg);
        $error = $this->setJsonError($info);
        return $this->renderText($error);
    }
    

    $sfGuardGroup->delete();

    $result = array('success'=>true);
    $result = json_encode($result);
    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
    return $this->renderText($result);
  }

  public function executeJsonGrid($request)
  {
    $isAjax = $request->isXmlHttpRequest();

    if(!$isAjax) return $this->redirect('@homepage');

    $limit = $this->getRequestParameter('limit', 10);
    $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

    // pager
    $this->pager = new sfPropelPager('sfGuardGroup', $limit);
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

    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
    return $this->renderText($result);

  }

  protected function addSortCriteria($criteria)
  {
    if ($this->getRequestParameter('sort')=='') return;

    $column = sfGuardGroupPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

    if ('asc' == strtolower($this->getRequestParameter('dir')))
      $criteria->addAscendingOrderByColumn($column);
    else
      $criteria->addDescendingOrderByColumn($column);
  }


  protected function processJsonForm(sfWebRequest $request, sfForm $form)
  {

    $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));

    if ($form->isValid())
    {
          $sfGuardGroup = $form->save();

          $result = array('success'=>true,'insert_id'=>$sfGuardGroup->getId());
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

  protected function setJsonError($info,$statusCode = 400){

      $this->getContext()->getResponse()->setStatusCode($statusCode);
      $error = json_encode($info);
      $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
      return $error;

  }
    
}
