<?php


class primaveraActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
    public function executeView(sfWebRequest $request)
    {
        $dispatcher_id = $request->getParameter('dispatcher_id');
        

        // load modules file of dispatcher
        if($dispatcher_id){

            $criteria = new Criteria();
            $criteria->add(EtvaServicePeer::ID,$dispatcher_id);

            $etva_service = EtvaServicePeer::doSelectOne($criteria);
            $dispatcher = $etva_service->getNameTmpl();
            $etva_server = $etva_service->getEtvaServer();

            $tmpl = $etva_server->getAgentTmpl().'_'.$dispatcher.'_modules';
            
            //if exists, load _PRIMAVERA_main_modules.php file
            $directory = $this->context->getConfiguration()->getTemplateDir('primavera', '_'.$tmpl.'.php');

            if($directory)
                return $this->renderPartial($tmpl);
            else
                return $this->renderText('Template '.$tmpl.' not found');
        }else{
            $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
            
        }

    }   
    
    /*
     * processes json requests and invokes dispatcher
     */
    public function executeJson(sfWebRequest $request)
    {
        $service_id = $request->getParameter('id');
        $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('id'));
        if(!$etva_service){
            $msg_i18n = $this->getContext()->getI18N()->__('No service with specified id',array());
            $msg = array('success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
            $result = $this->setJsonError($msg);
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($result);
        }

        $etva_server = $etva_service->getEtvaServer();

        $agent_tmpl =$etva_server->getAgentTmpl();
        $service_tmpl = $etva_service->getNameTmpl();
        $method = $request->getParameter('method');
        $mode = $request->getParameter('mode');
        $params = json_decode($request->getParameter('params'),true);

        if(!$params) $params = array();


        $dispatcher_tmpl = $agent_tmpl.'_'.$service_tmpl;

        if(method_exists($this,$dispatcher_tmpl))
        {
            $ret = call_user_func_array(array($this, $dispatcher_tmpl), array($etva_server,$method,$params,$mode,$service_id));

            if($ret['success'])
                $result = json_encode($ret);
                //$result = json_encode(array(utf8_encode($ret)));
            else
                $result = $this->setJsonError($ret);
        }else{
            $msg_i18n = $this->getContext()->getI18N()->__('No method implemented! %dispatcher%',array('%dispatcher%'=>$dispatcher_tmpl));
            $info = array('success'=>false,'error'=>$msg_i18n);
            $result = $this->setJsonError($info);
        }


            // $result = json_encode($ret);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setHttpHeader("X-JSON", '()');

        return $this->renderText($result);

    }

    public function executePrimavera_ChangeIP(sfWebRequest $request)
    {
    }

    public function executeJsonNoWait(sfWebRequest $request)
    {

        $service_id = $request->getParameter('id');
        $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('id'));

        if(!$etva_service){
            $msg_i18n = $this->getContext()->getI18N()->__('No service with specified id',array());
            $msg = array('success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
            $result = $this->setJsonError($msg);
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($result);
        }

        $etva_server = $etva_service->getEtvaServer();

        $method = $request->getParameter('method');
        $params = json_decode($request->getParameter('params'),true);

        if(!$params) $params = array();

        $etva_server->setSoapTimeout(5);

        // send soap request
        $response = $etva_server->soapSend($method,$params);

        if( !$response['success'] && ( $response['faultactor']!='socket_read' ) ){
            $result = json_decode($response);
        } else {
            $msg_i18n = $this->getContext()->getI18N()->__('Change IP ok.',array());
            $res = array('success'=>true,'agent'=>$response['agent'], 'response'=>$msg_i18n);
            $result = json_decode($res);
        }

        $this->getResponse()->setHttpHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setHttpHeader("X-JSON", '()');

        return $this->renderText($result);
    }

    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }

    public function executePrimavera_Backup(sfWebRequest $request)
    {
    }
    public function executePrimavera_Restore(sfWebRequest $request)
    {
    }
    public function executePrimavera_Users(sfWebRequest $request)
    {
    }
    public function executePrimavera_NewUser(sfWebRequest $request)
    {
    }
    public function executePrimavera_EditUser(sfWebRequest $request)
    {
    }
    public function executePrimavera_WindowsNewUser(sfWebRequest $request)
    {
    }

    private function ignoreWindowsUser( $username )
    {
        if( $username == 'INTERACTIVE' ) return true;
        if( $username == 'Authenticated Users' ) return true;
        return false;
    }
    public function Primavera_main(EtvaServer $etva_server, $r_method, $params,$mode, $service_id)
    {

        $method = str_replace(array("_cbx","_tree","_grid"),"",$r_method);

        // prepare soap info....
        $initial_params = array(
                        //'dispatcher'=>'wizard'
        );

        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$call_params);        

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;

            switch($r_method){
                    case 'primavera_info':
                                $data = array( 'id'=>$service_id );
                                $disk_data = (array)$response_decoded['_disk_'];
                                $totalfreebytes = $disk_data['TotalNumberOfFreeBytes'];
                                $totalbytes = $disk_data['TotalNumberOfBytes'];
                                $totalfree_mb = round($totalfreebytes / 1024 / 1024, 2);
                                $total_mb = round($totalbytes / 1024 / 1024, 2);
                                $totalfree_per = round((100 * $totalfreebytes / $totalbytes), 2);
                                $data['totalfreeper'] = $totalfree_per;
                                $data['totalfreemb'] = $totalfree_mb;
                                $data['totalmb'] = $total_mb;
                                // format date
                                /*$backup_data = (array)$response_decoded['_backups_'];
                                $data['lastbackupdate'] = strftime('%Y-%m-%d %H:%M:%S', (int)$backup_data['changed']);*/

                                // translation of yes or no
                                $b_yes = $this->getContext()->getI18N()->__('Yes');
                                $b_no = $this->getContext()->getI18N()->__('No');

                                $services_data = (array)$response_decoded['_services_'];
                                foreach($services_data as $s=>$v){
                                    $sk = "$s" . "runservice";
                                    $status = (array)$v;
                                    $data["$sk"] = ( $status['state'] == 'SERVICE_RUNNING' ) ? $b_yes : $b_no;
                                }

                                $primavera_data = (array)$response_decoded['_primavera_'];
                                $data['segurancaactiva'] = ( $primavera_data['Seguranca_Activa'] == 0 ) ? $b_no : $b_yes;
                                $data['license'] = ( $primavera_data['License'] == 0 ) ? $b_no : $b_yes;
                                $data['segurancaproempactiva'] = ( $primavera_data['Seguranca_Pro_Emp_Activa'] == 0 ) ? $b_no : $b_yes;
                                $data['modoseguranca'] = ( $primavera_data['Modo_Seguranca'] == 0 ) ? $b_no : $b_yes;
                                $data['nempresas'] = $primavera_data['N_Empresas'];
                                $data['nutilizadores'] = $primavera_data['N_Utilizadores'];
                                $data['npostos'] = $primavera_data['N_Postos'];
                                $data['language'] = $primavera_data['Language'];

                                $network_data = (array)$response_decoded['_network_'];
                                $data['ipaddr'] = $network_data['ipaddr'];
                                $data['netmask'] = $network_data['netmask'];
                                $data['gateway'] = $network_data['gateway'];
                                $data['dhcp'] = $network_data['dhcp'];

                                $return = array( 'success'=>true, 'data'=>$data );
                                break;
                    case 'primavera_backupinfo':
                                $data = array( 'id'=>$service_id );
                                $empresas_data = (array)$response_decoded;
                                $empresas = array();
                                foreach($empresas_data as $eObj){
                                    $e = (array)$eObj;
                                    $db = (array)$e['DATABASE'];
                                    $bkps = (array)$e['BACKUPS'];
                                    //array_push($empresas,array( $db['name'], $e['name'] ));
                                    array_push($empresas,array( 'name'=>$e['name'], 'db'=>$db['name'], 'bkps'=>$bkps ));
                                }
                                $data['empresas'] = $empresas;
                                $return = array( 'success'=>true, 'data'=>$data );
                                break;
                    case 'windows_listusers':
                                $users_data = (array)$response_decoded;
                                $husers = array();
                                foreach($users_data as $eObj){
                                    $e = (array)$eObj;
                                    $uname = $e['username'];
                                    if( !$this->ignoreWindowsUser($uname) ){
                                        if( !$husers["$uname"] ) $husers["$uname"] = array( 'username'=>"$uname", 'groups'=>array() );
                                        array_push($husers["$uname"]['groups'], $e['group']);
                                    }
                                }
                                $wusers = array_values($husers);
                                $return = array( 'success'=>true, 'data'=>$wusers );
                                break;
                    case 'primavera_listbackupplans':
                                $bp_data = (array)$response_decoded;
                                $backupplans = array();
                                foreach($bp_data as $bpObj){
                                    $b = (array)$bpObj;
                                    $str_companies = '';
                                    $str_schedule = 'Daily';
                                    if( isset($b['schedule']) ){
                                        $schedule = (array)$b['schedule'];
                                        if( isset($schedule[0]) ){
                                            $sc_0 = (array)$schedule[0];
                                            if( preg_match("/semanal/i",$sc_0['periodo']) )
                                                $str_schedule = 'Weekly';
                                            else if( preg_match("/mensal/i",$sc_0['periodo']) )
                                                $str_schedule = 'Monthly';
                                        }
                                    }
                                    if( isset($b['companies']) ){
                                        $companies = (array)$b['companies'];
                                        foreach($companies as $cObj){
                                            $company = (array)$cObj;
                                            if( $str_companies != '' )
                                                $str_companies .= ';';
                                            $str_companies .= $company['key'] . ',' . $company['name'];
                                        }
                                    }
                                    $bplan = array( 'id'=>$b['id'],'name'=>$b['name'],'date'=>$b['date'],'lastExecution'=>$b['lastExecution'],'nextExecution'=>$b['nextExecution'],'schedule'=>$str_schedule,'companies'=>$str_companies );
                                    $bplan['verify'] = $b['verify'] ? 'Yes' : 'No';
                                    $bplan['overwrite'] = $b['overwrite'] ? 'Yes' : 'No';
                                    $bplan['incremental'] = $b['incremental'] ? 'Yes' : 'No';
                                    array_push($backupplans,$bplan);
                                }
                                $return = array( 'success'=>true, 'data'=>$backupplans );
                                break;
                    /*case 'primavera_listdatabases':
                                $data = array( 'id'=>$service_id );
                                $db_data = (array)$response_decoded;
                                $databases = array();
                                foreach($db_data as $dbObj){
                                    $db = (array)$dbObj;
                                    if( preg_match("/^PRI/",$db['name']) ){
                                        $db_name = $db['name'];
                                        $e_name = str_replace($db_name,'PRI','');
                                        $dbase = array('db'=>$db_name,'name'=>$e_name);
                                        array_push($databases,$dbase);
                                    }
                                }
                                $data['databases'] = $databases;
                                $return = array( 'success'=>true, 'data'=>$data );
                                break;*/
                    case 'windows_createuser':
                    case 'primavera_backup':
                    case 'primavera_fullbackup':
                    case 'primavera_insertbackupplan':
                    case 'primavera_removebackupplan':
                    case 'primavera_restore':
                    case 'primavera_fullrestore':
                    case 'primavera_start':
                    case 'primavera_stop':
                    case 'primavera_insertuser':
                    case 'primavera_updateuser':
                    case 'primavera_deleteuser':
                    case 'primavera_updateuser_aplicacoes':
                    case 'primavera_updateuser_permissoes':
                    case 'change_ip':
                                $data = array();
                                $okmsg_i18n = $this->getContext()->getI18N()->__($response_decoded['_okmsg_'],array());
                                $return = array( 'success'=>true, 'data'=>$data, 'response'=>$okmsg_i18n );
                                break;
                    case 'primavera_listempresas_cbx':
                                $empresas_data = (array)$response_decoded;
                                $return_empresas = array(array( 'name'=>$this->getContext()->getI18N()->__('All'),'cod'=>'***'));
                                foreach($empresas_data as $eObj){
                                    $empresa = (array)$eObj;
                                    array_push($return_empresas, array('name'=>$this->getContext()->getI18N()->__($empresa['name']),'cod'=>$empresa['name']));
                                }
                                $return = array( 'success'=>true, 'data'=>$return_empresas );
                                break;
                    case 'primavera_listperfis':
                    case 'primavera_listusers':
                    case 'primavera_listempresas':
                    case 'primavera_list_user_aplicacoes_join':
                    case 'primavera_list_user_permissoes_join':
                    case 'primavera_listaplicacoes':
                    case 'primavera_viewuser':
                                $return_data = (array)$response_decoded;
                                $return = array( 'success'=>true, 'data'=>$return_data );
                                break;
                    default:
                                $error_i18n = $this->getContext()->getI18N()->__('No action \'%method%\' defined yet.',array('%method%'=>$method));
                                $info_i18n = $this->getContext()->getI18N()->__('No action \'%method%\' implemented yet',array('%method%'=>$method));
                                $return = array('success' => false,
                                                'error'=>$error_i18n,
                                                'info'=>$info_i18n);
            }
            return $return;

        }else{

            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error_details_i18n = $this->getContext()->getI18N()->__($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details_i18n,'faultcode'=>$response['faultcode']);
            return $result;
        }
    }

}
