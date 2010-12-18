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
  public function executeViewer(sfWebRequest $request)
  {

  }

  public function executeJsonGridInfo(sfWebRequest $request)
  {
    $isAjax = $request->isXmlHttpRequest();

    if(!$isAjax) return $this->redirect('@homepage');
    $elements = array();
    $this->sfGuardUser = sfGuardUserPeer::retrieveByPk($request->getParameter('id'));

    $user_info = $this->sfGuardUser->toArray();
    
    // returns array of profiles
    $user_profiles = $this->sfGuardUser->getsfGuardUserProfiles();

    // Get first profile. We only have one profile per user
    $profiles = $user_profiles[0]->toArray();
    
    $elements[] = array_merge($user_info,$profiles);
       

    $final = array('total' =>   count($elements),'data'  => $elements);
    $result = json_encode($final);

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
    $this->pager = new sfPropelPager('sfGuardUser', $limit);
    $c = new Criteria();
    // $c->addSelectColumn(sfGuardUserPeer::ALGORITHM);
    $this->addSortCriteria($c);
    // $this->addServerCriteria($c);

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


// return $this->renderText('{"metaData":{"totalProperty":"totalCount","root":"results","id":"id","fields":[{"name":"id","type":"int"},{"name":"firstname"},{"name":"lastname"},{"name":"username"},{"name":"email"},{"name":"active"},{"name":"updateTime"}]},"totalCount":1,"results":[{"id":160,"class":"TutorAccount","active":"Yes","createTime":new Date(1240424045000),"email":"wilt@moore.com","firstname":"Wsda","lastname":"Moore","note":"ssdf","password":"tota","updateTime":new Date(1240559517000),"username":"wilt"}]}');




   

    $final = array(
      'total' =>   $this->pager->getNbResults(),
      'data'  => $elements
    );


   $result = $final;
   $result = json_encode($final);
 // $result = '{"metaData":{"totalProperty":"totalCount","root":"results","id":"id","fields":[{"name":"Username"},{"name":"IsActive"},{"name":"updateTime"}]},"totalCount":1,"results":[{"id":160,"class":"TutorAccount","active":"Yes","createTime":new Date(1240424045000),"email":"wilt@moore.com","Username":"Wsda","IsActive":"Moore","note":"ssdf","password":"tota","updateTime":new Date(1240559517000),"username":"wilt"}]}';

    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
   return $this->renderText($result);

  }

  protected function addSortCriteria($criteria)
  {
    if ($this->getRequestParameter('sort')=='') return;

    $column = sfGuardUserPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

    if ('asc' == strtolower($this->getRequestParameter('dir')))
      $criteria->addAscendingOrderByColumn($column);
    else
      $criteria->addDescendingOrderByColumn($column);
  }
  
}
