<script>
    var containerId = <?php echo json_encode($containerId); ?>;
    
    // Add the additional 'advanced' VTypes
    Ext.apply(Ext.form.VTypes, {
        daterange : function(val, field) {

            var date = field.parseDate(val);            
            if(!date){
                return false;
            }
            if (field.startDateField && (!this.dateRangeMax || (date.getTime() != this.dateRangeMax.getTime()))) {
                var start = Ext.getCmp(field.startDateField);                
                start.setMaxValue(date);
                this.dateRangeMax = date;
                start.validate();                
            }
            else if (field.endDateField && (!this.dateRangeMin || (date.getTime() != this.dateRangeMin.getTime()))) {
                var end = Ext.getCmp(field.endDateField);
                end.setMinValue(date);
                this.dateRangeMin = date;
                end.validate();                
            }
            /*
             * Always return true since we're only using this vtype to set the
             * min/max allowed values (these are tested for after the vtype test)
             */
            return true;
        }
    });

    //builds graph iframe window
    graphIFrameWindow = function(config) {

        Ext.apply(config, {
            title:'Loading content...',

            //   width         : 600,
            //   height        : 440,
            maximizable   : true,
            collapsible   : true,
            constrain     : true,
            //  layout:'fit',
            shadow        : Ext.isIE,
            animCollapse  : false,
            autoScroll    : true,
            hideMode      : 'nosize',            

            listeners : {
                domready : function(frameEl){  //raised for "same-origin" frames only

                    var MIF = frameEl.ownerCt;
                    View.notify({html:MIF.title+' reports:domready '});
                    //Demo.balloon(null, );
                },
                documentloaded : function(frameEl){
                    var MIF = frameEl.ownerCt;
                    var doc = frameEl.getFrameDocument();
                    var img = doc.getElementsByTagName('img');
                    MIF.setTitle(doc.title);
                    MIF.setWidth(img[0].width+45);
                    MIF.setHeight(img[0].height+80);

                    notify({html:MIF.title+' reports:docloaded '});
                    // Demo.balloon(null, MIF.title+' reports:','docloaded ');
                },
                beforedestroy : function(){
                    
                }
            },

            sourceModule : 'mifsimple'

        });

        Ext.apply(config, {
            tools:[
                {
                    id:'refresh',
                    // text    :'Reload',
                    qtip: 'Home',
                    scope : this,
                    handler: function(button) {
                        this.setSrc();
                    }
                }]});

        // toolbar
        Ext.apply(config, {
            tbar:[
                {
                    text    :'Reload',
                    tooltip : 'Reload the Frame',
                    scope : this,
                    handler: function(button) {
                        this.setSrc();
                    }
                }]});


        Ext.apply(this,config);

        graphIFrameWindow.superclass.constructor.call(this,config);

        //   this.addEvents({add:true});
    }
    Ext.extend(graphIFrameWindow, Ext.ux.ManagedIFrame.Window, {});

    /*
    * Builds date range panel
    *
    */
    drange = function(pFunc) {        

        this.panelID = pFunc.getPanelId();
        this.panelGraph = pFunc.getPanelGraphGen();

        var step_time=0;

        this.combo = new Ext.form.ComboBox({
            //  xtype:'combo',
            fieldLabel: 'Presets',
            name: 'presets',
            store: new Ext.data.ArrayStore({
                id:'type',
                fields: ['type', 'name'],
                data : [
                    ['last_day','Last day'],
                    ['last_h','Last hour'],
                    ['last_2h','Last 2 hour'],
                    ['last_week','Last week']]
            }),
            displayField:'name',
            valueField:'type',
            typeAhead:true,
            value:'last_h',
            mode:'local',
            forceSelection: true,
            triggerAction: 'all',
            //    emptyText:'Choose...',
            selectOnFocus:true,
            anchor:'80%',
            listeners:{
                select:function(cb,record){



                    var cur_date = new Date();
                    var cur_month = cur_date.getMonth();
                    var cur_year = cur_date.getFullYear();
                    var cur_day = cur_date.getDate();


                    switch(record.data.type)
                    {
                        case 'last_h':
                            step_time = 300;
                            pFunc.reloadStore(record.data.name,'-1h','',step_time);
                            break;
                        case 'last_2h':
                            step_time = 300;
                            pFunc.reloadStore(record.data.name,'-2h','',step_time);
                            break;
                        case 'last_day':
                            step_time = 3600;
                            pFunc.reloadStore(record.data.name,'-1d','',step_time);
                            break;
                        case 'cur_day':
                            step_time = 300;
                            pFunc.reloadStore(record.data.name,'-2h','',step_time);
                            break;
                        case 'last_week':
                            step_time = 21600; // 6h interval
                            //step_time = 300;
                            pFunc.reloadStore(record.data.name,'-1w','',step_time);
                            break;
                        case 'cur_week':
                            // step_time = 86400;
                            step_time = 21600; // interval 6h
                            var date_start=new Date(cur_year,cur_month,cur_day);
                            var date_end = new Date(cur_year,cur_month,cur_day);

                            var dow=date_start.getDay();
                            var diffdow = 6 - dow;
                            date_start.setDate(date_start.getDate()-dow);
                            date_end.setDate(date_end.getDate()+diffdow);

                            var start_unix_time = parseInt(date_start.getTime().toString().substring(0, 10));
                            var end_unix_time = parseInt(date_end.getTime().toString().substring(0, 10));

                            pFunc.reloadStore(record.data.name,start_unix_time,end_unix_time,step_time);
                            break;

                        default:break;
                    }




                    if(record.data.type=='month'){

                        var date_start = new Date(cur_year,cur_month);


                        var m_total_days = 32 - new Date(cur_year,cur_month,32).getDate();
                        var date_end = new Date(cur_year,cur_month,m_total_days,23,59,59);

                        var start_unix_time = parseInt(date_start.getTime().toString().substring(0, 10));
                        alert(start_unix_time);
                        var end_unix_time = parseInt(date_end.getTime().toString().substring(0, 10));
                        //var new_date = new Date(string);
                        //  alert(end_unix_time);
                        //alert(Date.UTC(2005,7,8));
                        //alert(new_date.getTime());
                        step_time = 604800;

                        this.reloadStore(start_unix_time,end_unix_time,step_time);

                    }

                },scope:this

            }
        });

        var config = {

            labelAlign: 'right',
            labelWidth: 35,
            defaults: {width: 580},
            bodyStyle:'padding:5px 0 0 0',
            items: [{
                    layout:'column',
                    layoutConfig: {fitHeight: true},
                    items:[
                        {

                            columnWidth:.4,
                            layout: 'form',
                            items: [this.combo]
                        },
                        {
                            columnWidth:.3,
                            layout: 'form',
                            items: [{
                                    xtype:'datefield',
                                    fieldLabel: 'From',
                                    name: 'startdt',
                                    msgTarget:'none',
                                    allowBlank:false,
                                    format:'d/m/Y',
                                    id: this.panelID+'_startdt',
                                    vtype: 'daterange',
                                    anchor:'95%',
                                    endDateField: this.panelID+'_enddt' // id of the end date field
                                }]
                        },{
                            columnWidth:.3,
                            layout: 'form',
                            items: [{
                                    xtype:'datefield',
                                    fieldLabel: 'To',
                                    name: 'enddt',
                                    msgTarget:'none',
                                    allowBlank:false,
                                    format:'d/m/Y',
                                    id: this.panelID+'_enddt',
                                    anchor:'95%',
                                    vtype: 'daterange',
                                    startDateField: this.panelID+'_startdt' // id of the start date field
                                }]
                        },
                        {

                            layout: 'form',
                            items: [{
                                    xtype:'button',
                                    text:'Generate graph image',
                                    handler:function(){
                                        if(this.form.isValid()){

                                            var startdate = this.form.findField('startdt').getValue();
                                            var enddate = this.form.findField('enddt').getValue();


                                            var start_unix_time = startdate.format('U')-3600;
                                            var end_unix_time = enddate.format('U')-3600;



                                            var src =  '/server/graph_'+this.panelGraph+'Image?id=<?php echo $server_id ?>'+

                                                '&graph_start='+start_unix_time+
                                                '&graph_end='+end_unix_time;
                                           
                                            var graph_ifr =  new graphIFrameWindow({defaultSrc:src});
                                            graph_ifr.show();


                                        }
                                    },
                                    scope:this
                                }]
                        }
                    ]
                }]


        };

        Ext.apply(this,config);
        this.step_time = step_time;
        drange.superclass.constructor.call(this,config);

    }
    Ext.extend(drange, Ext.FormPanel, {});

    mainChartContainer = function(pFunc) {

        this.dr =  new drange(pFunc);
        
        this.title = pFunc.getPanelName();



        var config = {

            layout:'border',
            step_time:20,
            title:this.title,
            defaults: {
                collapsible: true,
                border:false,
                split: true,
                autoScroll:true
                ,bodyStyle: 'padding:0px'
            },
            items: [{
                    title: 'Date range',
                    region: 'north',
                    frame:true,
                    autoScroll:false,
                    height: 72,
                    items:[this.dr],
                    margins: '5 5 0 5',
                    cmargins: '5 5 5 5'
                }
                ,{
                    collapsible: false,
                    region:'center',
                    frame:true,
                    // layout:'accordion',
                    margins: '0 5 5 5'
                }]




        };

        Ext.apply(this,config);

        mainChartContainer.superclass.constructor.call(this,config);

    }
    Ext.extend(mainChartContainer, Ext.Panel, {});

    // render date format
    renderDate = function(t,step_time) {
        if (t) {
            var newDate = new Date();
            t = parseInt(t);
            newDate.setTime(t*1000);
            // return newDate;
            t = parseInt(t);
            var dtDate = newDate;
            
            if(step_time){
                if(step_time === 86400){
                    // week view. step_time set to 1 day
                    return Ext.util.Format.date(dtDate, 'd/m/y');
                }
            }

            var dt_hi = Ext.util.Format.date(dtDate, '    H:i  ');
            var dt_ym = Ext.util.Format.date(dtDate, '  d/m/y  ');
            return dt_hi+'\n'+dt_ym;
            return Ext.util.Format.date(dtDate, 'H:i '+'\n'+' d/m/y');


        }
    };







    Ext.ns('Stats');
    Stats.Network = function(){
        return{
            init:function(server_id){

                this.serverId = server_id;
                var networks = [];

<?php foreach($networks as $network): ?>
                var network = new Object();
                network.Id = '<?php echo $network->getId()?>';
                network.Target = '<?php echo $network->getTarget()?>';
                networks.push(network);
<?php endforeach;?>



                this.networkPanel =  new mainChartContainer(Stats.Network);

                for (var i = 0, len = networks.length; i < len; i++)
                {


                    this.addChart(networks[i]);
                }

                return this.networkPanel;
            }
            ,addChart:function(network){

                var stp_time = this.networkPanel.step_time;

                var store = new Ext.data.XmlStore({                  
                    url: 'network/xportRRA?id='+network.Id, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'input',mapping:'v0',type:'float'},
                        {name:'output',mapping:'v1',type:'float'}]

                });





                var task = {run:function(){
                        chart.store.reload();
                    },interval: 5000
                };



                var chart = new Ext.chart.LineChart({

                    xField: 'time',
                    height:200,
                    border:false,
                    store: store,
                    emptyText: 'Loading...',

                    series: [{
                            yField: 'input',
                            displayName: 'Input'

                        },{
                            yField: 'output',
                            displayName: 'Output'

                        }
                    ],
                    xAxis :  new Ext.chart.CategoryAxis({
                        labelRenderer : function(val){return renderDate(val,stp_time);}
                        
                    }),
                    yAxis: new Ext.chart.NumericAxis({
                        title: 'B/s',
                        labelRenderer : Ext.util.Format.numberRenderer('0,0')
                    }),
                    //scope:this,
                    chartStyle: {
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



                var pChart = new Ext.Panel({
                    title:'Interface '+network.Target+' - Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Network pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Network pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){                      
                    
                    store.reload();

                   
                    }
                    }
                });

                this.networkPanel.get(1).add(pChart);


            },
            reloadStore:function(time_span,start_unix_time,end_unix_time,step){


                var pCharts = this.networkPanel.get(1);

                if(step=== undefined) step = '';

                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                {

                    var itemP = pCharts.items.get(i);
                    var current_title = itemP.title;
                    current_title = current_title.replace(/ -.*/, "");
                    itemP.setTitle(current_title+' - '+time_span);

                    itemChart = itemP.items.get(0);
                    itemChart.store.reload({params:{graph_start:start_unix_time,
                            graph_end:end_unix_time,step:step}});


                }

            },
            getPanelId:function(){
                return 'server-stats-network-'+containerId;
            },
            getPanelGraphGen:function(){
                return 'network';
            },
            getPanelName:function(){
                return 'Network';
            }


        }
    }();



    Stats.Cpu_per = function(){
        return{
            init:function(server_id){

                this.serverId = server_id;



                this.cpuPanel =  new mainChartContainer(Stats.Cpu_per);

                this.addChart();


                return this.cpuPanel;
            }
            ,addChart:function(){

                var stp_time = this.cpuPanel.step_time;
                
                var store = new Ext.data.XmlStore({
                  //  autoLoad: true,
                    url: 'server/xportCpu_perRRA?id='+this.serverId, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'cpu_per',mapping:'v0',type:'float'}
                    ]

                });




                var task = {run:function(){
                        chart.store.reload();
                    },interval: 5000
                };



                var chart = new Ext.chart.ColumnChart({

                    xField: 'time',
                    yField: 'cpu_per',
                    height:200,
                    border:false,
                    store: store,
                    emptyText: 'Loading...',
                    xAxis: new Ext.chart.CategoryAxis({
                        labelRenderer : function(val){return renderDate(val,stp_time);}
                    }),
                    yAxis: new Ext.chart.NumericAxis({
                        title: '%'                        
                    }),
                    chartStyle: {
                        yAxis: {
                            majorGridLines: {
                                size: 1,
                                color:  0xdfe8f6
                            },
                            titleRotation:-90
                        }

                    }

                });

                var pChart = new Ext.Panel({
                    title:'CPU % '+'- Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'CPU pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'CPU pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.cpuPanel.get(1).add(pChart);


            },
            reloadStore:function(time_span,start_unix_time,end_unix_time,step){


                var pCharts = this.cpuPanel.get(1);

                if(step=== undefined) step = '';

                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                {

                    var itemP = pCharts.items.get(i);
                    var current_title = itemP.title;
                    current_title = current_title.replace(/ -.*/, "");
                    itemP.setTitle(current_title+' - '+time_span);

                    itemChart = itemP.items.get(0);
                    itemChart.store.reload({params:{graph_start:start_unix_time,
                            graph_end:end_unix_time,step:step}});


                }

            },
            getPanelId:function(){
                return 'server-stats-cpu_per-'+containerId;
            },
            getPanelGraphGen:function(){
                return 'cpu_per';
            },
            getPanelName:function(){
                return 'Cpu Usage';
            }

        }
    }();




    Stats.Memory = function(){
        return{
            init:function(server_id){

                this.serverId = server_id;



                this.memPanel =  new mainChartContainer(Stats.Memory);

                this.addMem_perChart();
                this.addMem_usageChart();


                return this.memPanel;
            }
            ,addMem_perChart:function(){

                var stp_time = this.memPanel.step_time;
                var store = new Ext.data.XmlStore({
                   // autoLoad: true,
                    url: 'server/xportMem_perRRA?id='+this.serverId, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'mem_per',mapping:'v0',type:'float'}]

                });




                var task = {run:function(){
                        chart.store.reload();
                    },interval: 5000
                };



                var chart = new Ext.chart.ColumnChart({

                    xField: 'time',
                    yField: 'mem_per',
                    height:200,
                    border:false,
                    store: store,
                    emptyText: 'Loading...',
                    xAxis: new Ext.chart.CategoryAxis({
                        labelRenderer : function(val){return renderDate(val,stp_time);}
                    }),
                    yAxis: new Ext.chart.NumericAxis({
                        title: '%'
                    }),
                    chartStyle: {
                        yAxis: {
                            majorGridLines: {
                                size: 1,
                                color:  0xdfe8f6
                            },
                            titleRotation:-90
                        }

                    }

                });

                var pChart = new Ext.Panel({
                    title:'Mem % '+'- Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Mem pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Mem pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.memPanel.get(1).add(pChart);


            }
            ,addMem_usageChart:function(){

                var stp_time = this.memPanel.step_time;
                var store = new Ext.data.XmlStore({
                   // autoLoad: true,
                    url: 'server/xportMem_usageRRA?id='+this.serverId, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'mem_m',mapping:'v0',type:'float'},
                        {name:'mem_v',mapping:'v1',type:'float'}]

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
                        title: 'Bytes',
                        labelRenderer : Ext.util.Format.numberRenderer('0,0')
                    }),
                    series: [{
                            yField: 'mem_v',
                            displayName: 'Available memory'
                        },{
                            yField: 'mem_m',
                            displayName: 'Total system memory'
                        }],
                    chartStyle:{
                        yAxis: {

                            titleRotation:-90
                        }
                    },


                    xAxis: new Ext.chart.CategoryAxis({

                        labelRenderer : function(val){return renderDate(val,stp_time);}

                    }),
                    extraStyle:
                        {
                        legend:
                            {
                            display: 'right',
                            padding: 2

                        }
                    }


                });


                var pChart = new Ext.Panel({
                    title:'Mem usage '+'- Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Mem pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Mem pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.memPanel.get(1).add(pChart);


            },
            reloadStore:function(time_span,start_unix_time,end_unix_time,step){


                var pCharts = this.memPanel.get(1);

                if(step=== undefined) step = '';

                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                {

                    var itemP = pCharts.items.get(i);
                    var current_title = itemP.title;
                    current_title = current_title.replace(/ -.*/, "");
                    itemP.setTitle(current_title+' - '+time_span);

                    itemChart = itemP.items.get(0);
                    itemChart.store.reload({params:{graph_start:start_unix_time,
                            graph_end:end_unix_time,step:step}});


                }

            },
            getPanelId:function(){
                return 'server-stats-mem-'+containerId;
            },
            getPanelGraphGen:function(){
                return 'mem';
            },
            getPanelName:function(){
                return 'Memory Usage';
            }

        }
    }();




    Stats.MemoryUsage = function(){
        return{
            init:function(server_id){

                this.serverId = server_id;



                this.memPanel =  new mainChartContainer(Stats.Memory);

                this.addMem_usageChart();


                return this.memPanel;
            }
            ,addMem_usageChart:function(){


                var store = new Ext.data.XmlStore({
                   // autoLoad: true,
                    url: 'server/xportMem_usageRRA?id='+this.serverId, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'mem_b',mapping:'v0',type:'float'},
                        {name:'mem_s',mapping:'v1',type:'float'}]

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
                        title: 'Bytes',
                        labelRenderer : Ext.util.Format.numberRenderer('0,0')
                    }),
                    series: [{
                            yField: 'mem_b',
                            displayName: 'Buffer size'
                        },{
                            yField: 'mem_s',
                            displayName: 'Swap size'
                        }],
                    chartStyle:{
                        yAxis: {

                            titleRotation:-90
                        }
                    },


                    xAxis: new Ext.chart.CategoryAxis({

                        labelRenderer : function(val){return renderDate(val,stp_time);}

                    }),
                    extraStyle:
                        {
                        legend:
                            {
                            display: 'right',
                            padding: 2

                        }
                    }


                });







                var pChart = new Ext.Panel({
                    title:'Mem usage '+'- Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Mem pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Mem pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.memPanel.get(1).add(pChart);


            },
            reloadStore:function(time_span,start_unix_time,end_unix_time,step){


                var pCharts = this.memPanel.get(1);

                if(step=== undefined) step = '';

                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                {

                    var itemP = pCharts.items.get(i);
                    var current_title = itemP.title;
                    current_title = current_title.replace(/ -.*/, "");
                    itemP.setTitle(current_title+' - '+time_span);

                    itemChart = itemP.items.get(0);
                    itemChart.store.reload({params:{graph_start:start_unix_time,
                            graph_end:end_unix_time,step:step}});


                }

            },
            getPanelId:function(){
                return 'server-stats-mem_usage-'+containerId;
            },
            getPanelGraphGen:function(){
                return 'mem_usage';
            },
            getPanelName:function(){
                return 'Memory Usage';
            }

        }
    }();





    Stats.Disk_rw = function(){
        return{
            init:function(server_id){

                this.serverId = server_id;


                this.diskPanel =  new mainChartContainer(Stats.Disk_rw);

                var disk = new Object();
                disk.Id = '<?php echo $lv->getId()?>';
                disk.Target = '<?php echo $lv->getTarget()?>';

                this.addChart(disk);


                return this.diskPanel;
            }
            ,addChart:function(disk){

                var stp_time = this.diskPanel.step_time;

                var store = new Ext.data.XmlStore({
                  //  autoLoad: true,
                    url: 'logicalvol/xportDiskRWRRA?id='+disk.Id, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'reads',mapping:'v0',type:'float'},
                        {name:'writes',mapping:'v1',type:'float'}],
                    listeners: {
                        exception: function(dataProxy, action, rs, params) {
                            Ext.MessageBox.alert(
                            'Couldn\'t load panel',
                            'Data for the \'disk\' panel could not be loaded. Maybe it doesn\'t exist?',
                            function() {
                                Stats.Server.unloadPanel('disk');
                            },
                            this
                        );
                        },
                        scope: this
                    }
                });




                var task = {run:function(){
                        chart.store.reload();
                    },interval: 5000
                };


                var chart = new Ext.chart.ColumnChart({
                    // xtype: 'columnchart',
                    store: store,
                    height:200,
                    //width:800,
                    //  url:'../../resources/charts.swf',
                    xField: 'time',
                    // xAxis: new Ext.chart.TimeAxis({
                    xAxis: new Ext.chart.CategoryAxis({
                        title: 'Today - Last two hours'
                        //      ,snapToUnits: true
                        //   ,minorTimeUnit:'day'
                        //    ,majorUnit:400
                        // ,minorUnit:500
                        ,labelRenderer : function(val){return renderDate(val,stp_time);}
                    }),
                    yAxis: new Ext.chart.NumericAxis({
                        title: 'Disk Access',
                        snapToUnits: true,
                        labelRenderer : Ext.util.Format.numberRenderer('0,0')
                    }),
                    tipRenderer : function(chart, record, index, series){
                        if(series.yField == 'reads'){
                            return Ext.util.Format.number(record.data.reads, '0,0') + ' reads in ' + record.data.time;
                        }else{
                            return Ext.util.Format.number(record.data.writes, '0,0') + ' writes in ' + record.data.time;
                        }
                    },
                    chartStyle: {
                        // padding: 10,
                        animationEnabled: true,
                        font: {
                            name: 'Tahoma',
                            color: 0x444444,
                            size: 11
                        },
                        dataTip: {
                            padding: 5,
                            border: {
                                color: 0x99bbe8,
                                size:1
                            },
                            background: {
                                color: 0xDAE7F6,
                                alpha: .9
                            },
                            font: {
                                name: 'Tahoma',
                                color: 0x15428B,
                                size: 10,
                                bold: true
                            }
                        },
                        xAxis: {
                            color: 0x69aBc8
                            ,majorTicks: {color: 0x69aBc8, length: 4},
                            minorTicks: {color: 0x69aBc8, length: 2},
                            majorGridLines: {size: 1, color: 0xeeeeee}
                        },
                        yAxis: {
                            color: 0x69aBc8
                            ,majorTicks: {color: 0x69aBc8, length: 4},
                            minorTicks: {color: 0x69aBc8, length: 2},
                            majorGridLines: {size: 1, color: 0xdfe8f6}
                            ,titleRotation:-90
                        }
                    },
                    series: [{
                            type: 'column',
                            displayName: 'Writes',
                            yField: 'writes',
                            style: {
                                //image:'bar.gif',
                                mode: 'stretch',
                                color:0x99BBE8
                            }
                        },{
                            type:'line',
                            displayName: 'Reads',
                            yField: 'reads',
                            style: {
                                color: 0x15428B
                            }
                        }],
                    extraStyle:
                        {
                        legend:
                            {
                            display: 'right',
                            padding: 2

                        }
                    }
                });






                var pChart = new Ext.Panel({
                    title:'Disk '+disk.Target+' - Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Disk pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Disk pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.diskPanel.get(1).add(pChart);


            },
            reloadStore:function(time_span,start_unix_time,end_unix_time,step){


                var pCharts = this.diskPanel.get(1);

                if(step=== undefined) step = '';

                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                {

                    var itemP = pCharts.items.get(i);
                    var current_title = itemP.title;
                    current_title = current_title.replace(/ -.*/, "");
                    itemP.setTitle(current_title+' - '+time_span);

                    itemChart = itemP.items.get(0);
                    itemChart.store.reload({params:{graph_start:start_unix_time,
                            graph_end:end_unix_time,step:step}});


                }

            },
            getPanelId:function(){
                return 'server-stats-disk_rw-'+containerId;
            },
            getPanelGraphGen:function(){
                return 'disk_rw';
            },
            getPanelName:function(){
                return 'Disk R/W';
            }

        }
    }();





    Stats.Disk = function(){
        return{
            init:function(server_id){

                this.serverId = server_id;


                this.diskPanel =  new mainChartContainer(Stats.Disk);

                var disk = new Object();
                disk.Id = '<?php echo $lv->getId()?>';
                disk.Target = '<?php echo $lv->getTarget()?>';

                this.addDiskSpace(disk);
                this.addDiskRW(disk);

                //var disk_rw = Stats.Disk_rw.init(this.serverId);


                //this.diskPanel.get(1).add(disk_rw);
                // this.maintabs.add(disk);


                return this.diskPanel;
            }
            ,addDiskSpace:function(disk){

                var stp_time = this.diskPanel.step_time;
                var store = new Ext.data.XmlStore({
                  //  autoLoad: true,
                    url: 'logicalvol/xportDiskSpaceRRA?id='+disk.Id, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'size',mapping:'v0',type:'float'},
                        {name:'freesize',mapping:'v1',type:'float'}]


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
                        title: 'Bytes',
                        labelRenderer : Ext.util.Format.numberRenderer('0,0')
                    }),
                    series: [{
                            yField: 'freesize',
                            displayName: 'Free size'
                        },{
                            yField: 'size',
                            displayName: 'Total size'
                        }],
                    chartStyle:{
                        yAxis: {

                            titleRotation:-90
                        }
                    },


                    xAxis: new Ext.chart.CategoryAxis({

                        labelRenderer : function(val){return renderDate(val,stp_time);}

                    }),
                    extraStyle:
                        {
                        legend:
                            {
                            display: 'right',
                            padding: 2

                        }
                    }


                });




                var pChart = new Ext.Panel({
                    title:'Disk '+disk.Target+' :: size - Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Disk pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Disk pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.diskPanel.get(1).add(pChart);


            }
            ,addDiskRW:function(disk){


                var stp_time = this.diskPanel.step_time;

                var store = new Ext.data.XmlStore({
                   // autoLoad: true,
                    url: 'logicalvol/xportDiskRWRRA?id='+disk.Id, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'reads',mapping:'v0',type:'float'},
                        {name:'writes',mapping:'v1',type:'float'}],
                    listeners: {
                        exception: function(dataProxy, action, rs, params) {
                            Ext.MessageBox.alert(
                            'Couldn\'t load panel',
                            'Data for the \'disk\' panel could not be loaded. Maybe it doesn\'t exist?',
                            function() {
                                Stats.Server.unloadPanel('disk');
                            },
                            this
                        );
                        },
                        scope: this
                    }
                });




                var task = {run:function(){
                        chart.store.reload();
                    },interval: 5000
                };


                var chart = new Ext.chart.ColumnChart({
                    // xtype: 'columnchart',
                    store: store,
                    height:200,
                    //width:800,
                    //  url:'../../resources/charts.swf',
                    xField: 'time',
                    // xAxis: new Ext.chart.TimeAxis({
                    xAxis: new Ext.chart.CategoryAxis({
                        labelRenderer : function(val){return renderDate(val,stp_time);}
                    }),
                    yAxis: new Ext.chart.NumericAxis({
                        title: 'Disk Access',
                        snapToUnits: true,
                        labelRenderer : Ext.util.Format.numberRenderer('0,0')
                    }),
                    tipRenderer : function(chart, record, index, series){
                        if(series.yField == 'reads'){
                            return Ext.util.Format.number(record.data.reads, '0,0') + ' reads in ' + record.data.time;
                        }else{
                            return Ext.util.Format.number(record.data.writes, '0,0') + ' writes in ' + record.data.time;
                        }
                    },
                    chartStyle: {
                        // padding: 10,
                        animationEnabled: true,
                        font: {
                            name: 'Tahoma',
                            color: 0x444444,
                            size: 11
                        },
                        dataTip: {
                            padding: 5,
                            border: {
                                color: 0x99bbe8,
                                size:1
                            },
                            background: {
                                color: 0xDAE7F6,
                                alpha: .9
                            },
                            font: {
                                name: 'Tahoma',
                                color: 0x15428B,
                                size: 10,
                                bold: true
                            }
                        },
                        xAxis: {
                            color: 0x69aBc8
                            ,majorTicks: {color: 0x69aBc8, length: 4},
                            minorTicks: {color: 0x69aBc8, length: 2},
                            majorGridLines: {size: 1, color: 0xeeeeee}
                        },
                        yAxis: {
                            color: 0x69aBc8
                            ,majorTicks: {color: 0x69aBc8, length: 4},
                            minorTicks: {color: 0x69aBc8, length: 2},
                            majorGridLines: {size: 1, color: 0xdfe8f6}
                            ,titleRotation:-90
                        }
                    },
                    series: [{
                            type: 'column',
                            displayName: 'Writes',
                            yField: 'writes',
                            style: {
                                //image:'bar.gif',
                                mode: 'stretch',
                                color:0x99BBE8
                            }
                        },{
                            type:'line',
                            displayName: 'Reads',
                            yField: 'reads',
                            style: {
                                color: 0x15428B
                            }
                        }],
                    extraStyle:
                        {
                        legend:
                            {
                            display: 'right',
                            padding: 2

                        }
                    }
                });






                var pChart = new Ext.Panel({
                    title:'Disk '+disk.Target+' :: access - Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Disk pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Disk pool successfully STOP'});
                                }

                            },scope:this
                        },
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.diskPanel.get(1).add(pChart);


            },
            reloadStore:function(time_span,start_unix_time,end_unix_time,step){


                var pCharts = this.diskPanel.get(1);

                if(step=== undefined) step = '';

                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                {

                    var itemP = pCharts.items.get(i);
                    var current_title = itemP.title;
                    current_title = current_title.replace(/ -.*/, "");
                    itemP.setTitle(current_title+' - '+time_span);

                    itemChart = itemP.items.get(0);
                    itemChart.store.reload({params:{graph_start:start_unix_time,
                            graph_end:end_unix_time,step:step}});


                }

            },
            getPanelId:function(){
                return 'server-stats-disk-'+containerId;
            },
            getPanelGraphGen:function(){
                return 'disk';
            },
            getPanelName:function(){
                return 'Disk';
            }

        }
    }();



    Stats.NodeLoad = function(){
        return{
            init:function(server_id){

                this.serverId = server_id;
                this.nodeId = <?php echo $node_id ?>;


                this.loadPanel =  new mainChartContainer(Stats.NodeLoad);

                this.addChart();


                return this.loadPanel;
            }
            ,addChart:function(){

                var stp_time = this.loadPanel.step_time;
                var store = new Ext.data.XmlStore({
                   // autoLoad: true,
                    url: 'node/xportLoadRRA?id='+this.nodeId, // automatically configures a HttpProxy
                    baseParams:{'graph_start':'-1h'},
                    record: 'row', // records will have an "Item" tag
                    fields: [{name:'time', mapping:'t'},
                        {name:'load1min',mapping:'v0',type:'float'},
                        {name:'load5min',mapping:'v1',type:'float'},
                        {name:'load15min',mapping:'v2',type:'float'}]


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
                        },{
                            yField: 'load5min',
                            displayName: '5 min avg'
                        },
                        {
                            yField: 'load15min',
                            displayName: '15 min avg'

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




                var pChart = new Ext.Panel({
                    title:'Disk  - Last hour',
                    border:false,
                    collapsible:true,
                    bodyStyle:'background:white;',
                    items:[chart],
                    bbar:
                        [{

                            text:          'START / STOP Polling',
                            iconCls:       'startPoll',
                            enableToggle:  true,
                            //  allowDepress: false,
                            handler:       function(btn){


                                if(btn.pressed){
                                    var delay = new Ext.util.DelayedTask(function(){
                                        Ext.TaskMgr.start(task);
                                    });
                                    delay.delay(5000);

                                    notify({html:'Disk pool successfully START'});
                                }
                                else{

                                    Ext.TaskMgr.stop(task);

                                    notify({html:'Disk pool successfully STOP'});
                                }

                            },scope:this
                        },
                        '-',
                        {text: 'Reload',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                    ],
                    listeners:{
                    render:function(){
                    store.reload();


                    }
                    }
                });

                this.loadPanel.get(1).add(pChart);


            },
            reloadStore:function(time_span,start_unix_time,end_unix_time,step){


                var pCharts = this.loadPanel.get(1);

                if(step=== undefined) step = '';

                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                {

                    var itemP = pCharts.items.get(i);
                    var current_title = itemP.title;
                    current_title = current_title.replace(/ -.*/, "");
                    itemP.setTitle(current_title+' - '+time_span);

                    itemChart = itemP.items.get(0);
                    itemChart.store.reload({params:{graph_start:start_unix_time,
                            graph_end:end_unix_time,step:step}});


                }

            },
            getPanelId:function(){
                return 'server-stats-nodeLoad-'+containerId;
            },
            getPanelGraphGen:function(){
                return 'nodeLoad';
            },
            getPanelName:function(){
                return 'Node Load';
            }

        }
    }();



    Stats.Server = function(){


        return{
            init:function(){

                this.server_id = <?php echo $server_id ?>;

                this.panels = new Ext.util.MixedCollection();

                this.maintabs = new Ext.TabPanel({
                    activeTab:0,
                    //  region:'center',
                    border:false

                });

                this.addNetworks();
                this.addCpu_per();
                this.addMem();
                this.addDisk();
              //  this.addMemUsage();
                //  this.addDisk_rw();
                this.addNodeLoad();

                this.mainview = new Ext.Panel({
                    title:'Statistics',
                    border:false,
                    //  layout:'border',
                    layout:'fit',
                    items:[this.maintabs]
                    //                    listeners:{
                    //                        activate:function(){
                    //                            var cur_tab = this.maintabs.getActiveTab();
                    //                            alert(cur_tab);
                    //
                    //
                    //                            var pCharts = cur_tab.get(1);
                    //
                    //
                    //
                    //                for (var i = 0, len = (pCharts.items).length; i < len; i++)
                    //                {
                    //                    alert('1');
                    //
                    //                    var itemP = pCharts.items.get(i);
                    ////                    var current_title = itemP.title;
                    ////                    if(step_time === 86400) itemP.setTitle(current_title+' - Week view');
                    ////
                    //                    itemChart = itemP.items.get(0);
                    //                    itemChart.store.reload();
                    //
                    //
                    //                }
                    //
                    //                            alert('active');
                    //
                    //                        },
                    //                        scope:this
                    //                    }

                });


this.mainview.on('stopAllPolls',function(){
    Ext.TaskMgr.stopAll();
});


                return this.mainview;
            }
            ,addNetworks:function(){
                var network = Stats.Network.init(this.server_id);
                this.maintabs.add(network);

                this.panels.add('network', {
                    chartPanel: network
                });

            }
            ,addCpu_per:function(){
                var cpu_per = Stats.Cpu_per.init(this.server_id);
                this.maintabs.add(cpu_per);

                this.panels.add('cpu_per', {
                    chartPanel: cpu_per
                });
            }
            ,addMem:function(){
                var mem = Stats.Memory.init(this.server_id);
                this.maintabs.add(mem);

                this.panels.add('mem', {
                    chartPanel: mem
                });
            }
            ,addMemUsage:function(){
                var mem = Stats.MemoryUsage.init(this.server_id);
                this.maintabs.add(mem);

                this.panels.add('mem_usage', {
                    chartPanel: mem
                });
            }
            ,addDisk:function(){
                var disk = Stats.Disk.init(this.server_id);
                this.maintabs.add(disk);

                this.panels.add('disk', {
                    chartPanel: disk
                });
            }
            ,addDisk_rw:function(){
                var disk = Stats.Disk_rw.init(this.server_id);
                this.maintabs.add(disk);

                this.panels.add('disk_rw', {
                    chartPanel: disk
                });
            }
            ,addNodeLoad:function(){
                var nodeLoad = Stats.NodeLoad.init(this.server_id);
                this.maintabs.add(nodeLoad);

                this.panels.add('nodeLoad', {
                    chartPanel: nodeLoad
                });
            },
            unloadPanel: function(panel) {

                var p = this.panels.removeKey(panel);
                this.maintabs.remove(p.chartPanel);

            }

        }
    }();
</script>