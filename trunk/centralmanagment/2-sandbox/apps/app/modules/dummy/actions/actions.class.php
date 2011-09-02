<?php


class dummyActions extends sfActions
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
            
            //if exists, load _DUMMY_main_modules.php file
            $directory = $this->context->getConfiguration()->getTemplateDir('dummy', '_'.$tmpl.'.php');

            if($directory)
                return $this->renderPartial($tmpl);
            else
                return $this->renderText('Template '.$tmpl.' not found');
        }else{
            $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
            
        }

    }   
    
}
