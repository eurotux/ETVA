<?php

/**
 * user actions.
 *
 * @package    centralM
 * @subpackage user
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class userActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeIndex(sfWebRequest $request)
  {
    #$this->forward('default', 'module');
  }

  public function executeView(sfWebRequest $request)
  {

  }
  private function initModules()
  {
    $this->modules = array( );
    $this->modulesConf = array( );

    /*$servers_with_service = EtvaServerQuery::create()
                             ->useEtvaServiceQuery("EtvaService","INNER JOIN")
                             ->endUse()
                             ->orderByAgentTmpl()
                             ->find();*/
    $service_with_server = EtvaServiceQuery::create()
                             ->useEtvaServerQuery("EtvaServer","INNER JOIN")
                             ->endUse()
                             ->find();

    //foreach($servers_with_service as $server){
    foreach($service_with_server as $service){
        $server = $service->getEtvaServer();
        $agent_tmpl = $server->getAgentTmpl();
        $s_name_tmpl = $service->getNameTmpl();

        if( !$this->modulesConf["$agent_tmpl"] ){
            $this->modulesConf["$agent_tmpl"] = array();

            $header = $agent_tmpl;
            $dataIndex = "is".$agent_tmpl;
            array_push($this->modules,array('header'=>$header,'dataIndex'=>$dataIndex,'id'=>$agent_tmpl));
        }
        array_push($this->modulesConf["$agent_tmpl"]["$s_name_tmpl"] = array('server_id'=>$server->getId(),'agent_port'=>$server->getAgentPort(),'ip'=>$server->getIp(),'state'=>$server->getState(),'vm_state'=>$server->getVmState(), 'service_id'=>$service->getId(),'service_params'=>$service->getParams()));
    }
  }
  public function executeList(sfWebRequest $request)
  {
    // get default Group ID to mark as cannot delete
    $this->defaultGroupID = sfGuardGroupPeer::getDefaultGroup()->getId();

    $this->initModules();
  }
  public function executeJsonGrid(sfWebRequest $request)
  {
    $this->forward('sfGuardUser','jsonGrid');
  }
  public function executeJsonDelete(sfWebRequest $request)
  {
    $this->forward('sfGuardUser','jsonDelete');
  }
  public function executeJsonUpdate(sfWebRequest $request)
  {
    $this->forward('sfGuardUser','jsonUpdate');
  }
  public function executeView_UserCreationWizard(sfWebRequest $request)
  {
    $this->initModules();
  }
}
