<?php

class EtvaVolumePhysical extends BaseEtvaVolumePhysical
{
  // on save mark physical volume
  public function save(PropelPDO $con = null)
  {


        $etva_phy = $this->getEtvaPhysicalvolume();
        $etva_phy->setAllocatable(0);        


        parent::save($con);


  }

  public function preDelete(PropelPDO $con = null)
  {
      $etva_phy = $this->getEtvaPhysicalvolume();
      $etva_phy_devsize = $etva_phy->getDevsize();
      $etva_phy->setPvsize($etva_phy_devsize);
      $etva_phy->setPvfreesize($etva_phy_devsize);
      $etva_phy->setAllocatable(1);
      $etva_phy->save();
      return true;
  }  


}
