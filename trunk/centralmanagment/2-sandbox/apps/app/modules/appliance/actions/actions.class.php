<?php

/**
 * appliance actions.
 *
 * @package    centralM
 * @subpackage appliance
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12479 2008-10-31 10:54:40Z fabien $
 */
class applianceActions extends sfActions
{
    public function executeBackup(sfWebRequest $request)
    {
    }

    public function executeRestore(sfWebRequest $request)
    {
    }

    public function executeRegister(sfWebRequest $request)
    {
    }



    public function executeJsonListBackup(sfWebRequest $request)
    {

        $apli = new Appliance();
        $serial = $apli->get_serial_number();

        if(!$serial){
            $msg = 'Need to register first!';
            $result = array('success'=>false,'action'=>'need_register','info'=>$msg, 'error'=>$msg);
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }
        
        $elements = $apli->get_backups();        

        if(isset($elements['success']) && $elements['success'] == false)
        {
            $error = $this->setJsonError($elements);
            return $this->renderText($error);
        }
        
        $response = array(
            'total' => count($elements),
            'sn' => $serial,
            'data'  => $elements
        );

        $result = json_encode($response);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeJsonDelBackup(sfWebRequest $request)
    {
        $backup_id = $request->getParameter('backup');
        $apli = new Appliance();
        $response = $apli->delete($backup_id);

        $result = json_encode($response);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    
    public function executeJsonBackup(sfWebRequest $request)
    {
        $apli = new Appliance();
        $serial = $apli->get_serial_number();

        if(!$serial){
            $msg = 'Need to register first!';
            $result = array('success'=>false,'agent'=>'MASTERSITE','action'=>'need_register','info'=>$msg, 'error'=>$msg);
            $error = $this->setJsonError($result);
            return $this->renderText($error);            
        }                

        $method = $request->getParameter('method');

        switch($method){
            case 'get_backup_progress':

                            $action = $apli->getStage(Appliance::BACKUP_STAGE);

                            switch($action){
                                case Appliance::MA_BACKUP :
                                                $m_agent = $apli->getStage($action);
                                                
                                                $cache = new apc_CACHE($serial);                                                
                                                $partial_download = $cache->get(cURL::PROGRESS_DOWNLOAD_NOW);                                                
                                                
                                                $result = array('success'=>true,'action'=>$action,'ma'=>$m_agent,'down'=>$partial_download);
                                                break;
                                case Appliance::UPLOAD_BACKUP :
                                                $cache = new apc_CACHE($serial);
                                                $total_upload = $cache->get(cURL::PROGRESS_UPLOAD_TOTAL);
                                                $partial_upload = $cache->get(cURL::PROGRESS_UPLOAD_NOW);
                                                $percent = $partial_upload/$total_upload;
                                                $result = array('success'=>true,'action'=>$action,'total_up'=>$total_upload,'partial_up'=>$partial_upload,'percent'=>$percent);
                                                break;
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
            case 'backup' :
                            $force = $request->getParameter('force');
                            $result = $apli->backup($force);
                            
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



        if(!$result['success'])
        {
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }

        $json_encoded = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($json_encoded);       

    }

    

    /*
     * perform register of ETVA in MASTERSITE and returns product SERIAL NUMBER
     */
    public function executeJsonRegister(sfWebRequest $request)
    {
        $apli = new Appliance();
        $method = $request->getParameter('method');

        switch($method){
            case 'register' :
                              $user = $request->getParameter('username');
                              $pass = $request->getParameter('password');
                              $sn = $request->getParameter('serial_number');
                              $desc = $request->getParameter('description');
                              
                              $result = $apli->login($user, $pass, $sn, $desc);
                              
                              break;
            default         :

                              $serial = $apli->get_serial_number();
                              $desc = $apli->get_description();
                              $result = array('success'=>true,'data'=>array('description'=>$desc,'serial_number' => $serial));
                              break;
        }

        if(!$result['success'])
        {
            $error = $this->setJsonError($result);
            return $this->renderText($error);   
        }

        $json_encoded = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($json_encoded);       
    }


    public function executeJsonRestoreProgress(sfWebRequest $request)
    {
        
        $serial = $request->getParameter('sn');
        $apli = new Appliance($serial);
        
        $action = $apli->getStage(Appliance::RESTORE_STAGE);

        switch($action){
            case Appliance::GET_RESTORE :
                            $cache = new apc_CACHE($serial);
                            $total_download = $cache->get(cURL::PROGRESS_DOWNLOAD_TOTAL);
                            $partial_download = $cache->get(cURL::PROGRESS_DOWNLOAD_NOW);
                            $percent = $partial_download/$total_download;
                            $result = array('success'=>true,'action'=>$action,'total_down'=>$total_download,'partial_down'=>$partial_download,'percent'=>$percent);
                            break;
            
            case Appliance::ARCHIVE_RESTORE :
                            $txt = 'Uncompressing archive...';
                            $result = array('success'=>true,'txt'=>$txt,'action'=>$action);
                            break;
            case Appliance::DB_RESTORE :
                            $txt = 'Restoring DB...';
                            $result = array('success'=>true,'txt'=>$txt,'action'=>$action);
                            break;            
            case Appliance::VA_ERROR_STORAGE :
                            $result = array('success'=>true,'txt'=>'Storage not restored! Getting actual storage...','action'=>$action);
                            break;
            default:
                            $txt = $action;
                            $result = array('success'=>true,'txt'=>$txt,'action'=>$action);

        }

        if(!$result['success'])
        {
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }

        $json_encoded = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($json_encoded);
    }
    

    public function executeJsonRestore(sfWebRequest $request)
    {
        $backup_id = $request->getParameter('backup');
        $backup_size = $request->getParameter('backup_size');
        
        $apli = new Appliance();        

        $method = $request->getParameter('method');

        switch($method){            
            case 'restore' :

                            $serial = $apli->get_serial_number();

                            if(!$serial){
                                $msg = 'Need to register first!';
                                $result = array('success'=>false,'agent' => 'MASTERSITE','action'=>'need_register','info'=>$msg, 'error'=>$msg);
                                $error = $this->setJsonError($result);
                                return $this->renderText($error);
                            }
                            
                            $result = $apli->restore($backup_id);                            

                            if(!$result['success']){                                
                                if($result['action'] == 'check_nodes') $result['txt'] = 'VA error...';
                            }
                            
                            break;
            default :
                            $result = array('success'=>true,'data'=>array());
                            break;
        }



        if(!$result['success'])
        {
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }

        $json_encoded = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($json_encoded);

    }

    


    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }

}
