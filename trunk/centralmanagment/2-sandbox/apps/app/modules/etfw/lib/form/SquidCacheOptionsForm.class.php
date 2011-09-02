<?php

class SquidCacheOptionsForm extends myBaseForm
{
  public function configure()
  {
      $this->setValidators(array(
            'dead_peer_timeout'    => new sfValidatorNumber(array('required' => false)),
            'hierarchy_stoplist'   => new sfValidatorString(array('required' => false)),
            'icp_query_timeout' => new sfValidatorNumber(array('required' => false)),
            'mcast_icp_query_timeout' => new sfValidatorString(array('required' => false))
          ));
      
  }
}
