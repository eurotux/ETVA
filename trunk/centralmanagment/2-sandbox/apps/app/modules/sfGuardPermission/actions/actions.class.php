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
     * Now lists the etva permissions
     * return json array response
     *
     */
    public function executeJsonList(sfWebRequest $request)
    {
      
      $c = new Criteria();    
      //$groups = sfGuardPermissionPeer::doSelect($c);
      $groups = EtvaPermissionPeer::doSelect($c);
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

    public function executeJsonHasPermission(sfWebRequest $request){
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $level = $request->getParameter('level');
        $id = $request->getParameter('id');

        if($level == 'server'){
            $super = $this->getUser()->isSuperAdmin();
            $dc = $this->getUser()->hasDatacenterCredential(array('admin', array('server'=> $id)));
            $srv = $this->getUser()->hasServerCredential(array('op',$id));
            $return = array('success'       => true,
                            'super'         => $super,
                            'datacenter'    => $dc,
                            'server'        => $srv
            );
        }elseif($level == 'node'){
            $super = $this->getUser()->isSuperAdmin();
            $dc = $this->getUser()->hasDatacenterCredential(array('admin', array('node'=> $id)));
            $return = array('success'       => true,
                            'super'         => $super,
                            'datacenter'    => $dc,
                            'node'          => $dc
            );
        }elseif($level == 'cluster'){
            $super = $this->getUser()->isSuperAdmin();
            $dc = $this->getUser()->hasDatacenterCredential(array('admin', array('cluster'=> $id)));
            $return = array('success'       => true,
                            'super'         => $super,
                            'datacenter'    => $dc
            );
        }else{
            $return = array('success' => false);
            $result=json_encode($return);
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($result);
        }

        $result=json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeJsonUpdateSpecific(sfWebRequest $request){
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $level = $request->getParameter('level');
        $id = $request->getParameter('id');
        $p_permtype = $request->getParameter('permtype');

        $p_users_data = $request->getParameter('etva_permission_user_list');
        $p_users = json_decode($p_users_data, true);

        $p_groups_data = $request->getParameter('etva_permission_group_list');
        $p_groups = json_decode($p_groups_data, true);

        // if has node level, find clusterid
        try{
            if($level == 'node'){
                $c = new Criteria();
                $c->add(EtvaNodePeer::ID, $id);
                $node = EtvaNodePeer::doSelectOne($c);
                $id = $node->getClusterId();
                $level = 'cluster';
            }

            if($level == 'server' or $level == 'cluster'){
                $this->changeUsersPermissions($id, $p_permtype, $level, $p_users);
                $this->changeGroupsPermissions($id, $p_permtype, $level, $p_groups);
                $this->cleanEmptyPermissions($id, $p_permtype, $level, $p_groups);
            }else{
                $info = array('success'=>false,'error'=>'Wrong parameters');
                $error = $this->setJsonError($info);
                return $this->renderText($error);
            }
        }catch(Exception $e){
            $response = array('success' => false,
                          'error'   => 'Could not perform operation',
                          'agent'   =>'Central Management',
                          'info'    => 'Could not perform operation');
            $error = $this->setJsonError($response);
            return $this->renderText($error);
        }

        $msg_i18n = $this->getContext()->getI18N()->__('User/groups permissions edited successfully');

        $result = array('success'=>true,
                            'agent' =>  'Central Management',
                            'response'  => $msg_i18n 
            );
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }
    public function executeJsonAddUserPermissions(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $level = $request->getParameter('level');
        $user_id = $request->getParameter('user_id');
        $p_permtype = $request->getParameter('permtype');

        $p_list_data = $request->getParameter('etva_permission_list');
        $p_list = json_decode($p_list_data, true);

        // if has node level, find clusterid
        try{
            $this->addUserPermissions($user_id, $p_permtype, $level, $p_list);

        }catch(Exception $e){
            $response = array('success' => false,
                          'error'   => 'Could not perform operation',
                          'agent'   =>'Central Management',
                          'info'    => 'Could not perform operation');
            return $response;
        }

        $msg_i18n = $this->getContext()->getI18N()->__('User permissions added successfully');

        $result = array('success'=>true,
                            'agent' =>  'Central Management',
                            'response'  => $msg_i18n 
            );
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }
    public function executeJsonUserPermissions(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $user_id = $request->getParameter('user_id');

        $users_cluster_perms_arr = array();
        $users_cluster_perms = EtvaPermissionClusterQuery::create()
                                ->useEtvaPermissionQuery()
                                    ->useEtvaPermissionUserQuery()
                                        ->filterByUserId($user_id)
                                    ->endUse()
                                ->endUse()
                                ->find();
        foreach( $users_cluster_perms as $uperm ){
            array_push($users_cluster_perms_arr, $uperm->getClusterId());
        }

        $users_server_perms_arr = array();
        $users_server_perms = EtvaPermissionServerQuery::create()
                                ->useEtvaPermissionQuery()
                                    ->useEtvaPermissionUserQuery()
                                        ->filterByUserId($user_id)
                                    ->endUse()
                                ->endUse()
                                ->find();
        foreach( $users_server_perms as $uperm ){
            array_push($users_server_perms_arr, $uperm->getServerId());
        }

        $result = array('success'=>true,
                            'data'=>array( 'user_cluster_permissions'=>$users_cluster_perms_arr,
                                            'user_server_permissions'=>$users_server_perms_arr ),
            );
        $return = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);
    }
    private function addGroupPermission($group_id, $p_permtype, $level, $id)
    {
        // get permission for the server/cluster and type
        if($level == 'cluster'){
            $perm = EtvaPermissionQuery::create()
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionClusterQuery()
                    ->filterByClusterId($id)
                ->endUse()
                ->findOne();
        }elseif($level == 'server'){
            $perm = EtvaPermissionQuery::create()
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionServerQuery()
                    ->filterByServerId($id)
                ->endUse()
                ->findOne();
        }

        // check if permission already exist
        if(!$perm){
            //create a new permission
            $perm = new EtvaPermission();
            $perm->setDescription('auto_generated');
            $perm->setPermType($p_permtype);
            $perm->setName('auto_'.$level.'_'.$id);
            $perm->save();

            if($level == 'cluster'){

                //associate new permission to datacenter
                $perm_dc = new EtvaPermissionCluster();
                $perm_dc->setClusterId($id);
                $perm_dc->setEtvaPermission($perm);
                $perm_dc->save();
            }elseif($level == 'server'){

                //associate new permission to server 
                $perm_srv = new EtvaPermissionServer();
                $perm_srv->setServerId($id);
                $perm_srv->setEtvaPermission($perm);
                $perm_srv->save();
            }
        }
        // add new group set
        $new_g = new EtvaPermissionGroup();
        $new_g->setGroupId($group_id);
        $new_g->setEtvaPermission($perm);
        $new_g->save();
    }
    private function addGroupPermissions($group_id, $p_permtype, $level, $p_list)
    {
        if($level == 'cluster'){
            $group_perms = EtvaPermissionGroupQuery::create()
                ->filterByGroupId($group_id)
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionClusterQuery()
                    ->endUse()
                ->endUse()
                ->find();
            $group_perms->delete();
        }elseif($level == 'server'){
            $group_perms = EtvaPermissionGroupQuery::create()
                ->filterByGroupId($group_id)
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionServerQuery()
                    ->endUse()
                ->endUse()
                ->find();
            $group_perms->delete();
        }

        foreach($p_list as $id){
            $this->addGroupPermission($group_id,$p_permtype,$level,$id);
        }
    }

    public function executeJsonAddGroupPermissions(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $level = $request->getParameter('level');
        $group_id = $request->getParameter('group_id');
        $p_permtype = $request->getParameter('permtype');

        $p_list_data = $request->getParameter('etva_permission_list');
        $p_list = json_decode($p_list_data, true);

        // if has node level, find clusterid
        try{
            $this->addGroupPermissions($group_id, $p_permtype, $level, $p_list);
        }catch(Exception $e){
            $response = array('success' => false,
                          'error'   => 'Could not perform operation',
                          'agent'   =>'Central Management',
                          'info'    => 'Could not perform operation');
            $error = $this->setJsonError($response);
            return $this->renderText($error);
        }

        $msg_i18n = $this->getContext()->getI18N()->__('Group permissions added successfully');

        $result = array('success'=>true,
                            'agent' =>  'Central Management',
                            'response'  => $msg_i18n 
            );
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }
    public function executeJsonGroupPermissions(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $group_id = $request->getParameter('group_id');

        $groups_cluster_perms_arr = array();
        $groups_cluster_perms = EtvaPermissionClusterQuery::create()
                                ->useEtvaPermissionQuery()
                                    ->useEtvaPermissionGroupQuery()
                                        ->filterByGroupId($group_id)
                                    ->endUse()
                                ->endUse()
                                ->find();
        foreach( $groups_cluster_perms as $gperm ){
            array_push($groups_cluster_perms_arr, $gperm->getClusterId());
        }

        $groups_server_perms_arr = array();
        $groups_server_perms = EtvaPermissionServerQuery::create()
                                ->useEtvaPermissionQuery()
                                    ->useEtvaPermissionGroupQuery()
                                        ->filterByGroupId($group_id)
                                    ->endUse()
                                ->endUse()
                                ->find();
        foreach( $groups_server_perms as $gperm ){
            array_push($groups_server_perms_arr, $gperm->getServerId());
        }

        $result = array('success'=>true,
                            'data'=>array( 'group_cluster_permissions'=>$groups_cluster_perms_arr,
                                            'group_server_permissions'=>$groups_server_perms_arr ),
            );
        $return = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);
    }

    /*
     * Cleans empty, autogenerated permissions
     */
    private function cleanEmptyPermissions($id, $p_permtype, $level, $p_groups){
        $perms = array();

        if($level == 'cluster'){
            $perms = EtvaPermissionQuery::create()
                ->filterByDescription('auto_generated')
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionClusterQuery('EtvaPermissionCluster','LEFT JOIN')
                    ->filterByClusterId($id)
                ->endUse()
                ->find();

        }elseif($level == 'server'){
            $perms = EtvaPermissionQuery::create()
                ->filterByDescription('auto_generated')
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionServerQuery('EtvaPermissionServer','LEFT JOIN')
                    ->filterByServerId($id)
                ->endUse()
                ->find();
        }        

        foreach($perms as $perm){
            if($perm->countEtvaPermissionUsers() == 0 && $perm->countEtvaPermissionGroups() == 0){
                $perm->delete();
            }
        }
    }

    private function changeGroupsPermissions($id, $p_permtype, $level, $p_groups){

        // remove groups from manually created permissions
        if($level == 'cluster'){
            $groups = EtvaPermissionGroupQuery::create()               
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionClusterQuery()
                        ->filterByClusterId($id)
                    ->endUse()
                ->endUse()
                ->find();
            $groups->delete();
        }elseif($level == 'server'){
            $groups = EtvaPermissionGroupQuery::create()               
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionServerQuery()
                        ->filterByServerId($id)
                    ->endUse()
                ->endUse()
                ->find();
            $groups->delete();
        }

        // get permission for the server/cluster and type
        if($level == 'cluster'){
            $perm = EtvaPermissionQuery::create()
                ->filterByDescription('auto_generated')
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionClusterQuery()
                    ->filterByClusterId($id)
                ->endUse()
                ->findOne();
        }elseif($level == 'server'){
            $perm = EtvaPermissionQuery::create()
                ->filterByDescription('auto_generated')
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionServerQuery()
                    ->filterByServerId($id)
                ->endUse()
                ->findOne();
        }

        // check if permission already exist
        if($perm){
            
            //remove old groups
            $groups = EtvaPermissionGroupQuery::create()
                ->useEtvaPermissionQuery()                
                    ->filterByPrimaryKey($perm->getId())                   
                ->endUse()
                ->find();
            $groups->delete();
        }else{

            //create a new permission
            $perm = new EtvaPermission();
            $perm->setDescription('auto_generated');
            $perm->setPermType($p_permtype);
            $perm->setName('auto_'.$level.'_'.$id);
            $perm->save();

            if($level == 'cluster'){

                //associate new permission to datacenter
                $perm_dc = new EtvaPermissionCluster();
                $perm_dc->setClusterId($id);
                $perm_dc->setEtvaPermission($perm);
                $perm_dc->save();
            }elseif($level == 'server'){

                //associate new permission to server 
                $perm_srv = new EtvaPermissionServer();
                $perm_srv->setServerId($id);
                $perm_srv->setEtvaPermission($perm);
                $perm_srv->save();
            }
        }
    

        // add new group set
        foreach($p_groups as $new_group_id){
            $new_g = new EtvaPermissionGroup();
            $new_g->setGroupId($new_group_id);
            $new_g->setEtvaPermission($perm);
            $new_g->save();
        }
    }

    private function changeUsersPermissions($id, $p_permtype, $level, $p_users){

        // remove users from manually created permissions
        if($level == 'cluster'){
            $users = EtvaPermissionUserQuery::create()               
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionClusterQuery()
                        ->filterByClusterId($id)
                    ->endUse()
                ->endUse()
                ->find();
            $users->delete();
        }elseif($level == 'server'){
            $users = EtvaPermissionUserQuery::create()               
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionServerQuery()
                        ->filterByServerId($id)
                    ->endUse()
                ->endUse()
                ->find();
            $users->delete();
        }

        // get permission for the server/cluster and type
        if($level == 'cluster'){
            $perm = EtvaPermissionQuery::create()
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionClusterQuery()
                    ->filterByClusterId($id)
                ->endUse()
                ->findOne();
        }elseif($level == 'server'){
            $perm = EtvaPermissionQuery::create()
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionServerQuery()
                    ->filterByServerId($id)
                ->endUse()
                ->findOne();
        }

        // check if permission already exist
        if($perm){
            
            //remove old users permissions
            $users = EtvaPermissionUserQuery::create()
                ->useEtvaPermissionQuery()                
                    ->filterByPrimaryKey($perm->getId())                   
                ->endUse()
                ->find();
            $users->delete();
        }else{

            //create a new permission
            $perm = new EtvaPermission();
            $perm->setDescription('auto_generated');
            $perm->setPermType($p_permtype);
            $perm->setName('auto_'.$level.'_'.$id);
            $perm->save();

            if($level == 'cluster'){

                //associate new permission to datacenter
                $perm_dc = new EtvaPermissionCluster();
                $perm_dc->setClusterId($id);
                $perm_dc->setEtvaPermission($perm);
                $perm_dc->save();
            }elseif($level == 'server'){

                //associate new permission to server 
                $perm_srv = new EtvaPermissionServer();
                $perm_srv->setServerId($id);
                $perm_srv->setEtvaPermission($perm);
                $perm_srv->save();
            }
        }
    

        // add new user set
        foreach($p_users as $new_user_id){
            $new_g = new EtvaPermissionUser();
            $new_g->setUserId($new_user_id);
            $new_g->setEtvaPermission($perm);
            $new_g->save();
        }
    }

    private function addUserPermission($user_id, $p_permtype, $level, $id)
    {
        // get permission for the server/cluster and type
        if($level == 'cluster'){
            $perm = EtvaPermissionQuery::create()
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionClusterQuery()
                    ->filterByClusterId($id)
                ->endUse()
                ->findOne();
        }elseif($level == 'server'){
            $perm = EtvaPermissionQuery::create()
                ->filterByPermType($p_permtype)
                ->useEtvaPermissionServerQuery()
                    ->filterByServerId($id)
                ->endUse()
                ->findOne();
        }

        // check if permission already exist
        if(!$perm){
            //create a new permission
            $perm = new EtvaPermission();
            $perm->setDescription('auto_generated');
            $perm->setPermType($p_permtype);
            $perm->setName('auto_'.$level.'_'.$id);
            $perm->save();

            if($level == 'cluster'){

                //associate new permission to datacenter
                $perm_dc = new EtvaPermissionCluster();
                $perm_dc->setClusterId($id);
                $perm_dc->setEtvaPermission($perm);
                $perm_dc->save();
            }elseif($level == 'server'){

                //associate new permission to server 
                $perm_srv = new EtvaPermissionServer();
                $perm_srv->setServerId($id);
                $perm_srv->setEtvaPermission($perm);
                $perm_srv->save();
            }
        }
        // add new user set
        $new_g = new EtvaPermissionUser();
        $new_g->setUserId($user_id);
        $new_g->setEtvaPermission($perm);
        $new_g->save();
    }

    private function addUserPermissions($user_id, $p_permtype, $level, $p_list)
    {
        if($level == 'cluster'){
            $user_perms = EtvaPermissionUserQuery::create()
                ->filterByUserId($user_id)
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionClusterQuery()
                    ->endUse()
                ->endUse()
                ->find();
            $user_perms->delete();
        }elseif($level == 'server'){
            $user_perms = EtvaPermissionUserQuery::create()
                ->filterByUserId($user_id)
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionServerQuery()
                    ->endUse()
                ->endUse()
                ->find();
            $user_perms->delete();
        }

        foreach($p_list as $id){
            $this->addUserPermission($user_id,$p_permtype,$level,$id);
        }
    }

    public function executeJsonUpdate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $id = $request->getParameter('id');
        $permission_id = $request->getParameter('permission_id');
        $etvaperm = EtvaPermissionPeer::retrieveByPK($id);        

        if(!$etvaperm) $etvaperm_form = new EtvaPermissionForm();
            else $etvaperm_form = new EtvaPermissionForm($etvaperm);

        $result = $this->processForm($request, $etvaperm_form);

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

    /*
     * Delets etva permissions
     */
    public function executeJsonDelete(sfWebRequest $request)
    {        
        $request->checkCSRFProtection();

        $id = $request->getParameter('id');        


        if(!$sf_permission = EtvaPermissionPeer::retrieveByPK($id)){
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaPermissionPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

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

        //if(!$isAjax) return $this->redirect('@homepage');

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

    /*
     * list permissions with matching groups
     */
    public function executeJsonGridGroupsClustersVms($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        //if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('EtvaPermission', $limit);

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

            foreach ($item->getEtvaPermissionGroups() as $pg)
            {
                $group = $pg->getsfGuardGroup();
                $elements[$i]['etva_permission_group_list'][] = $group->getId();

            }

            foreach ($item->getEtvaPermissionServers() as $pg)
            {
                $elements[$i]['etva_permission_server_list'][] = $pg->getserverId();
            }

            foreach ($item->getEtvaPermissionClusters() as $pg)
            {
                $elements[$i]['etva_permission_cluster_list'][] = $pg->getclusterId();
            }

            
            foreach ($item->getEtvaPermissionUsers() as $pg)
            {
                //$user =
                $elements[$i]['etva_permission_user_list'][] = $pg->getuserId();
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

    public function executeJsonPermsWithGroupsUsers($request){
        $isAjax = $request->isXmlHttpRequest();

//        if(!$isAjax) return $this->redirect('@homepage');

        $level = $request->getParameter('level');
        $id = $request->getParameter('id');
        $p_permtype = $request->getParameter('permtype');

        $u = new Criteria();
        $g = new Criteria();

        $elements = array();
        $elements['id'] = 1;

        // if has node level, find clusterid
        if($level == 'node'){
            $c = new Criteria();
            $c->add(EtvaNodePeer::ID, $id);
            $node = EtvaNodePeer::doSelectOne($c);
            $id = $node->getClusterId();
            $level = 'cluster';
        }

        if($level == 'server'){
            $users = EtvaPermissionUserQuery::create()
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionServerQuery()
                        ->filterByServerId($id)
                    ->endUse()
                ->endUse()
                ->find();

            foreach($users as $user){
                $elements['etva_permission_user_list'][] = $user->getUserId();
            }

            $groups = EtvaPermissionGroupQuery::create()
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionServerQuery()
                        ->filterByServerId($id)
                    ->endUse()
                ->endUse()
                ->find();

            foreach($groups as $group){
                $elements['etva_permission_group_list'][] = $group->getGroupId();
            }

        }elseif($level == 'cluster'){
            $users = EtvaPermissionUserQuery::create()
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionClusterQuery()
                        ->filterByClusterId($id)
                    ->endUse()
                ->endUse()
                ->find();

            foreach($users as $user){
                $elements['etva_permission_user_list'][] = $user->getUserId();
            }

    
            $groups = EtvaPermissionGroupQuery::create()
                ->useEtvaPermissionQuery()
                    ->filterByPermType($p_permtype)
                    ->useEtvaPermissionClusterQuery()
                        ->filterByClusterId($id)
                    ->endUse()
                ->endUse()
                ->find();

            foreach($groups as $group){
                $elements['etva_permission_group_list'][] = $group->getGroupId();
            }
        }else{
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $final = array(
                        'total' =>   1,
                        'data'  => $elements);

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeJsonGrid($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        //if(!$isAjax) return $this->redirect('@homepage');

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

//        print_r($params);
        
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
                              'agent'   =>sfConfig::get('config_acronym'),
                              'info'    => 'Could not perform operation');
                $error = $this->setJsonError($response);
                return $this->renderText($error);
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
                              'agent'   =>sfConfig::get('config_acronym'),
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
