<?php

class processCheck {

    private $task;

    private $process;
    private $pipes;
    private $node;

    public $max_exec_time;
    public $start_time;
    public $output;
    public $error;
    public $return_value;

    private $cmd;

    //public function processCheck($node,$max_time){
    public function __construct($task,$node,$max_time,$cmd=null){
        $this->task = $task;
        $this->node = $node;
        $this->max_exec_time = $max_time;

        $descriptorspec = array(
                0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
                2 => array("pipe", "w")   // stderr is a file to write to
        );

        $cwd = dirname(__FILE__).'/../../utils';
        $this->cmd = $cmd ? $cmd 
                            : sprintf('/bin/sh check_explicit.sh %s %s %s %s',$this->node->getIp(),$this->node->getPort(),$this->node->getName(),$this->node->getFencingconf_cmd());
        #$this->task->log('[DEBUG] ' . $this->cmd);

        $this->process = proc_open($this->cmd, $descriptorspec, $this->pipes, $cwd);
        $this->start_time = mktime();
    }

    public function isRunning(){
        $status = proc_get_status($this->process);
        return $status['running'];
    }
    public function isTimeout(){
        if( ($this->start_time + $this->max_exec_time) < mktime() ) return true;
        else return false;
    }
    public function get_return_content(){
        fclose($this->pipes[0]);  // dont need input

        $this->output = stream_get_contents($this->pipes[1]);
        fclose($this->pipes[1]);

        $this->error = stream_get_contents($this->pipes[2]);
        fclose($this->pipes[2]);

        $this->return_value = proc_close($this->process);
    }
    public function isChecked(){
        if( preg_match('/CHECK = OK/',$this->output) ){
            return true;
        } else {
            return false;
        }
    }
    public function isFail(){
        //if( $this->isChecked() or $this->return_value == 0 ){
        if( $this->isChecked() ){
            return false;
        } else {
            return true;
        }
    }
    public function do_restart(){
        return new processCheckRestart($this->task,$this->node,1260); # 2 + 3 + 4 + 3 + 6 + 3 = 21 = 1260
    }
    public function do_poweroff(){
        $cluster = $this->node->getEtvaCluster();
        $sparenode = $cluster->getSpareNode();
        if( ($cluster->getAdmissionGateType() != EtvaCluster::CLUSTER_ADMISSION_GATE_TYPE_SPARENODE) ||     // if admission policy don't use spare node
                (!$this->node->getIssparenode() && $sparenode && $sparenode->isNodeFree() && ($sparenode->getState()==EtvaNode::NODE_ACTIVE)) ){   // or have sparenode and still free and active
            return new processCheckPoweroff($this->task,$this->node,360);
        } else {
            $this->node->setState(EtvaNode::NODE_FAIL);
            $this->node->save();
            if( $this->node->getIssparenode() ){ // spare node fail
                $msg = sprintf('Node %s is Spare Node and the process poweroff will not run...',$this->node->getName());
                $this->task->log('[INFO] ' . $msg);
                Etva::makeNotifyLogMessage($this->node->getName(),$msg);
            } else {
                $msg = sprintf('Process poweroff will not run for node %s, because no Spare node free and active configured for migrate VMs...',$this->node->getName());
                $this->task->log('[INFO] ' . $msg);
                Etva::makeNotifyLogMessage($this->node->getName(),$msg);
            }
        }
    }
    public function fail_handle(){
        if( $this->isTimeout() ){
            // do power off
            $msg = sprintf('Node %s process explicit check fail with timeout and it will try force poweroff...',$this->node->getName());
            $this->task->log('[INFO] ' . $msg);
            Etva::makeNotifyLogMessage($this->node->getName(),$msg);
            return $this->do_poweroff();
        } else {
            $msg = sprintf('Node %s process explicit check fail and it will try restart...',$this->node->getName());
            $this->task->log('[INFO] ' . $msg);
            Etva::makeNotifyLogMessage($this->node->getName(),$msg);
            return $this->do_restart();
        }
    }
    public function ok_handle(){
        $etva_node_va = new EtvaNode_VA($this->node);
        $response = $etva_node_va->checkState(EtvaNode::NODE_COMA);

        $msg = sprintf('Node %s process explicit check with success...',$this->node->getName());
        $this->task->log('[INFO] ' . $msg);
        Etva::makeNotifyLogMessage($this->node->getName(),$msg,array(),null,array(),EtvaEventLogger::INFO);

        if( !$response['success'] ){
            // do restart
            return $this->do_restart();
        }
    }
    public function handle(){
        $this->get_return_content();

        if( $this->isFail() or $this->isTimeout() ){
            return $this->fail_handle();
        } else {
            return $this->ok_handle();
        }
    }
    public function getNode(){
        return $this->node;
    }
}
class processCheckQueuing {
    private $task;
    private $queue = array();

    public function __construct($task){
        $this->task = $task;
    }

    public function enqueueObjProcess($obj){
        $this->queue[] = $obj;
    }
    public function enqueueProcess($node,$max_exec_time = 360){
        $this->queue[] =& new processCheck($this->task,$node,$max_exec_time);
    }

    public function loop(){
        while(count($this->queue)){
            foreach($this->queue as $k => $p){
                if( !$p->isRunning() or $p->isTimeout() ){
                    $np = $p->handle();
                    unset($this->queue[$k]);

                    if( $np !== null ) $this->queue[] = $np;
                }
            }
        }
    }
}
class processCheckPoweroff extends processCheck {
    public function __construct($task,$node,$max_time){
        $this->task = $task;
        $this->node = $node;
        $this->max_exec_time = $max_time;
        $this->cmd = sprintf('/bin/sh node_poweroff.sh %s %s %s %s',$node->getIp(),$node->getPort(),$node->getName(),$node->getFencingconf_cmd('off'));
        #$this->task->log('[DEBUG] ' . $this->cmd);

        parent::__construct($this->task,$this->node,$this->max_exec_time,$this->cmd);
    }
    public function do_marknode_asfailed(){
        $this->node->setState(EtvaNode::NODE_FAIL);
        $this->node->save();

        $msg = sprintf('Node %s process poweroff fail and it will marked as failed...',$this->node->getName());
        $this->task->log('[INFO] ' . $msg);
        Etva::makeNotifyLogMessage($this->node->getName(),$msg);
    }
    public function do_migrate_servers(){
        $this->node->setState(EtvaNode::NODE_FAIL);
        $this->node->save();

        $cluster = $this->node->getEtvaCluster();

        if( $cluster->getAdmissionGateType() == EtvaCluster::CLUSTER_ADMISSION_GATE_TYPE_SPARENODE ){     // if admission policy with spare node
            if( $this->node->getIssparenode() ){ // spare node fail
                $msg = sprintf('Node %s process poweroff with success, but is Spare Node and nothing to do for migrate VMs...',$this->node->getName());
                $this->task->log('[INFO] ' . $msg);
                Etva::makeNotifyLogMessage($this->node->getName(),$msg);
            } else {
                $sparenode = $cluster->getSpareNode();
                if( $sparenode && $sparenode->isNodeFree() && ($sparenode->getState()==EtvaNode::NODE_ACTIVE) ){   // have sparenode and still free and active

                    // migrate all servers
                    $node_va = new EtvaNode_VA($this->node);
                    $node_va->migrateAllServers($sparenode,true,true);

                    $msg = sprintf('Node %s process poweroff with success. Next will migrate the VMs to Spare Node...',$this->node->getName());
                    $this->task->log('[INFO] ' . $msg);
                    Etva::makeNotifyLogMessage($this->node->getName(),$msg,array(),null,array(),EtvaEventLogger::INFO);
                } else {
                    $msg = sprintf('Node %s process poweroff with success, but no Spare node free and active configured for migrate VMs...',$this->node->getName());
                    $this->task->log('[INFO] ' . $msg);
                    Etva::makeNotifyLogMessage($this->node->getName(),$msg);
                }
            }
        } else {            // for others policies
            // migrate all servers
            $node_va = new EtvaNode_VA($this->node);
            $node_va->migrateAllServers($sparenode,true,true);

            $msg = sprintf('Node %s process poweroff with success. Next will migrate the VMs...',$this->node->getName());
            $this->task->log('[INFO] ' . $msg);
            Etva::makeNotifyLogMessage($this->node->getName(),$msg,array(),null,array(),EtvaEventLogger::INFO);
        }
    }
    public function fail_handle(){
        // do power off
        $this->do_marknode_asfailed();
        return null;
    }
    public function ok_handle(){
        $this->do_migrate_servers();
        return null;
    }
    public function isChecked(){
        if( preg_match('/POWEROFF = OK/',$this->output) ){
            return true;
        } else {
            return false;
        }
    }
}
class processCheckRestart extends processCheck {
    private $timeoutmaxcount = 3;
    private $timeoutcount;
    public function __construct($task,$node,$max_time){
        $this->timeoutcount = 1;
        $this->task = $task;
        $this->node = $node;
        $this->max_exec_time = $max_time;
        $this->cmd = sprintf('/bin/sh node_restart.sh %s %s %s %s',$node->getIp(),$node->getPort(),$node->getName(),$node->getFencingconf_cmd('reboot'));
        #$this->task->log('[DEBUG] ' . $this->cmd);

        parent::__construct($this->task,$this->node,$this->max_exec_time,$this->cmd);
    }
    public function fail_handle(){
        // do power off
        $msg = sprintf('Node %s process restart fail and it will try force poweroff...',$this->node->getName());
        $this->task->log('[INFO] ' . $msg);
        Etva::makeNotifyLogMessage($this->node->getName(),$msg,array(),null,array(),EtvaEventLogger::INFO);

        return $this->do_poweroff();
    }
    public function ok_handle(){
        $etva_node_va = new EtvaNode_VA($this->node);
        $response = $etva_node_va->checkState();

        $msg = sprintf('Node %s process restart with success. it will wait for node recover and check again...',$this->node->getName());
        $this->task->log('[INFO] ' . $msg);
        Etva::makeNotifyLogMessage($this->node->getName(),$msg,array(),null,array(),EtvaEventLogger::INFO);

        return null;
    }
    public function isTimeout(){
        if( ($this->start_time + ($this->timeoutcount*$this->max_exec_time)) < mktime() ){
            if( $timeoutmaxcount < $this->timeoutcount ){
                $this->timeoutcount++; # inc time cout
                return false;
            } else {
                return true;
            }
        }else{
            return false;
        }
    }
    public function isChecked(){
        if( preg_match('/RESTART = OK/',$this->output) ){
            return true;
        } else {
            return false;
        }
    }
}

/*
 * Task to perform state nodes check
 */
class nodeExplicitnodescheckTask extends etvaBaseTask
{
  /**
    * Overrides default timeout
    **/
    protected function getSigAlarmTimeout(){
        return 3600; // less than 2 * ( 3 + (2 + 3 + 4 + 3 + 6 + 3) + 6 ) = 60 minutes 
    }

    protected function configure()
    {

        parent::configure();
        // // add your own arguments here
        // $this->addArguments(array(
        //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
        // ));

        $this->addOptions(array(
          new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
          new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
          new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
          // add your own options here
        ));

        $this->namespace        = 'node';
        $this->name             = 'explicit-nodes-check';
        $this->briefDescription = 'Send explicit checks to the nodes(virtAgents)';
        $this->detailedDescription = <<<EOF
The [node:check-nodes-keepalive|INFO] task does things.
Call it with:

  [php symfony node:explicit-nodes-check|INFO]
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {
        $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app','dev',true));
        parent::execute($arguments, $options);
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $con = $databaseManager->getDatabase($options['connection'])->getConnection();

        // add your code here

        $this->log('[INFO] Send explicit check to VirtAgents...'."\n");

        $inactive_nodes = EtvaNodeQuery::create()
                                    ->filterByState(EtvaNode::NODE_INACTIVE)
                                    ->find();

        if( count($inactive_nodes) > 0 ){

            $queue = new processCheckQueuing($this);

            foreach($inactive_nodes as $node){

                $cluster = $node->getEtvaCluster();

                // only if cluster has HA
                if( $cluster->getHasNodeHA() ){

                    $message = sprintf('Node %s is inactive and the cluster %s has Node HA configured.',$node->getName(),$cluster->getName());

                    $this->log($message);
                    Etva::makeNotifyLogMessage($this->name,$message);

                    // if fail mark as coma
                    $etva_node_va = new EtvaNode_VA($node);
                    $response = $etva_node_va->checkState(EtvaNode::NODE_COMA);

                    if( !$response['success'] ){
                        $msg_fail = sprintf(' agent %s getstate fail ',$node->getName());
                        $this->log( $msg_fail );
                        Etva::makeNotifyLogMessage($this->name,$msg_fail);

                        $queue->enqueueProcess($node,180);
                    } else {
                        $msg_ok = sprintf(' agent %s getstate with success ',$node->getName());
                        $this->log( $msg_ok );
                        Etva::makeNotifyLogMessage($this->name,$msg_ok,array(),null,array(),EtvaEventLogger::INFO);
                    }
                } else {
                    $msg_noha = sprintf('Node %s is inactive but the cluster %s doesn\'t have Node HA configured.',$node->getName(),$cluster->getName());
                    $this->log($msg_noha);
                }
            }
            $queue->loop();
        } else {
            $this->log('No inactive nodes found!');
        }
    }
}
?>
