<?php

/**
 * node actions.
 *
 * @package    centralM
 * @subpackage node
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * node actions controller
 * @package    centralM
 * @subpackage node
 *
 */
class nodeActions extends sfActions
{



    /*
     * export rra data in xml
     */
    public function executeXportLoadRRA(sfWebRequest $request)
    {
        $etva_node = EtvaNodePeer::retrieveByPK($request->getParameter('id'));

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $nodeload_rra = new NodeLoadRRA($etva_node->getName());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($nodeload_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;

    }

    /*
     * returns cpu load png image from rrd data file
     */
    public function executeGraphPNG(sfWebRequest $request)
    {

        $etva_node = EtvaNodePeer::retrieveByPK($request->getParameter('id'));

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');

        $nodeload_rra = new NodeLoadRRA($etva_node->getName());
        $title = $etva_node->getName();
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $nodeload_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;

    }



  /*
   * Used when loading from left panel
   * Displays node panel related stuff (node info | servers | network | storage)
   * Uses view template
   * Params: id ( node id)
   */
    public function executeView(sfWebRequest $request)
    {
        //used to make soap requests to node VirtAgent
        $this->node_id = $request->getParameter('id');

        // used to get parent id component (extjs)
        $this->containerId = $request->getParameter('containerId');

        // used to build node grid dynamic
        $this->node_tableMap = EtvaNodePeer::getTableMap();

        //maybe deprecated
        //used to build form to create new server with default values
        $this->server_form = new EtvaServerForm();

        // used to build server grid dynamic
        $this->server_tableMap = EtvaServerPeer::getTableMap();

        $this->sfGuardGroup_tableMap = sfGuardGroupPeer::getTableMap();
        


    }

    

  /*
   * Triggered when clicking in 'storage tab'
   * Shows device info
   * Uses storage template
   */
    public function executeStorage(sfWebRequest $request)
    {
        $this->node = EtvaNodePeer::retrieveByPk($request->getParameter('id'));
    }


  /*
   * Used to create new node object
   * Note: NOT used
   */

    public function executeJsonCreate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$request->isMethod('post')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $this->form = new EtvaNodeForm();

        $result = $this->processJsonForm($request, $this->form);

        if(!$result['success']){
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }

        $result = json_encode($result);

        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).

        return $this->renderText($result);

    }

    /*
     * performs update state of all nodes
     * for each node calls checkstate
     */
    public function executeBulkUpdateState()
    {
        $node_list = EtvaNodePeer::doSelect(new Criteria());

        $results = array();
        if($node_list)
            foreach($node_list as $etva_node)
                $this->checkState($etva_node);

                

    }

    /*
     * check node state
     * returns json script call
     * uses checkState
     */
    public function executeJsonCheckState(sfWebRequest $request){

        $etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'));
        $response = $this->checkState($etva_node);

        $success = $response['success'];

        if(!$success){
            

            return $this->renderText("<script>
                                        Ext.ux.Logger.error('".$response['error']."');
                                        notify({html:'".$response['error']."'});
                                      </script>");


        }else{
          

            return $this->renderText("<script>
                                        Ext.ux.Logger.info('System check');
                                      </script>");

        }



    }

    /*
     * checks node state
     * send soap request and update DB state
     * returns response from agent
     */
    public function checkState($etva_node){        

        $method = 'getstate';
        $response = $etva_node->soapSend($method);
                
        $success = $response['success'];
        
        if(!$success){

            $etva_node->setState(0);
            $etva_node->save();

        }else{            

            $etva_node->setState(1);
            $etva_node->save();

        }

        return $response;       

    }

  /*
   * Used to update a field node
   * Note: not in use
   */
    public function executeJsonUpdate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$request->isMethod('post') && !$request->isMethod('put')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        if(!$etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'))){
            $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('id'));
            $info = array('success'=>false,'error'=>$error_msg);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $etva_node->setByName($request->getParameter('field'), $request->getParameter('value'));
        $etva_node->save();

        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
        return $this->renderText($result);

    }

  /*
   * Used to delete a field node
   * Note: not in use
   */
    public function executeJsonDelete(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'))){
            $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('id'));
            $info = array('success'=>false,'error'=>$error_msg);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $etva_node->delete();

        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
        return $this->renderText($result);
    }

    /**
     * Returns pre-formated data for Extjs grid with node information
     *
     * Request must be Ajax
     *
     * $request may contain the following keys:
     * - query: json array (field name => value)
     * @return array json array('total'=>num elems, 'data'=>array(network))
     */
    /*
     * Used to show node grid info
     * Returns: array(total=>1,data=>node info)
     */
    public function executeJsonGridInfo(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');
        $elements = array();
        $this->etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'));
        $elements[] = $this->etva_node->toArray();

        $final = array('total' =>   count($elements),'data'  => $elements);
        $result = json_encode($final);

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).

        return $this->renderText($result);

    }

    
      /*
       * Returns lists all nodes for grid with pager
       */
    public function executeJsonGrid($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('EtvaNode', $limit);
        $c = new Criteria();

        $this->addSortCriteria($c);

        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelect');
        $this->pager->setPeerCountMethod('doCount');

        $this->pager->init();


        $elements = array();

        # Get data from Pager
        foreach($this->pager->getResults() as $item)
        $elements[] = $item->toArray();

        $final = array(
      'total' =>   $this->pager->getNbResults(),
      'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
        return $this->renderText($result);

    }

    protected function addSortCriteria($criteria)
    {
        if ($this->getRequestParameter('sort')=='') return;

        $column = EtvaNodePeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

        if ('asc' == strtolower($this->getRequestParameter('dir')))
        $criteria->addAscendingOrderByColumn($column);
        else
        $criteria->addDescendingOrderByColumn($column);
    }


/*
 *
 * SOAP Requests
 *
 */

  /*
   * Executes requests for all nodes ('broadcast' messages)
   */
    function executeBulkSoap(sfWebRequest $request){

        $node_list = EtvaNodePeer::doSelect(new Criteria());

        $results = array();

        foreach($node_list as $etva_node){


            $request->setParameter('id', $etva_node->getId());

            $results[$etva_node->getName()] = $this->executeSoap($request,1);
        }

        $return = json_encode($results);
        $this->getResponse()->setHttpHeader("X-JSON", '()');
        return $this->renderText($return);

    }



    


    function executeSoap(sfWebRequest $request,$bulk=null){
        
        $this->node = EtvaNodePeer::retrieveByPk($request->getParameter('id'));

        if(!$this->node){

            $error = 'Error: no virt agent found'. "\n";
            $this->getResponse()->setStatusCode(400);
            // $this->getResponse()->setHttpHeader('Status', '404 Not Found');
            $error = json_encode($error);
            // $error = json_encode(array('error'=>'da'));
            return $this->renderText($error);
        }

        $addr = $this->node->getIP();
        $port = $this->node->getPort();
        $proto = "tcp";
        $host = "" . $proto . "://" . $addr . ":" . $port;

        $method = $this->getRequestParameter('method');
        $params = ($this->getRequestParameter('params'))?
        json_decode($this->getRequestParameter('params'),true) : array("nil"=>"true");

        // $response = $this->soap($host,$addr,$port,$proto,$method,$params);
        //$soap = new SOAP_Client($host,false,$port);
        
        $soap = new soapClient($host,$port);
        $method = $this->getRequestParameter('method');
        $params = ($this->getRequestParameter('params'))?
        json_decode($this->getRequestParameter('params'),true) : array("nil"=>"true");

        $response = $soap->processSoap($method, $params);
        $return = $response;
       

//        if (is_a($response, 'PEAR_Error')) {
//            $error = $this->node->getName().' : ' . $response->getMessage();
//            // $error .= 'Error: ' . $response->getDetail() . "\n";
//
//
//
//            $params_string = array();
//            foreach ($params as $key => $value)
//            $params_string[] = $key ." = ". $value;
//
//
//            // $error .= "(".implode(',',$params_string).")"."\n";
//
//            $error_resp = array('success'=>false,'error'=>$error);
//            if(!$bulk){
//                $this->getResponse()->setStatusCode(503);
//                $error_resp = json_encode($error_resp);
//
//            }
//
//            return $this->renderText($error_resp);
//        } // end error response



        $is_xml = stripos($method,"xml");
        if($is_xml===false){

            $opt = $this->getRequestParameter('opt');
            // ex: method=getlvs&opt=tree
            // calls generatelvsTree
            if($opt=='tree'){
                $tam = strlen($method);
                $call_ = substr($method,3,$tam);
                $func_call = 'generate'.$call_.'Tree';
                switch($call_){
                    case 'vgs' : $pvs = $soap->call('getpvs',$params);
                        $return = $this->$func_call($response,$pvs);
                        break;
                    default: $return = $this->$func_call($response);
                    }

                }
                // ex: method=getlvs&opt=q
                // calls generatelvsQuery
                if($opt=='q'){
                    $query = $this->getRequestParameter('query');
                    $tam = strlen($method);

                    $call_ = substr($method,3,$tam);
                    $func_call = 'generate'.$call_.'Query';

                    $return = $this->$func_call($response,$query);
                }

                if($opt=='update'){
                    // $tam = strlen($method);
                    //  $call_ = substr($method,3,$tam);
                    $func_call = 'update_'.$method;

                    $return = $this->$func_call($this->node,$response);


                    //  die($call_);

                    // die($call_);
                    // $ret = $this->generateTree($ret);
                }



                if($opt=='l'){
                    $query = $this->getRequestParameter('q');
                    $tam = strlen($method);

                    $call_ = substr($method,3,$tam);
                    $func_call = 'generate'.$call_.'List';

                    $return = $this->$func_call($response,$query);
                }

//                if(!$bulk){
//                    $return = json_encode($return);
//                    $this->getResponse()->setHttpHeader("X-JSON", '()');
//                }
            }
            else $this->getContext()->getResponse()->setContentType('text/xml;');

         //   if(!$bulk) return $this->renderText($return);
           // else return $return;
           
           
           if($response['success']){
               $return = json_encode($response['response']);

           }
           else{
               $return = json_encode($response['error']);
               $this->getResponse()->setStatusCode(503);
               
           }

           return $this->renderText($return);

        }


        public function generatephydiskTree($list)
        {


            $aux = array();
            foreach ($list as $elem=>$props){
                $children = array();
                foreach ($props as $tag=>$item){
                    $id = $item->device;
                    $qtip = 'Physical volume NOT initialized';
                    $type = $cls = 'dev-pd';

                    $pretty_size = $item->pretty_size;
                    $size = $item->size;




                    $pretty_pvsize = $item->pretty_pvsize;
                    $pvsize = $item->pvsize;


                    if(isset($item->pvinit))
                    if($item->pvinit){ $qtip = 'Physical volume initialized';
                        $type= 'dev-pv';
                        $cls = 'dev-pv';
                    }

                    $children[] = array('id'=>$id,'uiProvider'=>'col','iconCls'=>'task','cls'=>$cls,'text'=>$tag,'size'=>$size,'prettysize'=>$pretty_size,'pvsize'=>$pvsize,'pretty-pvsize'=>$pretty_pvsize, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'leaf'=>true);
                }

                $aux[] = array('id'=>$elem,'uiProvider'=>'col','iconCls'=>'devices-folder','text'=>$elem, 'singleClickExpand'=>true,'children'=>$children);


            }


            //      $local_devs = $list->local;
            //
            //
            //      foreach ($local_devs as $elem=>$props){
            //
            //          $aux_ = array();
            //
            //
            //          $id = $props->device;
            //          $qtip = 'Physical volume NOT initialized';
            //          $type = $cls = 'dev-pd';
            //          $size = $props->size;
            //
            //
            //          $type= 'dev-pv';
            //
            //          if(isset($props->pvinit))
            //              if($props->pvinit){ $qtip = 'Physical volume initialized';
            //                                  $type= 'dev-pv';
            //                                  $cls = 'dev-pv';
            //              }
            //
            //           foreach ($props as $prop){
            //
            //         //  $size = $prop['size'];
            //
            ////          foreach ($props as $prop=>$value){
            ////                        if($prop=='device') $id = $value;
            ////                        if($prop=='pvinit'){
            ////                            $qtip = 'Physical volume initialized';
            ////                            $cls = 'dev-pv';
            ////                            $type= 'dev-pv';
            ////                        }
            ////
            ////                        $aux_[] = array('text'=>$prop,'leaf'=>true);
            ////
            //                        // die($prop);
            //                     //   $aux_[] = array('text'=>$prop,'id'=>$child_id,'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
            //                      //      'leaf'=>true);
            //          }
            //
            //
            //  //  iconCls:'task-folder',
            //$aux[] = array('id'=>$id,'uiProvider'=>'col','iconCls'=>'task','cls'=>$cls,'text'=>$elem,'size'=>$size, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'leaf'=>true);
            //          $aux[] = array('id'=>$id,'cls'=>$cls,'text'=>$elem,'size'=>$size, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'children'=>$aux_);

            //          if(empty($aux_)){
            //              $aux[] = array('text'=>$node->getName(),'id'=>$node->getID(),'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
            //                'children'=>$aux_servers,'expanded'=>true,'cls'=> 'x-tree-node-collapsed');
            //          }else $aux[] = array('text'=>$node->getName(),'id'=>$node->getID(),'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
            //                        'singleClickExpand'=>true,'children'=>$aux_servers);

            // }
            return $aux;

        }



        public function generatevgsTree($list,$pvsl)
        {

            $aux = array();
            foreach ($list as $elem=>$props){

                $aux_ = array();
                $id = '';
                $qtip = '';
                $cls = 'dev-pd';

                $pvs_list = $props->physicalvolumes;
                $pvs_tree = $this->generatepvsTree($pvs_list);

                //          foreach ($props as $prop=>$value){
                //                        if($prop=='vg') $id = $value;
                //                        if($prop=='vsize'){
                //                            $qtip = 'Size:'.$value;
                //                            $cls = 'vg';
                //                        }
                //
                //                        $aux_[] = array('text'=>$prop,'leaf'=>true);
                //
                //
                //                        // die($prop);
                //                     //   $aux_[] = array('text'=>$prop,'id'=>$child_id,'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
                //                      //      'leaf'=>true);
                //          }

                //          $pvs_tree = $this->generatepvsTree($pvsl,$id);

                foreach($pvs_tree as $pvitem){
                    $aux_[] = $pvitem;
                }
                //$aux_[] = array_merge($aux,);
                //  $aux_[] = array('id'=>'toto','cls'=>$cls,'text'=>'elem','singleClickExpand'=>true,'type'=>'vg','qtip'=>$qtip,'children'=>$tt);

                //          $aux[] = array('id'=>$id,'cls'=>$cls,'text'=>$elem,'expanded'=> true,'singleClickExpand'=>true,'type'=>'vg','qtip'=>$qtip,'children'=>$aux_);
                $aux[] = array('id'=>$id,'uiProvider'=>'col','iconCls'=>'task','cls'=>$cls,'text'=>$elem,'size'=>$size, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'leaf'=>true);

            }
            return $aux;

        }


        public function generatepvsTree_old($list,$vg_filter=null)
        {

            $aux = array();
            $aux_it = array();
            foreach ($list as $elem=>$props){

                $aux_ = array();
                $id = '';
                $qtip = '';
                $cls = 'dev-pv';
                $match = 0;
                foreach ($props as $prop=>$value){
                    if($vg_filter)
                    if($prop=='vg' && $value==$vg_filter) $match = 1; // pv belogns to the vg_filter group

                    if($prop=='pv') $id = $value; // setting id
                    if($prop=='psize'){
                        $qtip[] = 'Total size:'.$value;
                    }

                    if($prop=='pfree'){
                        $qtip[] = 'Free size:'.$value;
                    }

                    $aux_[] = array('text'=>$prop,'leaf'=>true);

                    // die($prop);
                    //   $aux_[] = array('text'=>$prop,'id'=>$child_id,'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
                    //      'leaf'=>true);
                }
                $qtip = implode("<br>",$qtip);
                if($vg_filter)
                if($match)
                $aux[] = array('id'=>$id,'cls'=>$cls,'text'=>$elem,'singleClickExpand'=>true,'type'=>'dev-pv','qtip'=>$qtip,'children'=>$aux_);


            }
            //   $tt = array();
            //   $aux_it[0] = array('id'=>'toto','cls'=>$cls,'text'=>'elem','singleClickExpand'=>true,'type'=>'vg','qtip'=>$qtip,'children'=>$tt);
            return $aux;

        }

        public function generatepvsTree($list)
        {

            $aux = array();
            $aux_it = array();
            foreach ($list as $elem=>$props){

                $aux_ = array();
                $id = $props->device;
                $size = $props->size;
                $pretty_size = $props->pretty_size;
                $qtip = '';
                $cls = 'dev-pv';
                $match = 0;
                //          foreach ($props as $prop=>$value){
                //
                //                        if($prop=='pv') $id = $value; // setting id
                //                        if($prop=='psize'){
                //                            $qtip[] = 'Total size:'.$value;
                //                        }
                //
                //                        if($prop=='pfree'){
                //                            $qtip[] = 'Free size:'.$value;
                //                        }
                //
                //                        $aux_[] = array('text'=>$prop,'leaf'=>true);
                //
                //                        // die($prop);
                //                     //   $aux_[] = array('text'=>$prop,'id'=>$child_id,'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
                //                      //      'leaf'=>true);
                //          }
                //   $qtip = implode("<br>",$qtip);

                $aux[] = array('id'=>$id,'cls'=>$cls,'uiProvider'=>'col','iconCls'=>'task','text'=>$elem,'size'=>$size,'prettysize'=>$pretty_size,'singleClickExpand'=>true,'type'=>'dev-pv','qtip'=>$qtip,'leaf'=>true);


            }
            //   $tt = array();
            //   $aux_it[0] = array('id'=>'toto','cls'=>$cls,'text'=>'elem','singleClickExpand'=>true,'type'=>'vg','qtip'=>$qtip,'children'=>$tt);
            return $aux;

        }


        public function generatepvsQuery($list,$query)
        {

            $elements = array();
            foreach ($list as $elem=>$props){

                if(!isset($props->$query))
                $elements[] = array('value'=>$props->device,'name'=>$elem);

            }

            $result = array('total' =>   count($elements),'data'  => $elements);

            return $result;
        }


        public function generatevgsList($list,$query)
        {

            $elements = array();
            foreach ($list as $elem=>$props){
                if($props->$query<>0 ){
                    $size = $props->freesize;
                    // $size = Etva::byte_to_MBconvert($size);
                    $elements[] = array('id'=>$props->vg,'txt'=>$elem,'size'=>$size);
                }
            }

            $result = array('total' =>   count($elements),'data'  => $elements);

            return $result;
        }


        public function generatevgpvsTree($list)
        {

            $aux = array();
            foreach ($list as $elem=>$props){

                $aux_ = array();
                $id = $props->vg;
                $qtip = '';
                $cls = 'vg';
                $size = $props->size;
                $pretty_size = $props->pretty_size;


                $pvsList = $props->physicalvolumes;
                $pvs_tree = $this->generatepvsTree($pvsList);


                //  $aux_[] = array('id'=>'toto','cls'=>$cls,'text'=>'elem','singleClickExpand'=>true,'type'=>'vg','qtip'=>$qtip,'children'=>$tt);

                //          $aux[] = array('id'=>$id,'cls'=>$cls,'text'=>$elem,'expanded'=> true,'singleClickExpand'=>true,'type'=>'vg','qtip'=>$qtip,'children'=>$aux_);
                $aux[] = array('id'=>$id,'uiProvider'=>'col','iconCls'=>'devices-folder','cls'=>$cls,'text'=>$elem,'type'=>'vg','size'=>$size,'prettysize'=>$pretty_size, 'singleClickExpand'=>true,'qtip'=>$qtip,'children'=>$pvs_tree);

            }
            return $aux;

        }


        public function generatelvsTree($list)
        {

            $aux = array();
            $aux_it = array();
            foreach ($list as $elem=>$props){

                $aux_ = array();
                $id = $props->lv;
                $pretty_size = $props->lsize;
                $size = $props->size;
                $vgfreesize = $props->vgfreesize;
                $vg = $props->vg;
                $vg_size = $props->vgsize;
                $cls = 'lv';
                $aux[] = array('id'=>$id,'cls'=>$cls,'uiProvider'=>'col','iconCls'=>'devices-folder','text'=>$elem,'size'=>$size,'prettysize'=>$pretty_size,'vgsize'=>$vg_size,'singleClickExpand'=>true,'type'=>'lv','vg'=>$vg,'vgfreesize'=>$vgfreesize,'leaf'=>true);


            }
            //   $tt = array();
            //   $aux_it[0] = array('id'=>'toto','cls'=>$cls,'text'=>'elem','singleClickExpand'=>true,'type'=>'vg','qtip'=>$qtip,'children'=>$tt);
            return $aux;

        }

        public function generatelvsList($list,$query)
        {

            $elements = array();
            foreach ($list as $elem=>$props){
                if($props->$query<>0 ){
                    $size = $props->size;
                    // $size = Etva::byte_to_MBconvert($size);
                    $elements[] = array('id'=>$props->lvdevice,'txt'=>$elem,'size'=>$size);
                }
            }

            $result = array('total' =>   count($elements),'data'  => $elements);

            return $result;
        }


        public function update_list_vms(EtvaNode $node,$vms){

            //   $this->request->setParameter('uid', $node_uid);
            // $this->request->setParameter('vms',$vms);

            //  $this->executeUpdate($etva_node, $vms);
            // $this->forward('server', 'update');
            $action = sfContext::getInstance()->getController()->getAction('server','update');
            $result = $action->executeUpdate($node,$vms);
            return $result;

        }


        public function executeSoapCreate(sfWebRequest $request)
        {
            if(SF_ENVIRONMENT == 'soap'){

                //           $array = array(
                //                'name'=>'node_name',
                //                'memtotal'=>1,
                //                'cputotal'=>1,
                //                'ip'=>'10.10.20.79',
                //                'port'=>'7001',
                //                'uid'=>'e93927a5-05e4-4ed6-a0bb-8c0268e1096c',
                //                'state'=>1);


                //    $this->request->setParameter('etva_node', $array);



                // Check if already initialized
                $this->form = new EtvaNodeForm();
                $params = $request->getParameter($this->form->getName());
                $uid = $params['uid'];

                //   $request->getParameterHolder()->remove('etva_node');



                $c = new Criteria();
                $c->add(EtvaNodePeer::UID,$uid);

                $etva_node = EtvaNodePeer::doSelectOne($c);
                //$da = array()
                //   $request->setParameter('etva_node', $etva_node->getId());

                if($etva_node){

                    $params['id'] = $etva_node->getId();

                    $request->getParameterHolder()->set($this->form->getName(),$params);
                    $this->form = new EtvaNodeForm($etva_node);
                }else{


                    $uid = EtvaNodePeer::generateUUID();

                    $params['uid'] = $uid;
                    $request->getParameterHolder()->set($this->form->getName(),$params);

                }


                $result = $this->processJsonForm($request, $this->form);

               


                return $result;


            }

        }

        public function executeSoapUpdate(sfWebRequest $request)
        {

            if(SF_ENVIRONMENT == 'soap'){

                $c = new Criteria();
                $c->add(EtvaNodePeer::UID ,$request->getParameter('uid'));


                if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                    $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uid'));
                    $error = array('success'=>false,'error'=>$error_msg);

                    return $error;
                }

                $etva_node->setByName($request->getParameter('field'), $request->getParameter('value'),BasePeer::TYPE_FIELDNAME);
                $etva_node->save();

                $result = array('success'=>true);
                return $result;
            }

        }

/*
 * list all nodes with servers
 */
    public function executeJsonNodeList(sfWebRequest $request)
    {

    

        $nodes = EtvaNodePeer::getWithServers();

        $elements = array();
        foreach ($nodes as $node){
            $node_array = $node->toArray();

         
            foreach ($node->getServers() as $i => $server){

                $server_array = $server->toArray();
                $node_array['servers'][] = $server_array;

            }

            $elements[] = $node_array;


        }

        $result = array('success'=>true,
                    'total'=> count($elements),
                    'response'=> $elements
        );


        $return = json_encode($result);

        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;

        
    }

  /*
 *
 * END SOAP Requests
 *
 */

        protected function processJsonForm(sfWebRequest $request, sfForm $form)
        {
            // die(print_r($request));
            $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));

            // print_r($form);
            // die($form->getName());
            if ($form->isValid())
            {
                //  print_r($etva_node);
                // die(print_r($form));
                $etva_node = $form->save();
                $params = $request->getParameter($this->form->getName());
                $uid = $etva_node->getUid();
                

                //          $request->getParameterHolder()->remove('etva_node');
                //
                //          $request->setParameter('id', $etva_node->getId());
                //          $request->setParameter('method', 'list_vms');
                //
                //
                //          $this->executeGetSoap($request);

                
                
                $result = array('success'=>true,'uuid'=>$uid);
                return $result;

            }
            else{

                $errors = array();
                foreach ($form->getErrorSchema() as $field => $error)
                $errors[$field] = $error->getMessage();
                $result = array('success'=>false,'error'=>$errors);
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
