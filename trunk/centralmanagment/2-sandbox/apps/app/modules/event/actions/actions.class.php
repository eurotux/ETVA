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
}
