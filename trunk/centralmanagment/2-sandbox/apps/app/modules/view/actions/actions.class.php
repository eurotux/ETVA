<?php

/**
 * view actions.
 *
 * @package    centralM
 * @subpackage view
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z fabien $
 */
class viewActions extends sfActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $this->node_list = EtvaNodePeer::doSelect(new Criteria());
    $this->node_form = new EtvaNodeForm();

    $action = $this->getController()->getAction('node','bulkUpdateState');
    $result = $action->executeBulkUpdateState($this->request);


  }

  public function executeVncviewer(sfWebRequest $request)
  {
      $etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('id'));
      $etva_node = $etva_server->getEtvaNode();
      

      $this->forward404Unless($etva_server);
      $user = $this->getUser();
      $tokens = $user->getGuardUser()->getEtvaVncTokens();

      $this->username = $tokens[0]->getUsername();
      $this->token = $tokens[0]->getToken();

      $proxyhost1 = $request->getHost();
      $proxyhost1 = split(':',$proxyhost1);
      $proxyhost1 = $proxyhost1[0];

      $this->proxyhost1 = $proxyhost1;
      $this->host = $etva_node->getIp();
      $this->port = $etva_server->getVncPort();
     

  }

  public function executeView(sfWebRequest $request)
  {
      $this->node_form = new EtvaNodeForm();
      
      $this->node_tableMap = EtvaNodePeer::getTableMap();
      
      // parent extjs container id
      $this->containerId = $request->getParameter('containerId');

      
            

            
      // $this->request->setParameter('id', 1);
      
     // $this->request->setParameter('method', 'list_vms');
    //  $this->dispatcher->notify(new sfEvent($this, 'updatedsoap'));
    // $this->forward('node','soap');


// WORKING!!!
// CAN BE USED TO PERFORM EXTRA STUFF
    // $action = sfContext::getInstance()->getController()->getAction('node','soap');
    // $action = $this->getController()->getAction('node','soap');
    // $result = $action->executeSoap($this->request,1);
// END WORKING

    // return sfView::SUCCESS;
    //sfContext::getInstance()->getController()->dispatch('somemodule', 'someaction');

      
  }

  public function executeNetworks(sfWebRequest $request)
  {
      $this->network_form = new EtvaNetworkForm();
      $this->network_tableMap = EtvaNetworkPeer::getTableMap();
      
      $this->vlan_tableMap = EtvaVlanPeer::getTableMap();
    
  }

}
