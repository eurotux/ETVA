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
            case 10:    // version 1.0
                $this->upgradeToV10($dbfile);
                break;
            case 11:    // version 1.1
                $this->upgradeToV11($dbfile);
                break;
            case 12:    // version 1.2
                $this->upgradeToV12($dbfile);
                break;
            case 13:    // version 1.3
                $this->upgradeToV13($dbfile);
                break;
            case 20:    // version 2.0
                $this->upgradeToV20($dbfile);
                break;
        }   
    }

    return 0;
  }

  private function loadModelConf(){
    $this->etva_model = Etva::getEtvaModelFile();
  }

  private function versionToInt($version){
    $pattern = '/(\d+)\.(\d+)/';
    $replacement = '${1}${2}';
    return intval(preg_replace($pattern, $replacement, $version));
  }

  public function getCurrentVersion()
  {
    $dbversion = $this->etva_model['dbversion'];
    $this->log("[INFO] Central Management database is in version ".$dbversion);

    return $this->versionToInt($dbversion);
  }

  public function getRequiredVersion(){
    $dbrequired = sfConfig::get('app_dbrequired');
    $this->log("[INFO] Central Management requires that database must be in version ".$dbrequired);
    
    return $this->versionToInt($dbrequired);
  }

  private function upgradeToV10($dbdata){
    $this->log("[INFO] Upgrading database to version 1.0!");
    $this->log($dbdata);

    $output = shell_exec("perl utils/pl/upDBv1.pl $dbdata"); 
    echo $output;

    # cluster_id: EtvaCluster_1

  }

  private function upgradeToV11($dbdata){
    $this->log("[INFO] Upgrading database to version 1.1!");
    // nothing to change
  }

  private function upgradeToV12($dbdata){
    $this->log("[INFO] Upgrading database to version 1.2!");
    $this->log($dbdata);

    $output = shell_exec("perl utils/pl/upDBv12.pl $dbdata"); 
    echo $output;
  }
  private function upgradeToV13($dbdata){
    $this->log("[INFO] Upgrading database to version 1.3!");
    $this->log($dbdata);
    $this->log("[INFO] ... nothing to do...");
  }

  private function upgradeToV20($dbdata){
    $this->log("[INFO] Upgrading database to version 2.0!");

    // Insert your code here
  }

}

?>
