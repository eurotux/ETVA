<?php
/*
 * This class is used to register/backup/restore an ETVA appliance
 * Restore/Backup should only be performed on ETVA SMB
 * for APC class see cURL class
 */

class Appliance
{
    const CONF_SITE_NAME = 'mastersite'; // name to lookup in etva-model.conf

    private $serial_number; // ETVA serial number
    private $username;      // username to login to mastersite
    private $password;      // password to login to mastersite
    private $site_url;      // url of mastersite (set in etva-model.conf)

    private $tmp_db_filename;   // full path for storing DB dump
    private $archive_base_dir;  // full dir path to archive

    const ARCHIVE_FILE = 'backup.tar.gz'; // name of the main ETVA archive
    const DB_FILE = 'db_backup.yml';    //name of DB dump file
    const MA_ARCHIVE_FILE = 'backup_%s__%s'; // string pattern for storing MA backups
    const VA_ARCHIVE_FILE = 'backup_%s__%s'; // string pattern for storing VA backup

    const BACKUP_STAGE = 'backup';
    const MA_BACKUP = 'ma_backup';
    const UPLOAD_BACKUP = 'upload_backup';
    const DB_BACKUP = 'db_backup';
    const ARCHIVE_BACKUP = 'archive_backup';
    const LOGIN_BACKUP = 'login_backup';

    const RESTORE_STAGE = 'restore';
    const GET_RESTORE = 'get_restore';
    const ARCHIVE_RESTORE = 'archive_restore';
    const DB_RESTORE = 'db_restore';

    const VA_RESET = 'va_reset';
    const VA_INIT = 'va_init';
    const VA_UPDATE_PVS = 'va_update_pvs';
    const VA_UPDATE_VGS = 'va_update_vgs';
    const VA_UPDATE_LVS = 'va_update_lvs';
    const VA_UPDATE_VMS = 'va_update_vms';
    const VA_ERROR_STORAGE = 'va_error_storage';
    const VA_COMPLETED = 'va_completed';

    const MA_COMPLETED = 'ma_completed';

    
    /*
     * if serial_number passed use it, otherwise get from DB
     */
    public function Appliance($sn = null)
    {
        $etva_data = Etva::getEtvaModelFile();
	
        isset($etva_data[self::CONF_SITE_NAME]) ? $this->site_url = $etva_data[self::CONF_SITE_NAME]: $this->site_url = '';

        // set temporary DB dump to fixtures/backup/DB_FILE
        $this->tmp_db_filename = sfConfig::get('sf_data_dir').'/fixtures/backup/'.self::DB_FILE;

        // set archive dir to uploads/backup
        $this->archive_base_dir = sfConfig::get('sf_data_dir').'/backup';

        if($sn) $this->serial_number = $sn;

        /*
         * get serial number from DB if not set
         */
        if(!$this->serial_number)
        {
            $pk = 'serial_number';
            $sn_setting = EtvaSettingPeer::retrieveByPk($pk);
            if(!$sn_setting) $sn_setting = new EtvaSetting();
           $this->serial_number = $sn_setting->getValue();
        }
        
    }

    public function get_archive_base_dir()
    {
        return $this->archive_base_dir;
    }

    public function get_serial_number()
    {                
        return $this->serial_number;
    }


    public function get_description()
    {
        if(!isset($this->description)) $this->loadDescription();
        
        return $this->description;
    }
    

    /*
     * get serial, username, password from DB     
     * used for login in mastersite
     *
     */
    public function loadSettings()
    {
        if(!$this->serial_number)
        {
            $pk = 'serial_number';
            $sn_setting = EtvaSettingPeer::retrieveByPk($pk);
            if(!$sn_setting) $sn_setting = new EtvaSetting();
            $this->serial_number = $sn_setting->getValue();
        }

        if(!isset($this->username))
        {
            $pk = 'username';
            $user_setting = EtvaSettingPeer::retrieveByPk($pk);
            if(!$user_setting) $user_setting = new EtvaSetting();
            $this->username = $user_setting->getValue();
        }
        
        if(!isset($this->password))
        {
            $pk = 'password';
            $pwd_setting = EtvaSettingPeer::retrieveByPk($pk);
            if(!$pwd_setting) $pwd_setting = new EtvaSetting();
            $this->password = $pwd_setting->getValue();
        }        
    }

    public function loadDescription()
    {        
        $pk = 'description';
        $desc_setting = EtvaSettingPeer::retrieveByPk($pk);
        if($desc_setting) $this->description = $desc_setting->getValue();
        else $this->description = '';
    }
    

    /*
     * set serial, username, password to DB
     *
     */
    public function saveSettings($sn,$user,$pwd,$desc)
    {
         
        $this->serial_number = $sn;
        $pk = 'serial_number';
        $sn_setting = EtvaSettingPeer::retrieveByPk($pk);
        if(!$sn_setting) $sn_setting = new EtvaSetting();
        $sn_setting->setParam($pk);
        $sn_setting->setValue($this->serial_number);
        $sn_setting->save();

        $this->username = $user;        
        $pk = 'username';
        $user_setting = EtvaSettingPeer::retrieveByPk($pk);
        if(!$user_setting) $user_setting = new EtvaSetting();
        $user_setting->setParam($pk);
        $user_setting->setValue($this->username);
        $user_setting->save();

        $this->password = $pwd;
        $pk = 'password';
        $pwd_setting = EtvaSettingPeer::retrieveByPk($pk);
        if(!$pwd_setting) $pwd_setting = new EtvaSetting();
        $pwd_setting->setParam($pk);
        $pwd_setting->setValue($this->password);
        $pwd_setting->save();

        $this->description = $desc;
        $pk = 'description';
        $desc_setting = EtvaSettingPeer::retrieveByPk($pk);
        if(!$desc_setting) $desc_setting = new EtvaSetting();
        $desc_setting->setParam($pk);
        $desc_setting->setValue($this->description);
        $desc_setting->save();
    }

    /*
     * method to set appliance stage
     * uses APC cache vars to set/get operations progress
     * cache vars name uses appliance serial number has prefix followed by stage name
     */
    public function initStage($full_stage)
    {        

        if(!isset($this->$full_stage)){ // appliance stage not set yet
            $serial_number = $this->get_serial_number();
            // instantiate cache store with serial number
            $this->$full_stage = new apc_CACHE($serial_number);
        }        
    }

    /*
     * add cache var (appliance stage) value
     */
    public function setStage($stage,$val)
    {
        $stage_var = 'stage_'.$stage;
        $this->initStage($stage_var);                
        
        // set cache var for appliance current stage
        $this->$stage_var->add($stage_var,$val);   
    }


    /*
     * get cache var (appliance stage) value
     */
    public function getStage($stage)
    {        
        $stage_var = 'stage_'.$stage;
        $this->initStage($stage_var);
        
        $val = $this->$stage_var->get($stage_var);
        return $val;
    }

    /*
     * del cache var (appliance stage) value
     */
    public function delStage($stage)
    {
        $stage_var = 'stage_'.$stage;
        $this->initStage($stage_var);

        $this->$stage_var->del($stage_var);
    }

    public function resetStages()
    {
        apc_CACHE::clear();
    }


    /*
     * perfoms login/register into mastersite
     */
    public function login($user=null, $pwd=null, $sn=null, $description = null)
    {

        /*
         * load user, pass and serial number
         */
        $this->loadSettings();

        $username = $this->username;
        $password = $this->password;
        $serial_number = $this->serial_number;

        /*
         * if $user, $pwd, $sn means that this login its to register an Appliance
         */
        if($user) $username = $user;
        if($pwd) $password = $pwd;
        if($sn) $serial_number = $sn;

        $url = $this->site_url."/login";

        /*
        * send authentication to mastersite
        */
        $curl_req = new cURL($url);
        $send_auth_params = "username=$username&password=$password";
        if($serial_number) $send_auth_params .= "&serial_number=$serial_number";
        if($description) $send_auth_params .= "&description=$description";
        
        $curl_req->post($send_auth_params);
        $curl_req->cookies(self::CONF_SITE_NAME);        
        $curl_req->exec();
        $curl_req->close();
       
        $status = $curl_req->get_status();

        if($status != 200)
        {
            if($status){
                $raw_response = $curl_req->get_response();
                $response_data = json_decode($raw_response,true);
                $data = array('success'=>false,'agent'=>'MASTERSITE','action'=>'login','info'=>'Cannot authenticate!','error'=>'Cannot authenticate!');

                if($status == 400) $data['info'] = $response_data['error'];

            }
            else{
                $error = $curl_req->get_error();
                $data = array('success'=>false,'agent'=>'MASTERSITE','action'=>'login','info'=>'An error occurred! '.$error,'error'=>$error);
            }

            return $data;
        }

        /*
         * ok...
         */
        $raw_response = $curl_req->get_response();
        $response_data = json_decode($raw_response,true);        
        $response = $response_data['response'];

        $serial = $response['serial_number'];


        /*
        *
        * save SETTINGS
        * update user, pass and serial
        *
        */
        if($serial) $this->saveSettings($serial, $username, $password, $description);

        $data = array('success'=>true, 'serial_number'=>$serial);
        return $data;
    }

    /*
     * checks if CURL request already has session initiated or have to login....
     */
    public function process_curl_auth(cUrl $curl)
    {
        $curl->cookies(self::CONF_SITE_NAME);
        $curl->exec();

        $status = $curl->get_status();        

        if($status == 401){
            //try login            
            $logged_in = $this->login();
            if(!$logged_in['success']){
                //erro no auth                
                $curl->close();
                return $logged_in;
            }else{                
                //now resend the request
                $curl->cookies(self::CONF_SITE_NAME);
                $curl->exec();                
                $status = $curl->get_status();
            }
        }       
        
        if($status == 200){
            $curl->close();
            return array('success'=>true);
        }else{
            $error = $curl->get_error();
            $curl->close();
            return array('success'=>false, 'agent'=>'MASTERSITE','info'=>'An error occurred! '.$error,'error'=>$error);
        }
                           
                       
    }




    public function uploadApplianceProgress($bytes_sent)
    {
        $serial_number = $this->serial_number;
        $this->total_sent += $bytes_sent;                
        
        if(!$this->cache) $this->cache = new apc_CACHE($serial_number);
        $this->cache->add(cUrl::PROGRESS_UPLOAD_TOTAL,$this->upload_archive_size);
        $this->cache->add(cUrl::PROGRESS_UPLOAD_NOW,$this->total_sent);
    }
    

    /*
     *
     * Send appliance archive to MASTERISTE throught socket POST
     *
     */
    public function uploadApplianceBackup($archive_files)
    {
        // parse the given URL
        $url_aux = $this->site_url."/upload"; // mastersite url to upload file
        $url = parse_url($url_aux);

        if ($url['scheme'] != 'http') {
            die('Error: Only HTTP request are supported !');
        }

        // extract host and path:

        $host = $url['host'];
        $port = isset($url['port']) ? $url['port'] : 80;
        $path = $url['path'];                                      

        $data = "";
        $boundary = "---------------------".substr(md5(rand(0,32000)), 0, 10);

        //Collect post data (login info)
        $this->loadSettings();
        $postdata = array(
                            'username' => $this->username,
                            'password' => $this->password,
                            'serial_number' => $this->serial_number
        );        

        //Collect Postdata
        foreach($postdata as $key => $val)
        {
            $data .= "--$boundary\r\n";
            $data .= "Content-Disposition: form-data; name=\"".$key."\"\r\n\r\n".$val."\r\n";
        }

        $data .= "--$boundary\r\n";

        $files = array('file'=>array('name'=>self::ARCHIVE_FILE));
        $gz_options = array('basedir' => $this->archive_base_dir, 'overwrite'=>1, 'storepaths' => 0, 'level' => 3);

        /*
         * simulate compress and check for file size archive
         */
        $this->setStage(self::BACKUP_STAGE,self::ARCHIVE_BACKUP);
        
        $create_gz = new gzip_file(Appliance::ARCHIVE_FILE);
        $create_gz->set_options(array_merge(array('count'=>1),$gz_options));        
        $create_gz->add_files($archive_files);
        $create_gz->create_archive();

        $archive_size = $create_gz->get_count();
        $this->upload_archive_size = $archive_size;

        $file_post_data = array();
        foreach($files as $key => $file)
        {
            $file_post_data[0] = "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$file['name']}\"\r\n";
            $file_post_data[1] = "Content-Type: application/octect-stream\r\n";
            $file_post_data[2] = "Content-Transfer-Encoding: binary\r\n\r\n";
            $file_post_data[3] = "\r\n";
            $file_post_data[4] = "--$boundary--\r\n";
        }

        $content_size = $archive_size+strlen($data)+strlen(implode($file_post_data));

        $this->setStage(self::BACKUP_STAGE,self::UPLOAD_BACKUP);        
        // open a socket connection
        $fp = fsockopen($host, $port, $errno, $errstr);
        if(!$fp){
            $error_msg = "Could not connect to host. $errstr ($errno)";
            return array('success'=>false,'info' =>$error_msg, 'error' =>$error_msg);
        }

        fputs($fp, "POST $path HTTP/1.1\r\n");
        fputs($fp, "Host: $host\r\n");
        fputs($fp, "Content-Type: multipart/form-data; boundary=$boundary\r\n");
        fputs($fp, "Content-length: ". $content_size ."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);


        foreach($files as $key => $file)
        {
            $create_gz = new gzip_file(Appliance::ARCHIVE_BACKUP);
            $create_gz->set_options(array_merge(array('stream' => 1,'cbk'=>array($this, 'uploadApplianceProgress'),'socket' => $fp),$gz_options));            
            $create_gz->add_files($archive_files);
                 
            fputs($fp, $file_post_data[0]);
            fputs($fp, $file_post_data[1]);     
            fputs($fp, $file_post_data[2]);
            
            $create_gz->create_archive();
            
            fputs($fp, $file_post_data[3]);            
            fputs($fp, $file_post_data[4]);
        }

        $result = '';
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 128);
        }
        // close the socket connection:
        fclose($fp);

        // split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);

        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $result[1] : '';

        $response_status = explode(" ",$header);
        $response_status_code = $response_status[1];


        $decoded = json_decode($content,true);

        if($decoded){

            if($decoded['success']){
                $decoded['serial_number'] = $this->serial_number;
                $decoded['success'] = true;
                return $decoded;
            }else{
                $error = $decoded['response']['errorMessage'];
                return array('success'=>false, 'agent'=>'MASTERSITE','info'=>'An error occurred! '.$error,'error'=>$error);

            }

        }

        if($response_status_code != 200)
            return array('success'=>false, 'agent'=>'MASTERSITE','info'=>'An error occurred! '.$error,'error'=>$error);        

        return array('success'=>true, 'agent'=>'MASTERSITE');   // status 200 send ok

    }

    /*
     *
     * Send appliance archive to MASTERISTE throught CURL (nto used)
     *
     */    
    public function uploadApplianceBackup_curl($filename)
    {
        $url = $this->site_url."/upload"; // mastersite url to upload file
        $serial_number = $this->serial_number;                

        $file_to_upload = array('serial_number'=>$serial_number,'file'=>'@'.$filename);
        
        $curl_req = new cURL($url);
        $curl_req->post($file_to_upload);        
        $curl_req->progress($serial_number);
        $result = $this->process_curl_auth($curl_req); // check auth....        

        if(!$result['success']){
            $result['action'] = self::LOGIN_BACKUP;
            return $result;
        }
        

        // ok...
        $response = $curl_req->get_response();
        $decoded = json_decode($response,true);        
        $decoded['serial_number'] = $serial_number;
        $decoded['success'] = true;
        return $decoded;

    }


    /*
     *
     * Get appliance backup from MASTERISTE
     * $backup_id - id file from mastersite DB
     * $filename - new file for backup archive
     *
     */
    public function getApplianceBackup($backup_id, $filename)
    {                
        
        $fp = fopen($filename,'w+');
        
        $url = $this->site_url."/download";
        $serial_number = $this->serial_number;        
        
        $curl_req = new cURL($url);
        $curl_req->post(array('id'=>$backup_id));
        $curl_req->progress($serial_number);
        $curl_req->setopt(CURLOPT_FILE,$fp);        
        $result = $this->process_curl_auth($curl_req);
        fclose($fp);

        if(!$result['success']) return $result;

        $response = $curl_req->get_response();

        $decoded = json_decode($response,true);
        return $decoded;

    }


    /*
     * list all backups in MASTERSITE
     */
    public function get_backups()
    {
        $url = $this->site_url."/list";
        
        $curl_req = new cURL($url);        
        $result = $this->process_curl_auth($curl_req);        

        if(!$result['success']) return $result;
        
        $response = $curl_req->get_response();
        
        $decoded = json_decode($response,true);        
        return $decoded;

    }

    /*
     * delete backup ID from mastersite
     */
    public function delete($id)
    {
        $url = $this->site_url."/delete";
        $curl_req = new cURL($url);
        $curl_req->post(array('id'=>$id));
        $result = $this->process_curl_auth($curl_req);

        if(!$result['success']) return $result;

        $response = $curl_req->get_response();

        $decoded = json_decode($response,true);
        return $decoded;
    }

    /*
     *  return backup url for direct download backup conf (VA/MA) otherwise false
     *  $file_pattern - MA_ARCHIVE or VA_ARCHIVE
     */
    public function get_backupconf_url($file_pattern, $uuid, $agent)
    {
        
        $serial_number = $this->serial_number;
        $filename = sprintf($file_pattern, $uuid, $agent);

        // check if file is in dir without extension (.xxx) and returns it with extension.
        $file = IOFile::get_file_inDir($this->archive_base_dir,$filename,false);

        if($file && file_exists($this->archive_base_dir.'/'.$file)){
            sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
            // create url for direct file download
            //$full_url = public_path('uploads/backup/'.$file,true);
            $full_url = public_path('download/file/?filename='.$file.'&type=backup',true);
            //$full_url = url_for('download/file/filename='.$file,true);
            return $full_url;
        }
        return false;
    }

    public function del_backupconf_file($file_pattern, $uuid, $agent)
    {

        $serial_number = $this->serial_number;
        $filename = sprintf($file_pattern, $uuid, $agent);

        // check if file is in dir without extension (.xxx) and returns it with extension.
        $file = IOFile::get_file_inDir($this->archive_base_dir,$filename,false);

        if($file && file_exists($this->archive_base_dir.'/'.$file)){
            unlink($this->archive_base_dir.'/'.$file);
        }
        return false;
    }

    /*
     *  get backup conf from VA or MA
     *  returns path to stored backup file
     * 
     */
    public function get_backupconf($name, $ip,$port,$path_to_store, $diagnostic = false)    
    {
        $serial_number = $this->serial_number;

        $url = 'http://'.$ip;
        if($port) $url.=":".$port;
        $url.="/get_backupconf";        

        $fp = fopen($path_to_store,'wb');
        if($fp === false){
            $msg = 'Could not open '.$path_to_store.' for writing!';
            return array('success'=>false, 'error'=>$msg);
        }

        $curl_req = new cURL($url);
        $curl_req->post("diagnostic=$diagnostic");
        $curl_req->progress($serial_number);
        $curl_req->setopt(CURLOPT_FILE,$fp);
        $curl_req->exec();
        $curl_req->close();
        fclose($fp);

        $info = $curl_req->get_info();
        $content_type = $info['content_type'];

        $stored_path = $path_to_store;

        // try to add extension based on mime type received.....
        $ext = IOFile::get_mime_extension($content_type);
        if($ext)
        {
            // if has extension rename file...
            $stored_path = $path_to_store.'.'.$ext;
            rename($path_to_store,$stored_path);
        }

        $status = $curl_req->get_status();
        $error = $curl_req->get_error();
        if($status != 200 && $error){
            $msg = 'Could not get '.$name.' backup conf ('.$error.')';
            return array('success'=>false, 'error'=>$msg);
        }

        return array('success'=>true,'path' => $stored_path);
    }

    /*
     * performs Appliance Backup
     */
    public function backup($force, $diagnostic = false)
    {
        if(!$diagnostic){
            $serial_number = $this->serial_number;
    
            //clear stage cache
            $this->delStage(self::BACKUP_STAGE);
            $this->delStage(self::MA_BACKUP);
        }else{
            $response_msg = '';
            $va_down = array();
        }

        /*
         * VIRT AGENTS
         */

        $node_list = EtvaNodePeer::getWithServers();
        $node_num = count($node_list);
        if(!$diagnostic){
            if($node_num != 1){
                /*
                 * ERROR should be only one element (standard ETVA release only)
                 */
                $msg = "Sould only be one Virtualization Agent! $node_num found!";
                $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg,'error'=>$msg);
                return $data;
            }
        }

        $archive_files = array();
        foreach($node_list as $node){            

            /*
             * check node state ok to comm with agent
             */
            if(!$diagnostic){
                if(!$node->getState()){
                    $node_name = $node->getName();
                    $msg = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_STATE_DOWN_,array('%name%'=>$node_name));
                    $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg,'error'=>$msg);
                    return $data;
                }
            }else{
                if($node->getInitialize() == EtvaNodePeer::_INITIALIZE_PENDING_){
                    continue;
                }
                if(!$node->getState()){
                    $response_msg .= $node->getName();
                    $response_msg .= ', ';
                    $va_down[] = $node->getName();
                    $command = "touch ";
                    $command .= $this->archive_base_dir."/";
                    $command .= $node->getName()."_down";
                    exec($command);
                    continue;
                }
            }
            

            if(!$diagnostic){
                $servers_list = $node->getEtvaServers();
                $servers_down = array();

                /*
                 * Firs pass, check for servers down...
                 */
                foreach($servers_list as $server)
                {
                    $server_name =$server->getName();
                    $server_state =$server->getState();
                    $server_vm =$server->getVmState();
                    $server_ma =$server->getAgentTmpl();
    
                    /*
                     * if there's an agent in VM and if vm not running or agent is down add to servers down
                     */
                    if($server_ma && !$server_state){
                        $servers_down[] = Etva::getLogMessage(array('agent'=>$server->getName(),'msg'=>'Down'), Etva::_AGENT_MSG_);
                    }                                
                }

                /*
                 * First pass return errors found
                 */
                if(!empty($servers_down) && !$force)
                {
                    $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'action'=>self::MA_BACKUP,'info'=>implode('<br>',$servers_down),'error'=>implode(' ',$servers_down));
                    return $data;
                }
            


                /*
                 * Second pass...user has been warn
                 */
                
                $servers_error = array();
                $this->setStage(self::BACKUP_STAGE,self::MA_BACKUP);
                foreach($servers_list as $server)
                {
                    $server_name =$server->getName();
                    $server_state =$server->getState();
                    $server_vm =$server->getVmState();
                    $server_ma =$server->getAgentTmpl();
                    $server_uuid =$server->getUuid();
    
                    /*
                     * if there's an agent in VM send backup command
                     */
                    if($server_ma && $server_state){
                        /*
                         * take care of MA backup stuff here....
                         */                   
                        $this->setStage(self::MA_BACKUP,$server_ma);
                        
                        $ma_filename = sprintf(self::MA_ARCHIVE_FILE, $server_uuid, $server_ma);
    
                        // filename path without extension yet. it will be given by get_backupconf result
                        $full_path = $this->archive_base_dir.'/'.$ma_filename; 
    
                        $ma_backup = $this->get_backupconf($server_name, $server->getIp(), $server->getAgentPort(), $full_path);
                                            
                        if(!$ma_backup['success']){
                            $servers_error[] = $ma_backup['error'];
                            continue;
                        }                    
                        
                        $archive_files[] = $ma_backup['path'];
    
                    }
                }// end servers backup agents
            }


            /*
             * get VA backup
             *
             */
            $node_uuid = $node->getUuid();
            $node_name = $node->getName();
            $va_filename = sprintf(self::VA_ARCHIVE_FILE, $node_uuid, 'VA');

            // filename path without extension yet. it will be given by get_backupconf result
            $full_path = $this->archive_base_dir.'/'.$va_filename;            
            $va_backup = $this->get_backupconf($node_name, $node->getIp(), $node->getPort(), $full_path, $diagnostic);
            if($va_backup['success']) $archive_files[] = $va_backup['path'];

        }

        if(!$diagnostic){
            if(!empty($servers_error)){
                $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'action'=>self::MA_BACKUP,'info'=>implode('<br>',$servers_error),'error'=>implode(' ',$servers_error));
                return $data;
            }
        }
 

        /*
         *
         * CENTRAL MANAGEMENT BACKUP (DB)
         *
         *
         */       

        $db_filename = $this->tmp_db_filename;        
        $this->setStage(self::BACKUP_STAGE,self::DB_BACKUP);

        // do NOT store session info in backup
        $data = new MyPropelData();
        $data->dumpData($db_filename, 'all',array('Sessions'),null);        
        $archive_files[] = $db_filename;
        error_log('CM BACKUP');
        error_log($db_filename);
       
        if(!$diagnostic){
            $response = $this->uploadApplianceBackup($archive_files);
            if(!empty($servers_error)){
                $response['errors'] = implode('<br>',$servers_error);            
            }
        }else{
            $response_msg .= ' reported down. Their state was not included.';
            $response = array('success'=>true,'agent'=>sfConfig::get('config_acronym'), 'info'=>$response_msg, 'va_down'=>$va_down);
        }
        
        return $response;
        
    }

    /*
     * enables/disables etva for dev and prod env
     */
    public function disable($disable = true)
    {
        $state = 'disable';
        if(!$disable) $state = 'enable';

        chdir(sfConfig::get('sf_root_dir'));
        exec("symfony project:$state dev ",$output,$status);
        exec("symfony project:$state prod ",$output,$status);
    }
    

    /*
     * performs Appliance Restore
     */
    public function restore($backup_id)
    {

        //clear stage cache
        $this->delStage(self::RESTORE_STAGE);        
        
        chdir(sfConfig::get('sf_root_dir'));

        /*
         * TELL VA to reset stuff
         */
        $node_list = EtvaNodePeer::doSelect(new Criteria());
        $node_num = count($node_list);
        if($node_num != 1){
            /*
             * ERROR should be only one element (standard ETVA release only)
             */
            $msg = "Sould only be one Virtualization Agent! $node_num found!";
            $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg,'error'=>$msg);
            return $data;
        }

        $node = $node_list[0];

        /*
         * check node state ok to comm with agent
         */
        if(!$node->getState()){
            $node_name = $node->getName();
            $msg = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_STATE_DOWN_,array('%name%'=>$node_name));
            $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'action'=>'check_nodes','info'=>$msg,'error'=>$msg);
            return $data;
        }
                        

        $this->disable();

        /*
         *
         * FIRST THING....CLEANUP DESTINATION FOLDER AND GET RESTORE ARCHIVE
         *
         */
        IOFile::unlinkRecursive($this->archive_base_dir, false);
        $full_path = $this->archive_base_dir.'/'.self::ARCHIVE_FILE;
        
        $this->setStage(self::RESTORE_STAGE, self::GET_RESTORE);
        $response = $this->getApplianceBackup($backup_id,$full_path);

        
        /*
         *
         * DECOMPRESS BACKUP ARCHIVE
         *
         */
        $this->setStage(self::RESTORE_STAGE,self::ARCHIVE_RESTORE);
        
        $create_gz = new gzip_file($full_path);

        $base_dir = $this->archive_base_dir;
        $create_gz->set_options(array('basedir' => $base_dir,'overwrite'=>1));
        $create_gz->extract_files();


        /*
         * get DB file and put in tmp_db_filename
         */
        $db_filename = $base_dir.'/'.self::DB_FILE;
        

        if(!file_exists($db_filename)) return array('success'=>false,'error'=>'no file');

        // move DB backup to correct folder...
        rename($db_filename,$this->tmp_db_filename);
                     


        /*
         *
         * CLEAN DB ????
         *
         */
         
         /*
         * delete tables and build again....
         */        
        
        $command = "symfony propel:insert-sql --no-confirmation";
        $path = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."utils";

        ob_start();

        passthru('echo '.$command.' | sudo /usr/bin/php -f '.$path.DIRECTORY_SEPARATOR.'sudoexec.php',$return);

        $result = ob_get_contents();
        ob_end_clean();

        if($result!=0 || $return!=0){
            $msg = 'An error occurred while deleting DB. Aborted!'.$status;
            $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'action'=>self::DB_RESTORE,'info'=>$msg,'error'=>$msg);
            return $data;
        }
        


        /*
         *
         * RESTORE CENTRAL MANAGEMENT BACKUP (DB)
         *
         *
         */

        
        $this->setStage(self::RESTORE_STAGE,self::DB_RESTORE);

        

        /*
         * load data to DB
         */
        //sfContext::getInstance()->getStorage()->regenerate(true);
        exec("symfony propel:data-load ".$this->tmp_db_filename,$output,$status);

        if($status != 0){
            // aconteceu erro
            $msg = 'An error occurred while generating DB dump. Aborted!'.$status;
            $data = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'action'=>self::DB_RESTORE,'info'=>$msg,'error'=>$msg);
            return $data;
        }  


        /*
         * 
         * generate session to stay logged in...
         *
         */
        sfContext::getInstance()->getStorage()->regenerate();
      

        $this->setStage(self::RESTORE_STAGE, self::VA_RESET);

        $node_va = new EtvaNode_VA($node);

        /*
         * get new uuid from DB
         */
        $backup_node = EtvaNodePeer::doSelectOne(new Criteria());
        $uuid = $backup_node->getUuid();
        $response = $node_va->send_change_uuid($uuid);
        

        if(!$response['success']) return  $response;        

        $response = array('success'=>true);
        
        return $response;        

    }

}

?>
