<?php
class etvaUpdatedbTask extends etvaBaseTask
{
    /**
      * Overrides default timeout
      **/
    protected function getSigAlarmTimeout(){
        return 0;   //no notifications
    }

  protected function configure()
  {
    parent::configure();

     // add your own arguments here
     $this->addArguments(array(
        new sfCommandArgument('datafile', sfCommandArgument::REQUIRED, 'Please provide the .yml /path/file'),
     ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'app', 'prod'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'etva';
    $this->name             = 'updatedb';
    $this->briefDescription = 'Updates etva/etvm database';
    $this->detailedDescription = <<<EOF
The [etva:updatedb|INFO] task does things.
Call it with:

  [php symfony etva:updatedb|INFO]
EOF;

  }

  protected function execute($arguments = array(), $options = array())
  {
    // Instance variables
    $this->loadModelConf();

    parent::execute($arguments, $options);

    $this->log("[INFO] cheking db version");
    $i = $this->getCurrentVersion();
    $required_version = $this->getRequiredVersion();
    
    if($required_version == $i){
        $this->log("[INFO] Database is up to date. Nothing to do!");
        return 5;
    }

    $i++;
    $dbfile = $arguments['datafile'];

    for($i; $i<=$required_version; $i++){    
        switch($i){
            case 1:
                $this->upgradeToV1($dbfile);
                break;
            case 2:
                $this->updateToV2($dbfile);
                break;
        }   
    }

    return 0;
  }

  private function loadModelConf(){
    $this->etva_model = Etva::getEtvaModelFile();
  }

  public function getCurrentVersion()
  {
    $dbversion = $this->etva_model['dbversion'];
    $this->log("[INFO] Central Management database is in version ".$dbversion);

    $pattern = '/(\d+)\..*/';
    $replacement = '${1}';
    return intval(preg_replace($pattern, $replacement, $dbversion));
  }

  public function getRequiredVersion(){
    $dbrequired = sfConfig::get('app_dbrequired');
    $this->log("[INFO] Central Management requires that database must be in version ".$dbrequired);
    
    $pattern = '/(\d+)\..*/';
    $replacement = '${1}';
    return intval(preg_replace($pattern, $replacement, $dbrequired));
  }

  private function upgradeToV1($dbdata){
    $this->log("[INFO] Upgrading database to version 1!");
    $this->log($dbdata);

    $output = shell_exec("perl utils/pl/upDBv1.pl $dbdata"); 
    echo $output;

    # cluster_id: EtvaCluster_1

  }

  private function updateToV2($dbdata){
    $this->log("[INFO] Upgrading database to version 2!");

    // Insert your code here
  }

}

?>
