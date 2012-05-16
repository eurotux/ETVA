<?php

/**
 * event actions.
 *
 * @package    centralM
 * @subpackage event
 * @author     Your name here
 */
class eventActions extends sfActions
{

    public function executeView(sfWebRequest $request)
    {
    }

    private function updateSetting($param, $value){
        $c = new Criteria();
        $c->add(EtvaSettingPeer::PARAM, $param);
        $obj = EtvaSettingPeer::doSelectOne($c);
        if($obj === null){
            $obj = new EtvaSetting();           
            $obj->setParam($param);
        }
        $obj->setValue($value);
        $obj->save();
    }

    public function executeJsonDiagnostic(sfWebRequest $request)
    {
        $method     = $request->getParameter('method');
        if($method == 'diagnostic'){
            $this->updateSetting(EtvaSettingPeer::_SMTP_SERVER_, $request->getParameter('smtpserver'));
            $this->updateSetting(EtvaSettingPeer::_SMTP_PORT_, $request->getParameter('port'));
            $this->updateSetting(EtvaSettingPeer::_SMTP_USE_AUTH_ ,$request->getParameter('useauth'));
            $this->updateSetting(EtvaSettingPeer::_SMTP_USERNAME_ ,$request->getParameter('username'));
            $this->updateSetting(EtvaSettingPeer::_SMTP_KEY_ , $request->getParameter('key'));
            $this->updateSetting(EtvaSettingPeer::_SMTP_SECURITY_ ,$request->getParameter('security_type'));

        }

        error_log("[METHOD] $method");
        $result = diagnostic::getAgentFiles($method);

        if(!$result['success'])
        {
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }

        $json_encoded = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($json_encoded);       
    }

    public function executeJsonGetSMTPConf($request){
        error_log(sfConfig::get("app_remote_smtpserver"));

        $c = new Criteria();
        $settings = EtvaSettingPeer::doSelect($c);

        foreach($settings as $set){
            switch($set->getParam()){
                case EtvaSettingPeer::_SMTP_SERVER_:
                    $addr = $set->getValue();
                    break;
                case EtvaSettingPeer::_SMTP_PORT_:
                    $port = $set->getValue();
                    break;
                case EtvaSettingPeer::_SMTP_USE_AUTH_:
                    $useauth = $set->getValue();
                    break;
                case EtvaSettingPeer::_SMTP_USERNAME_:
                    $username = $set->getValue();
                    break;
                case EtvaSettingPeer::_SMTP_KEY_:
                    $key = $set->getValue();
                    break;
                case EtvaSettingPeer::_SMTP_SECURITY_:
                    $security = $set->getValue();
                    break;
            }
        }


        $final = array(
            'addr'      => $addr,
            'port'      => $port,
            'useauth'   => $useauth,
            'username'  => $username,
            'key'       => $key,
            'security'  => $security
        );

        $result = $final;
        $result = json_encode($final);
 
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeLogDownload($request)
    {
        $filepath = sfConfig::get("app_remote_log_file");
        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setHttpHeader('Content-Length', sprintf("%u",filesize($filepath)));
        $response->setContentType('application/x-download');
        $response->setHttpHeader('Content-Disposition',
                        'attachment; filename="'.
                        $filepath.'"');
        $response->sendHttpHeaders();
        ob_end_clean();
    
        $this->getResponse()->setContent(IOFile::readfile_chunked($filepath));    
        return sfView::NONE;
    }

    public function executeJsonGrid($request)
    {

        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('EtvaEvent', $limit);
        $c = new Criteria();

        $this->addSortCriteria($c);
        $this->addFilterCriteria($c);

        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelect');
        $this->pager->setPeerCountMethod('doCount');

        $this->pager->init();


        $elements = array();

        # Get data from Pager
    
        foreach($this->pager->getResults() as $item){
            $data = $item->toArray();
            $data['Priority'] = EtvaEventLogger::getPriority($data['Level']);
            $elements[] = $data;
        }

        $final = array(
            'total' =>   $this->pager->getNbResults(),
            'data'  => $elements
        );

        $result = $final;
        $result = json_encode($final);
 
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }




    protected function addSortCriteria($criteria)
    {
        if ($this->getRequestParameter('sort')=='') return;

        $column = EtvaEventPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

        if('asc' == strtolower($this->getRequestParameter('dir')))
            $criteria->addAscendingOrderByColumn($column);
        else
            $criteria->addDescendingOrderByColumn($column);
    }

    protected function addFilterCriteria($criteria)
    {
        $filters = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : null;
        if(!$filters) return;

        // GridFilters sends filters as an Array if not json encoded
        if(is_array($filters))
        {
            $encoded = false;
        }else
        {
            $encoded = true;
            $filters = json_decode($filters);
        }

        // loop through filters sent by client
        if (is_array($filters)) {
            for ($i=0;$i<count($filters);$i++){
                $filter = $filters[$i];

                // assign filter data (location depends if encoded or not)
                if($encoded){
                    $field = $filter->field;
                    $value = $filter->value;
                    $compare = isset($filter->comparison) ? $filter->comparison : null;
                    $filterType = $filter->type;
                }else{
                    $field = $filter['field'];
                    $value = $filter['data']['value'];
                    $compare = isset($filter['data']['comparison']) ? $filter['data']['comparison'] : null;
                    $filterType = $filter['data']['type'];
                }
                if(!$value) return;
                switch($filterType){
                    case 'string' :
                        $column = EtvaEventPeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
                        $criteria->add($column, "%${value}%",Criteria::LIKE);
                        break;
                    case 'list' :
                        $column = EtvaEventPeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
                        if(strstr($value,',')){
                            $value = explode(',',$value);
//                            for ($q=0;$q<count($fi);$q++){
//                                $fi[$q] = $fi[$q];
//                            }
//                            $value = implode(',',$fi);
                            
                            $criteria->add($column, $value,Criteria::IN);
                            //$qs .= " AND ".$field." IN (".$value.")";
                        }else{
                            $criteria->add($column, $value);                            
                        }
                        break;
                    default:
                        break;
                }
           }
        }
    } 

    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;
    }
}
