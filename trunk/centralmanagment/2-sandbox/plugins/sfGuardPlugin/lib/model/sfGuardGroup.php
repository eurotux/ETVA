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
 * @version    SVN: $Id: sfGuardGroup.php 7634 2008-02-27 18:01:40Z fabien $
 */
class sfGuardGroup extends PluginsfGuardGroup
{    
    public function isDefaultGroup()
    {
        return $this->id == sfGuardGroupPeer::DEFAULT_GROUP_ID;
    }


    /*
     *
     * on delete check if is not default group...
     *
     */
    public function delete(PropelPDO $con = null)
	{
		
		if ($con === null) {
			$con = Propel::getConnection(sfGuardGroupPeer::DATABASE_NAME, Propel::CONNECTION_WRITE);
		}

        if($this->isDefaultGroup()){
            throw new sfException('Cannot remove default group (ID '.$this->id.')!');
            return false;
        }

        /*
         * get default group
         *
         */

        $default_group = sfGuardGroupPeer::getDefaultGroup();

        //get default group
        
        /*
         * check if servers has this group
         * If servers found remove reference to group to be deleted
         */
		$con->beginTransaction();
		try {
            
            
            //select from...
            $c1 = new Criteria();
            $c1->add(EtvaServerPeer::SF_GUARD_GROUP_ID,$this->getId());                        

            //update set
            $c2 = new Criteria();           
            $c2->add(EtvaServerPeer::SF_GUARD_GROUP_ID,$default_group->getId());
            
            BasePeer::doUpdate($c1, $c2, $con);

            parent::delete($con);

            $con->commit();

		} catch (PropelException $e) {
			$con->rollBack();
			throw $e;
		}

	}
}
