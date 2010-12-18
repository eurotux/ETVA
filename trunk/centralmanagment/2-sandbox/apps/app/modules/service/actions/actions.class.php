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
        //parent container id (extjs)
        $this->containerId = $request->getParameter('containerId');
        $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
        $this->server_services = $this->etva_server->getEtvaServices();                           
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
    public function executeSoapInit(sfWebRequest $request)
    {


        if(SF_ENVIRONMENT == 'soap'){

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
            $etva_server->save();




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

            $result = array('success'=>true,'response'=>'1');

            return $result;


        }
    }


   

    
        

    protected function setJsonError($info,$statusCode = 400){

        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
        return $error;

    }

}
