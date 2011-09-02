<?php

class EtvaServer extends BaseEtvaServer
{
    const NAME_MAP = 'name';
    const MEMORY_MAP = 'memory';
    const VCPU_MAP = 'vcpu';
    const VNC_PORT_MAP = 'vnc_port';
    const VNC_KEYMAP_MAP = 'vnc_keymap';
    const VM_STATE_MAP = 'state';
    const DISKS_MAP = 'Disks';
    const NETWORKS_MAP = 'Network';

    /*
     * update some object data from VA response
     */
    public function initData($arr)
	{
        if(array_key_exists(self::NAME_MAP, $arr)) $this->setName($arr[self::NAME_MAP]);

        if(array_key_exists(self::MEMORY_MAP, $arr)){
            $memory = Etva::Byte_to_MBconvert($arr[self::MEMORY_MAP]);
            $this->setMem($memory);
        }

        if(array_key_exists(self::VCPU_MAP, $arr)) $this->setCpus($arr[self::VCPU_MAP]);
        if(array_key_exists(self::VNC_PORT_MAP, $arr)) $this->setVncPort($arr[self::VNC_PORT_MAP]);
        if(array_key_exists(self::VNC_KEYMAP_MAP, $arr)) $this->setVncKeymap($arr[self::VNC_KEYMAP_MAP]);
        if(array_key_exists(self::VM_STATE_MAP, $arr)) $this->setVmState($arr[self::VM_STATE_MAP]);
        if(array_key_exists(self::DISKS_MAP, $arr)) $this->setDisks($arr[self::DISKS_MAP]);
        if(array_key_exists(self::NETWORKS_MAP, $arr)) $this->setNetworks($arr[self::NETWORKS_MAP]);
        
	}


    public function setVncPort($port = null)
    {

        if($port) $vncPort = EtvaVncPortPeer::retrieveByPK($port);     

        if(!$vncPort) $vncPort = EtvaVncPortPeer::getByServer($this->getId());

        if(!$vncPort) $vncPort = EtvaVncPortPeer::getUnusedPort();                                
        
        $vncPort->setServerId($this->getId());
        $vncPort->setEtvaServer($this);
        $vncPort->setInuse(1);
        
        return $vncPort;

    }
    
    public function getVncPort()
    {
        $vncPort = null;
        if($this->getId()) $vncPort = EtvaVncPortPeer::getByServer($this->getId());

        if(!$vncPort) $vncPort = EtvaVncPortPeer::getUnusedPort();

        return $vncPort->getId();        
    }




    /*
     * set cpus info. vcps and cpuset
     */
    private function setCpus($vpcus)
    {
        $cpu_set = array();
        for($i=0;$i<$vpcus;$i++){
            $cpu_set[$i] = $i;
        }
        $cpu_set_string = implode(',',$cpu_set);
        $this->setCpuset($cpu_set_string);
        $this->setVcpu($vpcus);
        

    }

    /*
     * used to process soap agent disks data
     */
    private function setDisks($data)
    {
        $sls = $this->getEtvaServerLogicals();
        foreach($sls as $sl){
            $sl_lv = $sl->getEtvaLogicalvolume();

            foreach ($data as $disktag=>$disk_info){
                $disk = (array) $disk_info;

                if($disk[EtvaLogicalvolume::PATH_MAP]==$sl_lv->getLvdevice()){
                    $sl->initData($disk);          
                    break;
                }
            }
        }        
    }

     /*
     * used to process soap agent networks data
     */
    private function setNetworks($data)
    {
        $networks = $this->getEtvaNetworks();
        foreach($networks as $network){            

            foreach ($data as $intfport=>$intfdata){
                $intf = (array) $intfdata;

                if($intf[EtvaNetwork::MACADDR_MAP]==$network->getMac()){
                    $network->initData($intf);
                    break;
                }
            }
        }
    }


  /*
   * format network data to be sent to Virtualization Agent
   */
  public function networks_VA()
  {

      $server_networks = $this->getEtvaNetworks();
      $networks = array();
      
      foreach($server_networks as $server_network) $networks[] = $server_network->network_VA();
                
      $networks_string = implode(';',$networks);
     
      return $networks_string;
  }

  /*
   * format disk data to be sent to Virtualization Agent
   */
  public function disks_VA()
  {

      $server_disks = $this->getEtvaServerLogicals();
      if(!$server_disks) $server_disks = array();
      $disks = array();

      foreach($server_disks as $server_disk){          
          $disks[] = $server_disk->serverdisk_VA();
      }

      $disks_string = implode(';',$disks);

      return $disks_string;
  }

  /*
   * returns server start representation for sending to VA
   */
  public function vmMigrate_VA($values)
  {
      $data = array();

  }

  /*
   * returns server representation for sending to VA
   */
  public function _VA()
  {
      $networks = $this->networks_VA();
      $disks = $this->disks_VA();



    //  $etva_lv = $this->getEtvaLogicalvolume();
     // $server_path = $etva_lv->getLvdevice();




      $server_VA = array('uuid'=>$this->getUuid(),
                         'name'=> $this->getName(),
                         'boot'=> $this->getBoot(),
                         'location'=> $this->getLocation(),
                         'cdrom'=> $this->getCdrom(),
                         'ram' => $this->getMem(),
                         'vcpu'=>$this->getVcpu(),
                         'state'=> $this->getState(),
                         'network'=>$networks,
                         'disk'=>$disks,
                         'vm_os'=> $this->getVmOs(),
                         'vnc_port'=>$this->getVncPort(),
                         'vm_type' => $this->getVmType(),
                         'vnc_listen'=>'any',
                         'vnc_keymap'=>$this->getVncKeymap()
                                    


      );




      return $server_VA;

  }


  public function getEtvaLogicalvolumes(Criteria $criteria = null)
  {
      if(!$criteria) $criteria = new Criteria();

      $criteria->add(EtvaServerLogicalPeer::SERVER_ID, $this->getId());
      $criteria->addJoin(EtvaLogicalvolumePeer::ID, EtvaServerLogicalPeer::LOGICALVOLUME_ID);
      return EtvaLogicalvolumePeer::doSelect($criteria);
  }

  /*
   * checks if all disks of server are not local
   */
  public function isAllSharedLogicalvolumes()
  {
      $lvs = $this->getEtvaLogicalvolumes();
      $all_shared = true;

      foreach($lvs as $lv){
          if($lv->getStorageType() == EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP){
              $all_shared = false;
              break;
          }
      }
      

      return $all_shared;      
  }


  /*
   * checks if has at least one shared lv
   */
  public function hasSharedLogicalvolume()
  {
      $lvs = $this->getEtvaLogicalvolumes();
      $has_shared = false;

      foreach($lvs as $lv){
          if($lv->getStorageType() != EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP){
              $has_shared = true;
              break;
          }
      }


      return $has_shared;
  }

       
  /*
   * Delete server with optionnal keep filesystem flag
   */
  public function deleteServer($keep_fs = true)
  {
      $server_node = $this->getEtvaNode();
      $con = Propel::getConnection(EtvaServerPeer::DATABASE_NAME, Propel::CONNECTION_WRITE);
      //$con->setAttribute(PDO::ATTR_TIMEOUT,10);

      try {
			$con->beginTransaction();
           
            $this->deleteNetworks($con);

            $this->deleteRRAFiles();

            $this->deleteDisks($keep_fs,$con);

            EtvaServerPeer::doDelete($this, $con);

            
            $server_node->updateMemFree();
            $server_node->save();            

            $con->commit();


		} catch (PropelException $e) {
			$con->rollBack();
			throw $e;
		}

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
                $etva_network->delete($con);
      }

  }

  public function deleteDisks($keep_fs = true, PropelPDO $con = null)
  {
      $server_logicalvols = $this->getEtvaServerLogicals();

      foreach ($server_logicalvols as $server_logicalvol){

          $logicalvol = $server_logicalvol->getEtvaLogicalvolume();

          if($keep_fs){
            
              $logicalvol->setInUse(0);          
              $logicalvol->save($con);
          }else{             
              $logicalvol->delete($con);
          }          
          $server_logicalvol->delete($con);          
      }      

  }

  public function deleteNetworkByMac($mac)
  {

      $criteria = new Criteria();
      $criteria->add(EtvaNetworkPeer::SERVER_ID,$this->getId());      

      $etva_network = EtvaNetworkPeer::retrieveByMac($mac,$criteria);
      $etva_network->delete();

  }


  public function deleteRRAFiles()
  {
      $node = $this->getEtvaNode();
      $node_uuid = $node->getUuid();

      $cpu_per_rra = new ServerCpuUsageRRA($node_uuid, $this->getUuid(),false);
      $cpu_per_rra->delete();

      $server_memory_per_rra = new ServerMemoryUsageRRA($node_uuid, $this->getUuid(),false);
      $server_memory_per_rra->delete();
            
      $server_memory = new ServerMemoryRRA($node_uuid, $this->getUuid(),false);
      $server_memory->delete();
  }

  /*
   * sets first logicalvolume as boot disk
   */
  public function setBootDisk()
  {
      $lv = EtvaServerLogicalPeer::getBootDisk($this->getId());
      $this->setEtvaLogicalvolume($lv);
  }


  public function setSoapTimeout($val) //seconds
  {
      $this->rcv_timeout = $val;
  }


  /*
   * send soap request to management agent
   */
  public function soapSend($method,$params=null)
  {
      
        $dispatcher = sfContext::getInstance()->getEventDispatcher();

        $addr = $this->getIP();
        $port = $this->getAgentPort();
        $proto = "tcp";
        $host = "" . $proto . "://" . $addr . ":" . $port;
        $agent = $this->getName();

        if(!$params) $params = array("nil"=>"true");

        $request_array = array('request'=>array(
                            'agent'=>$agent,
                            'host'=>$host,
                            'port'=>$port,
                            'method'=>$method,
                            'params'=>$params));

        $soap = new soapClient_($host,$port);
        if($this->rcv_timeout) $soap->set_rcv_timeout($this->rcv_timeout);
        $response = $soap->processSoap($method, $params);
        $response['agent'] = $this->getName();

        $response_array = array('response'=>$response);

        $all_params = array_merge($request_array,$response_array);

        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));
        
        return $response;

    }

    /*
     * gets array of fields names in DB
     */
    public function toDisplay()
    {

        $array_data = $this->toArray(BasePeer::TYPE_FIELDNAME);
        return $array_data;

    }


    /*
     * if vm state is not running MA cannot be running
     */
    public function setVmState($v)
	{
        if($v!='running') $this->setState(0);
        return parent::setVmState($v);
	} 
  

}
