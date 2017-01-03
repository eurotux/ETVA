<?php

class EtvaNetwork extends BaseEtvaNetwork
{
    const MACADDR_MAP = 'macaddr';
    const TARGET_MAP = 'target';
    const _NETWORK_MAP_VA_   = 'name=%name%,macaddr=%mac%,model=%model%';

    /*
     * update some object data from VA response
     */
    public function initData($arr)
	{        

        if(array_key_exists(self::MACADDR_MAP, $arr)) $this->setMac($arr[self::MACADDR_MAP]);
        if(array_key_exists(self::TARGET_MAP, $arr)) $this->setTarget($arr[self::TARGET_MAP]);

	}



    /*
     * return string network representation for VA
     */
    public function network_VA()
    {
        $vlan = $this->getEtvaVlan();
        return strtr(self::_NETWORK_MAP_VA_, array(
                            '%name%' => $vlan->getName(),
                            '%mac%'  => $this->getMac(),
                            '%model%'  => $this->getIntfModel()
        ));
    
    }


    public function save(PropelPDO $con = null)
	{
        // store the backtrace
        $bt = debug_backtrace();

        // analyze backtrace to see if importing from fixtures
        $is_importing = false;
        foreach ($bt as $cf)
            if ($cf['function'] == 'loadData')
                $is_importing = true;
                               

        // check if import data from fixtures or if is a normal save action
        if(!$is_importing){
            
            $etva_mac = EtvaMacPeer::retrieveByMac($this->getMac());

            if(!$etva_mac ) return ;

            if($etva_mac->getInUse()) return;

            $etva_mac->setInUse(1);
            $etva_mac->save();
        }
                      
		$ret = parent::save($con);

		return $ret;
	}

    public function updateTarget($target){
        $this->setTarget($target);
        parent::save();
    }


    public function preDelete(PropelPDO $con = null)
    {
        $mac = EtvaMacPeer::retrieveByMac($this->getMac());
        $mac->setInUse(0);
        $mac->save();

        $mac_strip = str_replace(':','',$this->getMac());

        $server = $this->getEtvaServer();

        $node_uuid = '';
        $node = $server->getEtvaNode();
        if( $node ) $node_uuid = $node->getUuid();

        $network_rra = new ServerNetworkRRA($node_uuid,$server->getUuid(),$mac_strip,false);
        $network_rra->delete();

      return true;

    }
    
}
