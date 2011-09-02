<?php
/*
 * This script performs initial mysql setup. Set default root mysql passsword and db related privileges to etva user
 * uses adduser_mysql.sh
 * 
 * script to add etva mysql settings
 * rewrites databases.yml and propel.ini 
 */

require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('app', 'prod', false);


//generate random password and use it as root mysql password account
$gen_root_password = exec("sh ".dirname(__FILE__)."/genpwd.sh",$outgen,$status_root);

//generate random password and use it as user etva mysql account
$gen_user_password = exec("sh ".dirname(__FILE__)."/genpwd.sh",$outgen,$status_user);

if($status_root==0 && $status_user==0){        
    
    $databases_yml = sfYaml::load(sfConfig::get('sf_config_dir').'/databases.yml');
    $db_name = 'etva';
    $db_user = $databases_yml['all']['propel']['param']['username'];
    $db_pass = $gen_user_password;
               
    exec("sh ".dirname(__FILE__)."/adduser_mysql.sh $gen_root_password $db_name $db_user $db_pass",$output,$status);
    
    //if all went ok put database connection in databases.yml and propel.ini
    if($status==0){
        
        $xx = new sfPropelConfigureDatabaseTask($configuration->getEventDispatcher(),new sfFormatter());
        $xx->run(array('dsn'=>"mysql:host=localhost;dbname=$db_name",'username'=>$db_user,'password'=>$db_pass));
    }

    foreach($output as $outline) echo($outline."\n");

}else foreach($outgen as $outline) echo($outline."\n");
?>
