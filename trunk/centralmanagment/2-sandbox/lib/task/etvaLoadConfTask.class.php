<?php

class etvaLoadConfTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'etva';
    $this->name             = 'loadConf';
    $this->briefDescription = 'Load to DB some sysconfig data';
    $this->detailedDescription = <<<EOF
The [etva:loadConf|INFO] task does things.
Call it with:

  [php symfony etva:loadConf|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {    
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = $databaseManager->getDatabase($options['connection'])->getConnection();

    $model = Etva::getEtvaModelFile();

    $networks = isset($model['networks']) ? $model['networks'] : $model[ucfirst('networks')];


    if($this->initialize_networks($networks)) echo "Done!\n";
    else echo "Failed!\n";
    
   
  }

  /*
   * Adds to database default networks of the model
   */
  protected function initialize_networks($networks)
  {
      foreach($networks as $if=>$name)
      {
        try{
            $object = new EtvaVlan();
            $object->setIntf($if);
            $object->setName($name);
            $object->setTagged(0);
            $object->save();
        }catch(Exception $e){
            return false;
        }
      }
      return true;

  }
}
