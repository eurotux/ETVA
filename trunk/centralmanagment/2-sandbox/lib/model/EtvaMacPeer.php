<?php

class EtvaMacPeer extends BaseEtvaMacPeer
{
    const _ERR_ASSIGNED_   = 'Mac address %name% already assigned';
    const _ERR_NOMACS_ = 'No macs available in pool';

    const _ERR_AT_LEAST_MACS_ = 'Should have at least %num% macs available in pool';
    
    public static function retrieveByMac($mac)
	{

		

		$criteria = new Criteria(EtvaMacPeer::DATABASE_NAME);
		$criteria->add(EtvaMacPeer::MAC, $mac);

		$v = EtvaMacPeer::doSelect($criteria);

		return !empty($v) > 0 ? $v[0] : null;
	}
}
