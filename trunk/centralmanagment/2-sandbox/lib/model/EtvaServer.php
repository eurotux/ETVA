<?php

class EtvaServer extends BaseEtvaServer
{
       

  public function delete(PropelPDO $con = null)
  {

      $this->deleteNetworks();
      $this->deleteServices();
      $etva_logicalvol = $this->getEtvaLogicalvolume();
      $etva_logicalvol->setInUse(0);
      $etva_logicalvol->save();      

      parent::delete($con);

  }

  public function deleteServices()
  {
      $c = new Criteria();
      $c->add(EtvaServicePeer::SERVER_ID, $this->getId());
      EtvaServicePeer::doDelete($c);

  }

  public function retrieveByMac($mac){

      $criteria = new Criteria();
      $criteria->add(EtvaNetworkPeer::SERVER_ID,$this->getId());

      $etva_network = EtvaNetworkPeer::retrieveByMac($mac,$criteria);
      return $etva_network;
  }
  
  
  public function deleteNetworks(PropelPDO $con = null)
  {
      $etva_networks = $this->getEtvaNetworks();
      foreach($etva_networks as $etva_network){
                $etva_network->delete();
      }

  }

  public function deleteNetworkByMac($mac)
  {

      $criteria = new Criteria();
      $criteria->add(EtvaNetworkPeer::SERVER_ID,$this->getId());      

      $etva_network = EtvaNetworkPeer::retrieveByMac($mac,$criteria);
      $etva_network->delete();

  }

  /*
   * send soap request to management agent
   */
  public function soapSend($method,$params=null){


        $addr = $this->getIP();
        $port = $this->getAgentPort();
        $proto = "tcp";
        $host = "" . $proto . "://" . $addr . ":" . $port;

        if(!$params) $params = array("nil"=>"true");

        $soap = new soapClient($host,$port);

        $response = $soap->processSoap($method, $params);
        
        return $response;

    }




  

}
