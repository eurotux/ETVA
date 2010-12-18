<?php

class EtvaMacPeer extends BaseEtvaMacPeer
{
    
    public static function retrieveByMac($mac)
	{

		

		$criteria = new Criteria(EtvaMacPeer::DATABASE_NAME);
		$criteria->add(EtvaMacPeer::MAC, $mac);

		$v = EtvaMacPeer::doSelect($criteria);

		return !empty($v) > 0 ? $v[0] : null;
	}
}
