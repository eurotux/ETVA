<?php

class EtvaVolumegroup extends BaseEtvaVolumegroup
{

  // deletes volume group info and updates related info
  public function delete(PropelPDO $con = null)
  {

        // get physical volumes fron the vg we want to remove
        $etva_volphys = $this->getEtvaVolumePhysicals();

        foreach ( $etva_volphys as $etva_volphy )
        {
            /*
             * for each physical volume....must update size and set allocatable flag
             */            
            
            $etva_volphy->delete();
        }
                

        parent::delete($con);


  }
}
