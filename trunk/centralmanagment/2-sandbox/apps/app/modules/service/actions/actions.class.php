<?php

/**
 * service actions.
 *
 * @package    centralM
 * @subpackage service
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z fabien $
 */
class serviceActions extends sfActions
{
    public function executeIndex(sfWebRequest $request)
    {

        $this->etva_service_list = EtvaServicePeer::doSelect(new Criteria());        
    }


    public function executeView(sfWebRequest $request)
    {        
        $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));                                   
    }

    public function executeNew(sfWebRequest $request)
    {
        $this->form = new EtvaServiceForm();
    }

    public function executeCreate(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $this->form = new EtvaServiceForm();

        $this->processForm($request, $this->form);

        $this->setTemplate('new');
    }

    public function executeEdit(sfWebRequest $request)
    {
        $this->forward404Unless($etva_service = EtvaServicePeer::retrieveByPk($request->getParameter('id')), sprintf('Object etva_service does not exist (%s).', $request->getParameter('id')));
        $this->form = new EtvaServiceForm($etva_service);
    }

    public function executeUpdate(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post') || $request->isMethod('put'));
        $this->forward404Unless($etva_service = EtvaServicePeer::retrieveByPk($request->getParameter('id')), sprintf('Object etva_service does not exist (%s).', $request->getParameter('id')));
        $this->form = new EtvaServiceForm($etva_service);

        $this->processForm($request, $this->form);

        $this->setTemplate('edit');
    }

    public function executeDelete(sfWebRequest $request)
    {
        $request->checkCSRFProtection();

        $this->forward404Unless($etva_service = EtvaServicePeer::retrieveByPk($request->getParameter('id')), sprintf('Object etva_service does not exist (%s).', $request->getParameter('id')));
        $etva_service->delete();

        $this->redirect('service/index');
    }

    protected function processForm(sfWebRequest $request, sfForm $form)
    {
        $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));
        if ($form->isValid())
        {
            $etva_service = $form->save();

            $this->redirect('service/edit?id='.$etva_service->getId());
        }
    }

  /*
   * SOAP
   */

    /*
     * invoked when MA restore ok
     */
    public function executeSoapRestore(sfWebRequest $request)
    {
        if(sfConfig::get('sf_environment') == 'soap'){
            $macaddr = $request->getParameter('macaddr');
            $ok = $request->getParameter('ok');

            /*
             * restore ok...
             */
            $c = new Criteria();
            $c->add(EtvaNetworkPeer::MAC ,$macaddr);

            $etva_network = EtvaNetworkPeer::doSelectOne($c);

            if(!$etva_network){
                $error_msg = sprintf('Object etva_network does not exist (%s).', $macaddr);
                $error = array('success'=>false,'error'=>$error_msg);
                return $error;
            }            
            
            $etva_server = $etva_network->getEtvaServer();
            $agent = $etva_server->getAgentTmpl();

            $message = "MA $agent restore ok";

            //notify system log
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent($etva_server->getName(),'event.log',
                    array('message' =>$message, 'priority'=>EtvaEventLogger::INFO)
            ));

            /*
             * check if is an appliance restore operation...it should be...
             */
            $apli = new Appliance();
            $action = $apli->getStage(Appliance::RESTORE_STAGE);
            if($action){
                $apli->setStage(Appliance::RESTORE_STAGE,Appliance::MA_COMPLETED);
            }

            // remove backup MA file
            $apli->del_backupconf_file(Appliance::MA_ARCHIVE_FILE,$etva_server->getUuid(),$etva_server->getAgentTmpl());
            return array('success'=>true);
            
        }

    }

    public function executeSoapInit(sfWebRequest $request)
    {


        if(sfConfig::get('sf_environment') == 'soap'){

            $agent_tmpl = $request->getParameter('name');
            $ip = $request->getParameter('ip');
            $port = $request->getParameter('port');
            $services = $request->getParameter('services');
            $macaddr = $request->getParameter('macaddr');

            $c = new Criteria();
            $c->add(EtvaNetworkPeer::MAC ,$macaddr);

            $etva_network = EtvaNetworkPeer::doSelectOne($c);

            if(!$etva_network){
                $error_msg = sprintf('Object etva_network does not exist (%s).', $macaddr);
                $error = array('success'=>false,'error'=>$error_msg);

                return $error;
            }
            // print_r($agent_tmpl);
            $etva_server = $etva_network->getEtvaServer();
            $etva_server->setIp($ip);
            $etva_server->setAgentTmpl($agent_tmpl);
            $etva_server->setAgentPort($port);
            $etva_server->setState(1);
            $etva_server->save();



            if($services)
            foreach($services as $service){

                $c = new Criteria();
                $c->add(EtvaServicePeer::NAME_TMPL ,$service->name);
                $c->add(EtvaServicePeer::SERVER_ID ,$etva_server->getId());

                $etva_service = EtvaServicePeer::doSelectOne($c);
                if(!$etva_service){

                    $etva_service = new EtvaService();
                    $etva_service->setNameTmpl($service->name);
                    $etva_service->setEtvaServer($etva_server);

                }

                if(isset($service->description))
                $etva_service->setDescription($service->description);

                if(isset($service->params)){

                    $params = $service->params;
                    $encoded_json = json_encode($params);

                    $etva_service->setParams($encoded_json);

                }


                $etva_service->save();



            }

            /*
             *
             * check if has restore to perform....
             */

            $apli = new Appliance();
		
            $backup_url = $apli->get_backupconf_url(Appliance::MA_ARCHIVE_FILE,$etva_server->getUuid(),$etva_server->getAgentTmpl());

            $result = array('success' =>true);
            if($backup_url)
            {
                $result['reset'] = 1;
                $result['backup_url'] = $backup_url;
            }            

            return $result;


        }
    }




    public function executeJsonGetServices(sfWebRequest $request)
    {
        $sid = $request->getParameter('sid');

        $criteria = new Criteria();
        $criteria->add(EtvaServicePeer::SERVER_ID,$sid);
        $etva_services = EtvaServicePeer::doSelect($criteria);


        $elements = array();
        $i = 0;
        foreach($etva_services as $item){

            $item_arr = $item->toArray(BasePeer::TYPE_FIELDNAME);
            if( isset($item_arr['params']) ){
                // decode params
                $params = json_decode($item_arr['params'],true);
                $item_arr['params'] = $params;
            }
            $elements[$i] = $item_arr;
            $i++;
        }


        $data = array(
            'total' => count($elements),
            'data'  => $elements
        );

        $result = json_encode($data);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);

    }


   

    
        

    protected function setJsonError($info,$statusCode = 400){

        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
        return $error;

    }

}
