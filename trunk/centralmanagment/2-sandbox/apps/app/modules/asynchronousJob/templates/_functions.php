<script type='text/javascript'>
Ext.namespace('AsynchronousJob.Functions');
AsynchronousJob.Functions.Create = function(ns,name,args,opts, success_fh, failure_fh, depends, run_at, no_task_check, abort_at){

    if( !failure_fh ){
        failure_fh = function(resp,opt) {
            var response = Ext.util.JSON.decode(resp.responseText);
            /*Ext.ux.Logger.error(response['agent'],response['response']);*/

            //Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Unable to create new task!')) ?>);
            Ext.Msg.show({
            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
            buttons: Ext.MessageBox.OK,
            msg: String.format(<?php echo json_encode(__('Unable to create new task!')) ?>)+'<br>'+response['info'],
            icon: Ext.MessageBox.ERROR});
        };
    }

    var conn = new Ext.data.Connection({
        listeners:{
            // wait message.....
            beforerequest:function(){
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Please wait...')) ?>,
                    msg: <?php echo json_encode(__('Creating new task...')) ?>,
                    width:300,
                    wait:true
                });
            },// on request complete hide message
            requestcomplete:function(){Ext.MessageBox.hide();}
            ,requestexception:function(c,r,o){
                Ext.MessageBox.hide();
                Ext.Ajax.fireEvent('requestexception',c,r,o);}
        }
    });// end conn

    conn.request({
        url: <?php echo json_encode(url_for('asynchronousJob/new')) ?>,
        params: { 'asynchronousjob': Ext.encode({ 'tasknamespace': ns, 'taskname':name, 'arguments': Ext.encode(args), 'options': Ext.encode(opts), 'depends': depends, 'run_at': run_at, 'abort_at': abort_at })},
        success: function(resp,opt) {
            // TODO
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.info(response['agent'],response['response']);

            Ext.getCmp('tasks-tab-panel').activate('running-task-grid-panel');
            Ext.getCmp('running-task-grid-panel').getStore().reload();
            if( success_fh ) success_fh( resp,opt );

            if( !no_task_check ){
                AsynchronousJob.Functions.CheckStatus(response['asynchronousjob']['Id'],
                                    function(taskObj){
                                        //console.log('check_fh');
                                        //console.log(taskObj);
                                        if( taskObj['asynchronousjob']['Status'] == 'finished' ){
                                            var taskRes = taskObj['asynchronousjob']['Result'];
                                            if( taskRes ){
                                                taskResObj = Ext.util.JSON.decode(taskRes);
                                                if( taskResObj['success'] )
                                                {
                                                    Ext.ux.Logger.info(taskResObj['agent'],taskResObj['response']);
                                                } else {
                                                    Ext.ux.Logger.error(taskResObj['agent'],taskResObj['error']);
                                                }
                                            }
                                            return true;
                                        } else if( taskObj['asynchronousjob']['Status'] == 'aborted' ){
                                            return true;
                                        } else if( taskObj['asynchronousjob']['Status'] == 'invalid' ){
                                            return true;
                                        }
                                        return false;
                                    });
            }

        },
        failure: failure_fh
    });// END Ajax request
};
AsynchronousJob.Functions.Abort = function(params, success_fh, failure_fh){

    if( !failure_fh ){
        failure_fh = function(resp,opt) {
            var response = Ext.util.JSON.decode(resp.responseText);
            /*Ext.ux.Logger.error(response['agent'],response['response']);*/

            //Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Unable to abort task!')) ?>);
            Ext.Msg.show({
            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
            buttons: Ext.MessageBox.OK,
            msg: String.format(<?php echo json_encode(__('Unable to abort task!')) ?>)+'<br>'+response['info'],
            icon: Ext.MessageBox.ERROR});

        };
    }

    var conn = new Ext.data.Connection({
        listeners:{
            // wait message.....
            beforerequest:function(){
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Please wait...')) ?>,
                    msg: <?php echo json_encode(__('Aborting task...')) ?>,
                    width:300,
                    wait:true
                });
            },// on request complete hide message
            requestcomplete:function(){Ext.MessageBox.hide();}
            ,requestexception:function(c,r,o){
                Ext.MessageBox.hide();
                Ext.Ajax.fireEvent('requestexception',c,r,o);}
        }
    });// end conn

    conn.request({
        url: <?php echo json_encode(url_for('asynchronousJob/abort')) ?>,
        params: params,
        success: function(resp,opt) {
            // TODO
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.info(response['agent'],response['response']);

            Ext.getCmp('running-task-grid-panel').getStore().reload();
            if( success_fh ) success_fh( resp,opt );
        },
        failure: failure_fh
    });// END Ajax request
};

AsynchronousJob.Functions.CheckStatus = function(id, check_fh){
    var taskCheckStatus = {
            run: function(){
                var conn = new Ext.data.Connection({
                    /*listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: <?php echo json_encode(__('Aborting task...')) ?>,
                                width:300,
                                wait:true
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                        ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                    }*/
                });// end conn

                conn.request({
                    url: <?php echo json_encode(url_for('asynchronousJob/get')) ?>,
                    params: { 'id': id },
                    success: function(resp,opt) {
                        // TODO
                        var response = Ext.util.JSON.decode(resp.responseText);
                        //console.log(response);
                        if( check_fh ){
                            if( check_fh(response) ){
                                Ext.TaskMgr.stop( taskCheckStatus );
                            }
                        }
                        /*Ext.ux.Logger.info(response['agent'],response['response']);

                        Ext.getCmp('running-task-grid-panel').getStore().reload();
                        if( success_fh ) success_fh( resp,opt );*/
                    },
                    //failure: failure_fh
                });// END Ajax request
            },
            interval: 5*1000, // each 5 seconds
            scope: this
    };
    Ext.TaskMgr.start( taskCheckStatus );
}

</script>
