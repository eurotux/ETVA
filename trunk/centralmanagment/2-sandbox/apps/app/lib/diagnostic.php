<?php

class diagnostic
{

    public static function getAgentFiles($method)
    {
        $apli = new Appliance();

        switch($method){
            case 'get_diagnostic_progress':

                $action = $apli->getStage(Appliance::BACKUP_STAGE);
                error_log("[ACTION] $action");

                switch($action){
                    case Appliance::DB_BACKUP :
                                    $txt = 'Perfoming DB backup...';
                                    $result = array('success'=>true,'txt'=>$txt,'action'=>$action);
                                    break;
                    case Appliance::ARCHIVE_BACKUP :
                                    $txt = 'Creating compressed archive...';
                                    $result = array('success'=>true,'txt'=>$txt,'action'=>$action);
                                    break;
                    default:                                               
                                    $result = array('success'=>true,'txt'=>$action,'action'=>$action);
                                    
                }
                break;
            case 'diagnostic' :
#                $force = $request->getParameter('force');                
                $result = $apli->backup(true, true);

                // generate tarball with logs
                $filepath = sfConfig::get("app_remote_log_file");
                $scriptfile = sfConfig::get("app_remote_log_script");

                $command = "/bin/bash $scriptfile $filepath";
                $node_list = EtvaNodePeer::doSelect(new Criteria());
                foreach($node_list as $node){
                    $name   = $node->getName();
                    $ip     = $node->getIp();
                    $port   = $node->getPort();
                    $command .= " $name $ip $port";            
                }
                putenv("ETVADIAGNOSTIC=symfony");
                $command .= ' 2>&1';
                error_log('[COMMAND]'.$command);

                $path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."utils";
                error_log("[INFO] PATH TO SUDOEXEC".$path.DIRECTORY_SEPARATOR);
                passthru('echo '.$command.' | sudo /usr/bin/php -f '.$path.DIRECTORY_SEPARATOR.'sudoexec.php',$return);
                #$output = shell_exec($command);
                error_log("[INFO] Script diagnostic_ball has exited.");
                error_log("[INFO] ".$output);
                $mail_success = diagnostic::sendDiagnosticEmail();
                if($mail_success != 1){
                    $str = implode("\n", $mail_success);
                    $result['mail_errors'] = $mail_success;
                }    

                if(!$result['success']){
                    if($result['action'] == Appliance::LOGIN_BACKUP) $result['txt'] = 'Could not login!';
                    if($result['action'] == Appliance::DB_BACKUP) $result['txt'] = 'DB backup error...';
                    if($result['action'] == Appliance::MA_BACKUP) $result['txt'] = 'MA backup error...';
                }                            
                                            
                break;
            default :
                $result = array('success'=>true,'data'=>array());
                break;
        }
        return $result;
    }

    private function sendDiagnosticEmail(){

        // generate tarball with logs
//        $smtpServer = sfConfig::get("app_remote_log_smtpserver");
        $filepath = sfConfig::get("app_remote_log_file");
        $scriptfile = sfConfig::get("app_remote_log_mailscript");
        $email =   sfConfig::get("app_remote_log_toemail");        

        $security_type = EtvaSettingPeer::retrieveByParam(EtvaSettingPeer::_SMTP_SECURITY_)->getValue();
        $smtpServer = EtvaSettingPeer::retrieveByParam(EtvaSettingPeer::_SMTP_SERVER_)->getValue();
        $port = EtvaSettingPeer::retrieveByParam(EtvaSettingPeer::_SMTP_PORT_)->getValue();
        $useauth = EtvaSettingPeer::retrieveByParam(EtvaSettingPeer::_SMTP_USE_AUTH_)->getValue();
        if($useauth == '1'){
            $username = EtvaSettingPeer::retrieveByParam(EtvaSettingPeer::_SMTP_USERNAME_)->getValue();
            $key = EtvaSettingPeer::retrieveByParam(EtvaSettingPeer::_SMTP_KEY_)->getValue();
        }
        error_log("[SMTPSERVER] $smtpServer");
        error_log("[COMMAND] perl $scriptfile $filepath $smtpServer $port $email $security_type $useauth $username $key");
        $command = "perl $scriptfile $filepath $smtpServer $port $email $security_type $useauth $username $key";
    

        exec($command, $output, $retval);
        //$output = shell_exec("perl $scriptfile $email $smtpServer $filepath");
        //error_log(print_r($output, true));

        if($retval == 0){
//         $msg = array('success'=>true);
//           return $this->renderText(json_encode($msg));           
            return 1;
        }else{
//            $msg = "Cannot send email: \n";
//            $msg .= implode("\n", $output);
            error_log('[ERROR]'.print_r($output, true));
//            $info = array('success'=>false,'error'=>$msg, 'info'=>$msg);
//            $error = $this->setJsonError($info);
//            return $this->renderText($error);
            return $output;
        }
    }
}
