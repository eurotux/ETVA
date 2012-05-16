<script>
Ext.ns('Server.Stats');

// Add the additional 'advanced' VTypes
Ext.apply(Ext.form.VTypes, {
    daterange : function(val, field) {

        var date = field.parseDate(val);
        if(!date){            
            return false;
        }
        if (field.startDateField && (!this.dateRangeMax || (date.getTime() != this.dateRangeMax.getTime()))) {            
            eval(" var start = (field.ownerCt).ownerCt."+field.startDateField);
            start.setMaxValue(date);
            this.dateRangeMax = date;
            start.validate();
        }
        else if (field.endDateField && (!this.dateRangeMin || (date.getTime() != this.dateRangeMin.getTime()))) {            
            eval("var end = (field.ownerCt).ownerCt."+field.endDateField);
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


//builds graph iframe window
graphIFrameWindow = function(config) {

    Ext.apply(config, {
        title: <?php echo json_encode(__('Please wait...')) ?>,

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
                //View.notify({html:MIF.title+' reports:domready '});
                //Demo.balloon(null, );
            },
            documentloaded : function(frameEl){
                var MIF = frameEl.ownerCt;
                var doc = frameEl.getFrameDocument();
                var img = doc.getElementsByTagName('img');

                MIF.setTitle(doc.title);

                if(img[0] && img[0].width>0){

                    MIF.setWidth(img[0].width+45);
                    MIF.setHeight(img[0].height+80);
                    View.notify({html:MIF.title+' reports: DATA LOADED'});

                }else View.notify({html:MIF.title+' reports: NO DATA'});
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
                qtip: __('Refresh'),
                scope : this,
                handler: function(button) {
                    this.setSrc();
                }
            }]});

    // toolbar
    Ext.apply(config, {
        tbar:[
            {
                text    : __('Refresh'),
                tooltip : __('Refresh'),
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
drange = function(config) {

    Ext.apply(this,config);

    var step_time=0;

    this.combo = new Ext.form.ComboBox({
        //  xtype:'combo',
        fieldLabel: __('Presets'),
        name: 'presets',
        store: new Ext.data.ArrayStore({
            id:'type',
            fields: ['type', 'name'],
            data : [                
                ['last_h', __('Last hour')],
                ['last_2h', __('Last 2 hour')],
                ['last_day', __('Last day')],
                ['last_week', __('Last week')]]
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
        width: 120,
        //anchor:'80%',
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
                        this.pPanel.reloadStores(record.data.name,'-1h','',step_time);
                        break;
                    case 'last_2h':
                        step_time = 300;
                        this.pPanel.reloadStores(record.data.name,'-2h','',step_time);
                        break;
                    case 'last_day':
                        step_time = 3600;
                        this.pPanel.reloadStores(record.data.name,'-1d','',step_time);                                                                        
                        break;
                    case 'cur_day':
                        step_time = 300;
                        this.pPanel.reloadStores(record.data.name,'-2h','',step_time);
                        break;
                    case 'last_week':
                        step_time = 21600; // 6h interval
                        //step_time = 300;
                        this.pPanel.reloadStores(record.data.name,'-1w','',step_time);
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

                        this.pPanel.reloadStores(record.data.name,start_unix_time,end_unix_time,step_time);
                        break;

                    default:break;
                }




                if(record.data.type=='month'){

                    var date_start = new Date(cur_year,cur_month);


                    var m_total_days = 32 - new Date(cur_year,cur_month,32).getDate();
                    var date_end = new Date(cur_year,cur_month,m_total_days,23,59,59);

                    var start_unix_time = parseInt(date_start.getTime().toString().substring(0, 10));
//                    alert(start_unix_time);
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
        defaults: {width: 620},
        bodyStyle:'padding:5px 0 0 0',
        items: [{
                layout:'column',
                height:40,
                layoutConfig: {fitHeight: true},
                items:[
                    {
                        columnWidth:.33,
                        layout: 'form',
                        labelWidth:60,
                        items: [this.combo]
                    }
                    ,{
                        columnWidth:.2,
                        layout: 'form',
                        items: [{
                                xtype:'datefield',
                                fieldLabel: __('From'),
                                name: 'startdt',
                                msgTarget:'none',
                                allowBlank:false,
                                format:'d/m/Y',
                                ref:'../startdate',
                                vtype: 'daterange',
                                anchor:'100%',
                                endDateField: 'enddate'
                            }]
                    }
                    ,{
                        columnWidth:.2,
                        layout: 'form',
                        items: [{
                                xtype:'datefield',
                                ref:'../enddate',
                                fieldLabel: __('To'),
                                name: 'enddt',
                                msgTarget:'none',
                                allowBlank:false,
                                format:'d/m/Y',
                                anchor:'100%',
                                vtype: 'daterange',
                                startDateField:'startdate'
                            }]
                    }
                    ,{
                        columnWidth:.3,
                        layout: 'form',
                        items: [{
                                xtype:'button',
                                text: <?php echo json_encode(__('Generate graph image')) ?>,
                                handler:function(){
                                    if(this.form.isValid()){

                                        var startdate = this.form.findField('startdt').getValue();
                                        var enddate = this.form.findField('enddt').getValue();                                    

                                        //var start_unix_time = startdate.format('U');
                                        var start_unix_time = startdate.getTime()/1000;                                        
                                        var end_unix_time = enddate.getTime()/1000;                                        

                                        var src =  '/server/graph_'+this.pPanel.type+'Image?'+
                                            'id='+this.pPanel.server_id+
                                            '&graph_start='+start_unix_time+
                                            '&graph_end='+end_unix_time;
                                        if( this.pPanel.pre_graph_src ){
                                            var src =  this.pPanel.pre_graph_src +
                                                        '&graph_start='+start_unix_time+
                                                        '&graph_end='+end_unix_time;
                                        }

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
Ext.extend(drange, Ext.FormPanel, {
    getCombo:function(){
        return this.combo;
    }
});



mainChartContainer = function(config) {

    Ext.apply(this,config);


    var datarange =  new drange({pPanel:this});

    var config = {
        layout:'border',
        step_time:20,
        defaults: {
            collapsible: true,
            border:false,
            split: true,
            autoScroll:true
            ,bodyStyle: 'padding:0px'
        },
        items: [{
                title: __('Date filter'),
                region: 'north',
                frame:true,
                autoScroll:false,
                height: 72,
                items:[datarange],
                tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-stats-filter',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Data filter Help')) ?>});}}],
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
Ext.extend(mainChartContainer, Ext.Panel, {
    loadPreset:function(){        
        var combo = (this.items.get(0)).get(0).getCombo();
        var preset = combo.getValue();
        var combo_store = combo.getStore();
        var rec = combo_store.getAt(combo_store.find('type',preset));        
        combo.fireEvent('select',combo,rec);
        
        this.setPanelsTitle(rec.data['name']);
    }
    ,setPanelsTitle:function(time_span){
        var pCharts = this.items.get(1);

        for (var i = 0, len = (pCharts.items).length; i < len; i++)
        {

            var itemP = pCharts.items.get(i);
            var current_title = itemP.title;
            current_title = current_title.replace(/ -.*/, "");
            itemP.setTitle(current_title+' - '+time_span);            

        }
    }
    ,reloadStores:function(time_span,start_unix_time,end_unix_time,step){
        
        var pCharts = this.items.get(1);
        
        if(step=== undefined) step = '';

        for (var i = 0, len = (pCharts.items).length; i < len; i++)
        {

            var itemP = pCharts.items.get(i);
            var current_title = itemP.title;
            current_title = current_title.replace(/ -.*/, "");
            itemP.setTitle(current_title+' - '+time_span);

            var itemChart = itemP.items.get(0);
            itemChart.store.reload({params:{graph_start:start_unix_time,
                    graph_end:end_unix_time,step:step}});

        }

    }
});



Server.Stats_NodeLoad = Ext.extend(mainChartContainer, {
    type:'nodeLoad',
    initComponent:function(){

        Server.Stats_NodeLoad.superclass.initComponent.call(this);
        this.on('activate',function(){
            this.doLayout();
            this.loadPreset();});

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
            task:task
            ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-stats-server-load',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server load Help')) ?>});}}]
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

/*
*
* DISK PANEL
*
*/

Server.Stats_Disk = Ext.extend(mainChartContainer, {
    type:'disk',
    initComponent:function(){

        Server.Stats_Disk.superclass.initComponent.call(this);
        this.on('activate',function(){
            this.doLayout();
            this.loadPreset();});
    }
    ,loadData:function(loadPreset){

        this.items.get(1).removeAll(true);


        var disks = new Ext.data.JsonStore({
            url: <?php echo json_encode(url_for('logicalvol/jsonList')) ?>,
            baseParams:{'sid': this.server_id},
            root: 'data',
            fields: [
                {name: 'id', mapping: 'id'}
                ,{name: 'lv', mapping: 'lv'}
            ],
            listeners: {
                load:{scope:this,fn:function(data){

                    if(data.totalLength == 0){

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
                    }
                    else{
                        data.each(function(disk){     
                            this.addDiskRW(disk);
                        },this);
                    }
                    this.doLayout();
                    if(loadPreset) this.loadPreset();
                    //this.doLayout(false,true);
                }}
            }
        });

        disks.load();
    }    
    ,addDiskRW:function(disk){

        var stp_time = this.step_time;
        var store = new Ext.data.XmlStore({
            url: <?php echo json_encode(url_for('logicalvol/xportDiskRWRRA')) ?>,
                //+network.Id, // automatically configures a HttpProxy
            baseParams:{'id':disk.get('id'),'graph_start':'-1h'},
            successProperty:'success',
            record: 'row', // records will have an "Item" tag
            fields: [{name:'time', mapping:'t'},
                {name:'reads',mapping:'v0',type:'float'},
                {name:'writes',mapping:'v1',type:'float'}]
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
                'datachanged': function(s){

                            var yAxis_title = 'Bytes';
                            var yAxis_div = 1;

                            var max_v = 0;

                            s.each(function(r){
                                            var wv = r.get('writes');
                                            if( wv > max_v ) max_v = wv;
                                            var rv = r.get('reads');
                                            if( rv > max_v ) max_v = rv;
                                        });
                            if( max_v < 1024 ){
                                yAxis_div = 1;
                                yAxis_title = 'Bytes';
                            } else if( max_v < (1024*1024) ){
                                yAxis_div = 1024;
                                yAxis_title = 'KBytes';
                            } else if( max_v < (1024*1024*1024) ){
                                yAxis_div = (1024*1024);
                                yAxis_title = 'MBytes';
                            } else {
                                yAxis_div = (1024*1024*1024);
                                yAxis_title = 'GBytes';
                            }

                            chart.yAxis = new Ext.chart.NumericAxis({
                                title: yAxis_title,
                                labelRenderer: function(val){
                                                var newval = Math.round(((val * 10) / yAxis_div)) / 10;
                                                return Ext.util.Format.number(newval,'0,0');
                                }
                            });
                },
                scope: this
            }

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
                    yField: 'reads',
                    displayName: 'Reads'
                    ,style: { color: '#eacc00' }
                },{
                    yField: 'writes',
                    displayName: 'Writes'
                    ,style: { color: '#4d95dd' }
                }
            ],
            xAxis :  new Ext.chart.CategoryAxis({
                labelRenderer : function(val){
                    return renderDate(val,stp_time);}

            }),
            yAxis: new Ext.chart.NumericAxis({
                title: 'Bytes',
                labelRenderer : Ext.util.Format.numberRenderer('0,0')
                /*labelRenderer: function(val){
                                while( val > 1000 ){
                                    val = val / 1000;
                                }
                                return val;
                }*/
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


        var V = new Ext.ux.plugin.VisibilityMode();

        var pChart = new Ext.Panel({
            title: String.format(<?php echo json_encode(__('Disk {0} :: IO')) ?>,disk.get('lv')),
            //title:'Disk '+disk.get('target')+' :: access - Last hour',
            border:false,
            collapsible:false,
            bodyStyle:'background:white;',
            items:[chart],
            plugins:Ext.isIE ? [] : V,
            //plugins: V,
            task:task
            ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-stats-server-disk',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server disk Help')) ?>});}}]
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



/*
 *
 * MEMORY PANEL
 *
 */
Server.Stats_Memory = Ext.extend(mainChartContainer, {
    type:'mem',
    initComponent:function(){

        Server.Stats_Memory.superclass.initComponent.call(this);
        this.on('activate',function(){
            this.doLayout();
            this.loadPreset();});
    }
    ,loadData:function(loadPreset){

        this.items.get(1).removeAll(true);
        this.addMem_perChart();
        this.addMem_usageChart();
        this.doLayout();
        if(loadPreset) this.loadPreset();
        //this.doLayout(false,true);

    }
    ,addMem_perChart:function(){

        var stp_time = this.step_time;
        var store = new Ext.data.XmlStore({
           // autoLoad: true,
            url: <?php echo json_encode(url_for('server/xportMem_perRRA')) ?>,
            baseParams:{'id':this.server_id,'graph_start':'-1h'},
            record: 'row', // records will have an "Item" tag
            fields: [{name:'time', mapping:'t'},
                {name:'mem_per',mapping:'v0',type:'float'}]
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
            ,seriesStyles: { color: '#eacc00' }

        });

        var V = new Ext.ux.plugin.VisibilityMode();
        var pChart = new Ext.Panel({
            title: <?php echo json_encode(__('Mem %')) ?>,
            border:false,
            collapsible:true,
            bodyStyle:'background:white;',
            plugins:Ext.isIE ? [] : V,
            task:task
            ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-stats-server-mem',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server memory Help')) ?>});}}]
            ,items:[chart],
            bbar:
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
    ,addMem_usageChart:function(){

        var stp_time = this.step_time;
        var store = new Ext.data.XmlStore({
           // autoLoad: true,
            url: <?php echo json_encode(url_for('server/xportMem_usageRRA')) ?>,
            baseParams:{'id':this.server_id,'graph_start':'-1h'},
            record: 'row', // records will have an "Item" tag
            fields: [{name:'time', mapping:'t'},
                {name:'mem_m',mapping:'v0',type:'float'},
                {name:'mem_v',mapping:'v1',type:'float'}]
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
                'datachanged': function(s){

                            var yAxis_title = 'Bytes';
                            var yAxis_div = 1;

                            var max_v = 0;

                            s.each(function(r){
                                            var wv = r.get('mem_m');
                                            if( wv > max_v ) max_v = wv;
                                            var rv = r.get('mem_v');
                                            if( rv > max_v ) max_v = rv;
                                        });
                            if( max_v < 1024 ){
                                yAxis_div = 1;
                                yAxis_title = 'Bytes';
                            } else if( max_v < (1024*1024) ){
                                yAxis_div = 1024;
                                yAxis_title = 'KBytes';
                            } else if( max_v < (1024*1024*1024) ){
                                yAxis_div = (1024*1024);
                                yAxis_title = 'MBytes';
                            } else {
                                yAxis_div = (1024*1024*1024);
                                yAxis_title = 'GBytes';
                            }

                            chart.yAxis = new Ext.chart.NumericAxis({
                                stackingEnabled: true,
                                title: yAxis_title,
                                labelRenderer: function(val){
                                                var newval = Math.round(((val * 10) / yAxis_div)) / 10;
                                                return Ext.util.Format.number(newval,'0,0');
                                }
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
                title: 'Bytes',
                labelRenderer : Ext.util.Format.numberRenderer('0,0')
            }),
            series: [{
                    yField: 'mem_v',
                    displayName: 'Available memory'
                    ,style: { color: '#eacc00' }
                },{
                    yField: 'mem_m',
                    displayName: 'Total system memory'
                    ,style: { color: '#4d95dd' }
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

        var V = new Ext.ux.plugin.VisibilityMode();
        var pChart = new Ext.Panel({
            title: <?php echo json_encode(__('Mem usage')) ?>,
            border:false,
            collapsible:true,
            bodyStyle:'background:white;',
            plugins:Ext.isIE ? [] : V,
            task:task,
            items:[chart],
            bbar:
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



/*
 *
 * CPU USAGE
 *
 */

Server.Stats_Cpu_per = Ext.extend(mainChartContainer, {
    type:'cpu_per',
    initComponent:function(){

        Server.Stats_Cpu_per.superclass.initComponent.call(this);
        this.on('activate',function(){
            this.doLayout();
            this.loadPreset();});
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
          //  autoLoad: true,
            url: <?php echo json_encode(url_for('server/xportCpu_perRRA')) ?>, // automatically configures a HttpProxy
            baseParams:{'id':this.server_id,'graph_start':'-1h'},
            record: 'row', // records will have an "Item" tag
            fields: [{name:'time', mapping:'t'},
                {name:'cpu_per',mapping:'v0',type:'float'}
            ]
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

        var chart = new Ext.chart.ColumnChart({
            xField: 'time',
            yField: 'cpu_per',
            height:200,
            expressInstall:true,
            border:false,
            store: store,
            emptyText: 'Loading...',
            xAxis: new Ext.chart.CategoryAxis({
                labelRenderer : function(val){                    
                    return renderDate(val,stp_time);}
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
            ,seriesStyles: { color: '#eacc00' }

        });


        var V = new Ext.ux.plugin.VisibilityMode();

        var pChart = new Ext.Panel({
            title: <?php echo json_encode(__('CPU %')) ?>,            
            border:false,
            collapsible:false,
            bodyStyle:'background:white;',
            items:[chart],
            plugins:Ext.isIE ? [] : V,
            //plugins: V,
            task:task
            ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-stats-server-load',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server load Help')) ?>});}}]
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

/*
 *
 * NETWORK STATS PANEL
 *
 */
Server.Stats_Network = Ext.extend(mainChartContainer, {
    type:'network',    
    initComponent:function(){

        Server.Stats_Network.superclass.initComponent.call(this);
        this.on('activate',function(){            
            this.doLayout();            
            this.loadPreset();});

    }
    ,loadData:function(loadPreset){

        var query = {'server_id':this.server_id};
        this.items.get(1).removeAll(true);

        var networks = new Ext.data.JsonStore({
            url: <?php echo json_encode(url_for('network/jsonGridNoPager')) ?>,
            baseParams:{'query': Ext.encode(query), 'sort':'port', 'dir':'asc'},
            root: 'data',
            fields: [
                {name: 'network_id', mapping: 'Id'}
                ,{name: 'network_interface', mapping: 'Mac'}
            ],
            listeners: {
                load:{scope:this,fn:function(data){

                    if(data.totalLength == 0){

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
                    }
                    else{
                        data.each(function(network){
                            this.addChart(network);
                        },this);
                    }

                    //this.doLayout(false,true);
                    this.doLayout();
                    if(loadPreset) this.loadPreset();
                }}
            }
        });

        networks.load();
    }
    ,addChart:function(network){
        var network_data = network['data'];
        
        var stp_time = this.step_time;        
        
        var store = new Ext.data.XmlStore({
            url: <?php echo json_encode(url_for('network/xportRRA')) ?>,
                //+network.Id, // automatically configures a HttpProxy
            baseParams:{'id':network_data['network_id'],'graph_start':'-1h'},
            successProperty:'success',
            record: 'row', // records will have an "Item" tag
            fields: [{name:'time', mapping:'t'},
                {name:'input',mapping:'v0',type:'float'},
                {name:'output',mapping:'v1',type:'float'}]
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
                'datachanged': function(s){

                            var yAxis_title = 'B/s';
                            var yAxis_div = 1;

                            var max_v = 0;

                            s.each(function(r){
                                            var wv = r.get('input');
                                            if( wv > max_v ) max_v = wv;
                                            var rv = r.get('output');
                                            if( rv > max_v ) max_v = rv;
                                        });
                            if( max_v < 1024 ){
                                yAxis_div = 1;
                                yAxis_title = 'B/s';
                            } else if( max_v < (1024*1024) ){
                                yAxis_div = 1024;
                                yAxis_title = 'KB/s';
                            } else if( max_v < (1024*1024*1024) ){
                                yAxis_div = (1024*1024);
                                yAxis_title = 'MB/s';
                            } else {
                                yAxis_div = (1024*1024*1024);
                                yAxis_title = 'GB/s';
                            }

                            chart.yAxis = new Ext.chart.NumericAxis({
                                title: yAxis_title,
                                labelRenderer: function(val){
                                                var newval = Math.round(((val * 10) / yAxis_div)) / 10;
                                                return Ext.util.Format.number(newval,'0,0');
                                }
                            });
                },
                scope: this
            }

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
                    ,style: { color: '#eacc00' }
                },{
                    yField: 'output',
                    displayName: 'Output'
                    ,style: { color: '#4d95dd' }
                }
            ],
            xAxis :  new Ext.chart.CategoryAxis({
                labelRenderer : function(val){
                    return renderDate(val,stp_time);}

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


        var V = new Ext.ux.plugin.VisibilityMode();

        var pChart = new Ext.Panel({
            title: String.format(<?php echo json_encode(__('Interface {0}')) ?>,network.get('network_interface')),
            border:false,
            collapsible:false,
            bodyStyle:'background:white;',
            items:[chart],
            plugins:Ext.isIE ? [] : V,
            task:task
            ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-stats-server-interface',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server load Help')) ?>});}}]
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






Server.Stats = function(config){

    Ext.apply(this,config);

    this.panels = new Ext.util.MixedCollection();

    this.mainTabs = new Ext.TabPanel({
                    activeTab:0,
                    //  region:'center',
                    border:false
                });
    this.mainTabs.on('unLoadPanel',function(panel){this.unloadPanel(panel);},this);


    Server.Stats.superclass.constructor.call(this, {
        title: <?php echo json_encode(__('Statistics')) ?>,
        border:false,
        layout:'fit',
       // layout:'border',
        items:[this.mainTabs]
        ,listeners:{
            beforerender:function(){
                Ext.getBody().mask(String.format('Loading server {0} panel...',this.title));
            }
            ,render:{delay:100,fn:function(){
                Ext.getBody().unmask();
            }}
        }
    });
        this.on('beforehide',function(){Ext.TaskMgr.stopAll();});

        this.on('refresh',function(){            
            this.loadPanels();

            var cur = '';
            if(this.mainTabs.getActiveTab()) cur = this.mainTabs.getActiveTab().type;

            for(var i = 0,limit = this.mainTabs.items.length; i < limit; i++){               

                if(this.mainTabs.items.get(i).type==cur) this.mainTabs.items.get(i).loadData(true);
                else this.mainTabs.items.get(i).loadData();
            }
 
        });

        this.on('beforeshow',function(){            
            this.fireEvent('refresh');
        });     


}//

// define public methods
Ext.extend(Server.Stats, Ext.Panel,{
    loadPanels:function(){
        
        this.addCpu_per();
        this.addNetworks();
        this.addMem();
        this.addDisk();
//        //  this.addMemUsage();
//        //  this.addDisk_rw();
        //this.addNodeLoad();

    }
    ,addNetworks:function(){

        if(!this.panels.containsKey('network')) {
            var network = new Server.Stats_Network({title: <?php echo json_encode(__('Network interfaces')) ?>,type:'network',server_id:this.server_id});

            this.mainTabs.add(network);
            this.panels.add('network', {
                    chartPanel: network
                });
        }
    }
    ,addCpu_per:function(){

        if(!this.panels.containsKey('cpu_per')) {
            var cpu_per = new Server.Stats_Cpu_per({title: <?php echo json_encode(__('CPU Usage')) ?>,type:'cpu_per',server_id:this.server_id});
            this.mainTabs.add(cpu_per);
            this.panels.add('cpu_per', {
                    chartPanel: cpu_per
                });
        }        
    }
    ,addMem:function(){

        if(!this.panels.containsKey('mem')) {
            var mem = new Server.Stats_Memory({title: <?php echo json_encode(__('Memory Usage')) ?>,type:'mem',server_id:this.server_id});
            this.mainTabs.add(mem);
            this.panels.add('mem', {
                    chartPanel: mem
                });
        }
        
    }
    ,addDisk:function(){

        if(!this.panels.containsKey('disk')) {
            var disk = new Server.Stats_Disk({title: <?php echo json_encode(__('Disk Usage')) ?>,type:'disk',server_id:this.server_id});
            this.mainTabs.add(disk);
            this.panels.add('disk', {
                    chartPanel: disk
                });
        }

    }
    ,addNodeLoad:function(){

        if(!this.panels.containsKey('nodeLoad')) {
            var nodeLoad = new Server.Stats_NodeLoad({title: <?php echo json_encode(__('Node Load')) ?>,type:'nodeLoad',node_id:this.node_id,server_id:this.server_id});
            this.mainTabs.add(nodeLoad);
            this.panels.add('nodeLoad', {
                    chartPanel: nodeLoad
                });
        }
        
    }
    ,unloadPanel: function(panel) {
        var p = this.panels.removeKey(panel);
        this.mainTabs.remove(p.chartPanel);
    }
});


</script>
