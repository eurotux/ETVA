<?php
include_partial('server/stats');
?>

<script>
Ext.ns('Node.Stats');

Node.Stats_Load = Ext.extend(mainChartContainer, {
    type:'nodeLoad',
    initComponent:function(){

        this.pre_graph_src = '/node/graph_'+this.type+'Image?'+
                                            'id='+this.node_id;

        Node.Stats_Load.superclass.initComponent.call(this);
        this.on('activate',function(){
            this.doLayout();
            this.loadPreset();});

        this.on('beforeshow',function(){
                    this.loadData(true);
                });
    }
    ,loadData:function(loadPreset){
        
        this.items.get(1).removeAll(true);
        this.addChart();
        this.doLayout();
        if(loadPreset) this.loadPreset();
        //this.doLayout(false,true);



    }
    ,addChart:function(){

        var stp_time = this.step_time;
        var store = new Ext.data.XmlStore({
           // autoLoad: true,
            url: 'node/xportLoadRRA', // automatically configures a HttpProxy
            baseParams:{'id':this.node_id,'graph_start':'-1h'},
            record: 'row', // records will have an "Item" tag
            fields: [{name:'time', mapping:'t'},
                {name:'load1min',mapping:'v0',type:'float'},
                {name:'load5min',mapping:'v1',type:'float'},
                {name:'load15min',mapping:'v2',type:'float'}]
            ,listeners: {
                exception: function(dataProxy, type) {

                    Ext.Msg.show({
                        title: <?php echo json_encode(__('Couldn\'t load panel')) ?>,
                        msg: String.format(<?php echo json_encode(__('Data for the \'{0}\' panel could not be loaded.')) ?>,this.title),
                        width:300,
                        scope:this,
                        //modal:true,
                        //bodyStyle:'background:solid;font-size:15px;',
                        buttons: Ext.MessageBox.OK,
                        fn: function(){
                            this.ownerCt.fireEvent('unLoadPanel',this.type);
                        },
                        icon: Ext.MessageBox.INFO
                    });
                },
                scope: this
            }


        });

        var task = {run:function(){
                chart.store.reload();
            },interval: 5000
        };


        var chart = new Ext.chart.StackedColumnChart({
            xField: 'time',
            height:200,
            border:false,
            store: store,
            emptyText: 'Loading...',


            yAxis: new Ext.chart.NumericAxis({
                stackingEnabled: true,
                title: 'processes in the run queue'
            }),
            series: [{
                    yField: 'load1min',
                    displayName: '1 min avg'
                    ,style: { color: '#eacc00' }
                },{
                    yField: 'load5min',
                    displayName: '5 min avg'
                    ,style: { color: '#ea8f00' }
                },
                {
                    yField: 'load15min',
                    displayName: '15 min avg'
                    ,style: { color: '#ff0000' }
                }],



            xAxis: new Ext.chart.CategoryAxis({

                labelRenderer : function(val){return renderDate(val,stp_time);}

            }), chartStyle: {
                yAxis: {
                    majorGridLines: {
                        size: 1,
                        color:  0xdfe8f6
                    },
                    titleRotation:-90
                }

            },
            extraStyle:
                {
                legend:
                    {
                    display: 'right',
                    padding: 2

                }
            }
        });


        var V = new Ext.ux.plugin.VisibilityMode();

        var pChart = new Ext.Panel({
            title: <?php echo json_encode(__('Node Load')) ?>,
            border:false,
            collapsible:false,
            bodyStyle:'background:white;',
            items:[chart],
            plugins:Ext.isIE ? [] : V,
            task:task,
            tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-appliance-backup',autoLoad:{ params:'mod=node'},title: <?php echo json_encode(__('Node Load Help')) ?>});}}]
            ,bbar:
                [{

                    text:          <?php echo json_encode(__('START / STOP Polling')) ?>,
                    iconCls:       'startPoll',
                    enableToggle:  true,
                    //  allowDepress: false,
                    handler:       function(btn){


                        if(btn.pressed){
                            var delay = new Ext.util.DelayedTask(function(){
                                Ext.TaskMgr.start(task);
                            });
                            delay.delay(5000);
                            View.notify({html:String.format(<?php echo json_encode(__('{0} polling STARTED')) ?>,this.title)});
                        }
                        else{

                            Ext.TaskMgr.stop(task);
                            View.notify({html:String.format(<?php echo json_encode(__('{0} polling STOPPED')) ?>,this.title)});
                        }

                    },scope:this
                },
                '-',
                {text: __('Refresh'),
                    xtype: 'button',
                    tooltip: __('Refresh'),
                    iconCls: 'x-tbar-loading',
                    scope:this,
                    handler: function(button,event)
                    {
                        button.addClass('x-item-disabled');


                        store.reload({
                            callback:function(){button.removeClass('x-item-disabled');}});
                    }
                }

            ]
        });

        this.items.get(1).add(pChart);
        //if(this.isVisible()) store.reload();
    }

});


</script>
