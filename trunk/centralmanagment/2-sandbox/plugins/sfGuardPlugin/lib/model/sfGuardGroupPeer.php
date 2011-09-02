<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfGuardGroupPeer.php 7634 2008-02-27 18:01:40Z fabien $
 */
class sfGuardGroupPeer extends PluginsfGuardGroupPeer
{
    const DEFAULT_GROUP_ID = 1;
    const _ERR_NOTFOUND_ID_   = 'Group with ID %id% could not be found';
    
    public function getDefaultGroup()
    {
        $group = self::retrieveByPK(self::DEFAULT_GROUP_ID);
        
        if($group) return $group;
        else return self::setDefaultGroup();
    }    

    /*
     * If group exists update info... and return
     * otherwise create new one and set ID = 1
     *
     */
    public function setDefaultGroup()
    {
        $con = Propel::getConnection(sfGuardGroupPeer::DATABASE_NAME);
        
        $criteria = new Criteria();
        $criteria->add(self::NAME,'Admin');
        

        $group = self::doSelectOne($criteria);
        $new = false;
        if(!$group){
            $group = new sfGuardGroup();
            $new = true;
        }

        $group->setName('Admin');
        $group->setDescription('Admin Group');

        if($new) $group->save();        
                
        $selectCriteria = $group->buildPkeyCriteria();
               

        // update values are also stored in Criteria object
        $group->setId(self::DEFAULT_GROUP_ID);

        $updateValues = $group->buildCriteria();

        BasePeer::doUpdate($selectCriteria, $updateValues, $con);

        return $group;
    }
}
