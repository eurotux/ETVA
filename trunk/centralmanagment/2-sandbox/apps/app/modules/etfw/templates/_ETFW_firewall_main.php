<script>

Ext.apply(Ext.form.VTypes, {

    newchain : function(val) {

        var no_spaces = /^[a-zA-Z][-_.a-zA-Z0-9]{0,30}$/;
        if(!no_spaces.test(val)) return false;
        return true;
    },
    newchainText : 'Chain name not allowed'
});


// form combo equal/ignored not equal
ETFW.Firewall.Form_EqualCombo = function(config) {

    // call parent constructor
    ETFW.Firewall.Form_EqualCombo.superclass.constructor.call(this, config);

    var config = {
        triggerAction:'all'
        ,mode:'local'
        ,width:100
        ,editable:false
        ,store:new Ext.data.SimpleStore({
            fields:['value', 'name']
            ,data:[['', '-- Ignored --'],['=', 'Equals'], ['!', 'Does not equal']]
        })
        ,displayField:'name'
        ,valueField:'value'
        ,value:''
        ,validator:function(value){
                        if(value=='') this.setValue(this.originalValue);
                        return true;
                   }
        ,scope:this
    };

    Ext.apply(this, config);

}// end constructor

// extend
Ext.extend(ETFW.Firewall.Form_EqualCombo, Ext.form.ComboBox, {}); // end of extend


// above/bellow combo
ETFW.Firewall.Form_ABCombo = function(config) {

    // call parent constructor
    ETFW.Firewall.Form_ABCombo.superclass.constructor.call(this, config);


    var config = {
        triggerAction:'all'
        ,mode:'local'
        ,width:100
        ,value:'ignored'
        ,editable:false
        ,store:new Ext.data.SimpleStore({
            fields:['value', 'name']
            ,data:[['ignored', '-- Ignored --'],['!', 'Above'], ['=', 'Below']]
        })
        ,displayField:'name'
        ,valueField:'value'
        ,validator:function(value){
                        if(value=='') this.setValue(this.originalValue);
                        return true;
                   }
        ,scope:this
    };

    Ext.apply(this, config);

}

// extend
Ext.extend(ETFW.Firewall.Form_ABCombo, Ext.form.ComboBox, {}); // end of extend


/*
* Form used to create/edit rules
*
**/

ETFW.Firewall.Form = Ext.extend(Ext.form.FormPanel, {

    // defaults - can be changed from outside
    border:false
    ,frame:true
    ,labelWidth:170
    ,url:<?php echo json_encode(url_for('etfw/json'))?>

    ,initComponent:function() {

        //network protocol combo
        var protoData = [
            ['tcp','TCP'],['udp','UDP']
            ,['icmp','ICMP'],['-','-------']
            ,['3pc','3PC'],['a/n','A/N']
            ,['ah','AH'],['argus','ARGUS']
            ,['aris','ARIS'],['ax.25','AX.25']
            ,['bbn-rcc','BBN-RCC'],['bna','BNA']
            ,['br-sat-mon','BR-SAT-MON'],['cbt','CBT']
            ,['cftp','CFTP'],['chaos','CHAOS']
            ,['compaq-peer','COMPAQ-PEER'],['cphb','CPHB']
            ,['cpnx','CPNX'],['crdup','CRDUP']
            ,['crtp','CRTP'],['dccp','DCCP']
            ,['dcn','DCN'],['ddp','DDP']
            ,['ddx','DDX'],['dgp','DGP']
            ,['dsr','DSR'],['egp','EGP']
            ,['eigrp','EIGRP'],['emcon','EMCON']
            ,['encap','ENCAP'],['esp','ESP']
            ,['etherip','ETHERIP'],['fc','FC']
            ,['fire','FIRE'],['ggp','GGP']
            ,['gmtp','GMTP'],['gre','GRE']
            ,['hmp','HMP'],['hopopt','HOPOPT']
            ,['i-nlsp','I-NLSP'],['iatp','IATP']
            ,['idpr','IDPR'],['idpr-cmtp','IDPR-CMTP']
            ,['idrp','IDRP'],['ifmp','IFMP']
            ,['igmp','IGMP'],['igp','IGP']
            ,['il','IL'],['ip','IP']
            ,['ipcomp','IPCOMP'],['ipcv','IPCV']
            ,['ipencap','IPENCAP'],['ipip','IPIP']
            ,['iplt','IPLT'],['ippc','IPPC']
            ,['ipv6','IPV6'],['ipv6-frag','IPV6-FRAG']
            ,['ipv6-icmp','IPV6-ICMP'],['ipv6-nonxt','IPV6-NONXT']
            ,['ipv6-opts','IPV6-OPTS'],['ipv6-route','IPV6-ROUTE']
            ,['ipx-in-ip','IPX-IN-IP'],['irtp','IRTP']
            ,['isis','ISIS'],['iso-ip','ISO-IP']
            ,['iso-tp4','ISO-TP4'],['kryptolan','KRYPTOLAN']
            ,['l2tp','L2TP'],['larp','LARP']
            ,['leaf-1','LEAF-1'],['leaf-2','LEAF-2']
            ,['merit-inp','MERIT-INP'],['mfe-nsp','MFE-NSP']
            ,['micp','MICP'],['mobile','MOBILE']
            ,['mpls-in-ip','MPLS-IN-IP'],['mtp','MTP']
            ,['mux','MUX'],['narp','NARP']
            ,['netblt','NETBLT'],['nsfnet-igp','NSFNET-IGP']
            ,['nvp','NVP'],['ospf','OSPF']
            ,['pgm','PGM'],['pim','PIM']
            ,['pipe','PIPE'],['pnni','PNNI']
            ,['prm','PRM'],['ptp','PTP']
            ,['pup','PUP'],['pvp','PVP']
            ,['qnx','QNX'],['rdp','RDP']
            ,['rsvp','RSVP'],['rsvp-e2e-ignore','RSVP-E2E-IGNORE']
            ,['rvd','RVD'],['sat-expak','SAT-EXPAK']
            ,['sat-mon','SAT-MON'],['scc-sp','SCC-SP']
            ,['scps','SCPS'],['sctp','SCTP']
            ,['sdrp','SDRP'],['secure-vmtp','SECURE-VMTP']
            ,['skip','SKIP'],['sm','SM']
            ,['smp','SMP'],['snp','SNP']
            ,['sprite-rpc','SPRITE-RPC'],['sps','SPS']
            ,['srp','SRP'],['sscopmce','SSCOPMCE']
            ,['st','ST'],['stp','STP']
            ,['sun-nd','SUN-ND'],['swipe','SWIPE']
            ,['tcf','TCF'],['tlsp','TLSP']
            ,['tp++','TP++'],['trunk-1','TRUNK-1']
            ,['trunk-2','TRUNK-2'],['ttp','TTP']
            ,['udplite','UDPLITE'],['uti','UTI']
            ,['vines','VINES'],['visa','VISA']
            ,['vmtp','VMTP'],['vrrp','VRRP']
            ,['wb-expak','WB-EXPAK'],['wb-mon','WB-MON']
            ,['wsn','WSN'],['xnet','XNET']
            ,['xns-idp','XNS-IDP'],['xtp','XTP']
            ,['Other...','Other...']
        ];

        var protoStore = new Ext.data.ArrayStore({
            fields: ['value', 'name'],
            data : protoData
        });

        this.protoCombo = new Ext.form.ComboBox({
            store: protoStore,
            name:'p',
            displayField:'name',
            valueField:'value',
            editable:false,
            hideLabel:true,
            typeAhead: true,
            hiddenName:'p',
            mode: 'local',
            value:'tcp',
            forceSelection: true,
            triggerAction: 'all',
            selectOnFocus:true,
            validator:function(value){
                            if(value=='') this.setValue(this.originalValue);
                            return true;
                      },
            scope:this
        });
        //end network protocol combo

        this.proto_other = new Ext.form.TextField({
            fieldLabel: '',
            hideLabel:true,
            name: 'p-other'});

        //type of service combo
        var tosData = [
            ['Minimize-Delay','Minimize-Delay (0x10)']
            ,['Maximize-Throughput','Maximize-Throughput (0x08)']
            ,['Maximize-Reliability','Maximize-Reliability (0x04)']
            ,['Minimize-Cost','Minimize-Cost (0x02)']
            ,['Normal-Service','Normal-Service (0x00)']
        ];

        var tosStore = new Ext.data.ArrayStore({
            fields: ['value', 'name'],
            data : tosData
        });

        var tosCombo = new Ext.form.ComboBox({
            store: tosStore,
            name:'tos',
            hiddenName:'tos',
            displayField:'name',
            valueField:'value',
            editable:false,
            hideLabel:true,
            typeAhead: true,
            mode: 'local',
            value:'Minimize-Delay',
            forceSelection: true,
            triggerAction: 'all',
            selectOnFocus:true,
            validator:function(value){
                            if(value=='') this.setValue(this.originalValue);
                            return true;
                      },
            scope:this
        });

        //end type of service combo

        //ICMP type combo
        var icmpData = [
            ['any','any']
            ,['echo-reply','echo-reply']
            ,['destination-unreachable','destination-unreachable']
            ,['network-unreachable','network-unreachable']
            ,['host-unreachable','host-unreachable']
            ,['protocol-unreachable','protocol-unreachable']
            ,['port-unreachable','port-unreachable']
            ,['fragmentation-needed','fragmentation-needed']
            ,['source-route-failed','source-route-failed']
            ,['network-unknown','network-unknown']
            ,['host-unknown','host-unknown']
            ,['network-prohibited','network-prohibited']
            ,['host-prohibited','host-prohibited']
            ,['TOS-network-unreachable','TOS-network-unreachable']
            ,['TOS-host-unreachable','TOS-host-unreachable']
            ,['communication-prohibited','communication-prohibited']
            ,['host-precedence-violation','host-precedence-violation']
            ,['precedence-cutoff','precedence-cutoff']
            ,['source-quench','source-quench']
            ,['redirect','redirect']
            ,['network-redirect','network-redirect']
            ,['host-redirect','host-redirect']
            ,['TOS-network-redirect','TOS-network-redirect']
            ,['TOS-host-redirect','TOS-host-redirect']
            ,['echo-request','echo-request']
            ,['router-advertisement','router-advertisement']
            ,['router-solicitation','router-solicitation']
            ,['time-exceeded','time-exceeded']
            ,['ttl-zero-during-transit','ttl-zero-during-transit']
            ,['ttl-zero-during-reassembly','ttl-zero-during-reassembly']
            ,['parameter-problem','parameter-problem']
            ,['ip-header-bad','ip-header-bad']
            ,['required-option-missing','required-option-missing']
            ,['timestamp-request','timestamp-request']
            ,['timestamp-reply','timestamp-reply']
            ,['address-mask-request','address-mask-request']
            ,['address-mask-reply','address-mask-reply']

        ];

        var icmpStore = new Ext.data.ArrayStore({
            fields: ['value', 'name'],
            data : icmpData
        });

        var icmpCombo = new Ext.form.ComboBox({
            store: icmpStore,
            name:'icmp-type',
            displayField:'name',
            valueField:'value',
            editable:false,
            hideLabel:true,
            typeAhead: true,
            mode: 'local',
            value:'any',
            forceSelection: true,
            triggerAction: 'all',
            selectOnFocus:true,
            validator:function(value){
                        if(value=='') this.setValue(this.originalValue);
                        return true;
                      },
            scope:this
        });

        //end ICMP type combo

        //interfaces
        var intfStore = new Ext.data.JsonStore({
            url: this.url,
            baseParams:{id:this.network_dispatcher,method:'boot_interfaces',mode:'boot_real_interfaces'},
            id: 'fullname',
            remoteSort: false,
            totalProperty: 'total',
            root: 'data',
            fields: ['fullname']
        });
        intfStore.setDefaultSort('fullname', 'ASC');

        intfStore.on('load',function(){
                                intfStore.add(new intfStore.recordType({fullname:'Other...'}));
        });

        intfStore.load();


        var intfConf = {
            mode: 'local',
            triggerAction: 'all',
            editable:false,
            fieldLabel: '',
            forceSelection:true,
            hideLabel:true,
            allowBlank: false,
            readOnly:true,
            store:intfStore,
            valueField: 'fullname',
            displayField: 'fullname',
            width:70
        };

        // set incoming interface combo
        this.i_intfCombo = new Ext.form.ComboBox(intfConf);
        // get first value and set as default
        this.i_intfCombo.store.on('load',function(){
            var defval= this.i_intfCombo.store.getAt(0).data.fullname;
            this.i_intfCombo.setValue(defval);
        },this);

        // set outgoing interface combo
        this.o_intfCombo = new Ext.form.ComboBox(intfConf);
        this.o_intfCombo.store.on('load',function(){
            var defval= this.o_intfCombo.store.getAt(0).data.fullname;
            this.o_intfCombo.setValue(defval);
        },this);

        this.i_intf_other = new Ext.form.TextField({
            fieldLabel: '',
            hideLabel:true,
            name: 'i-other'});

        this.o_intf_other = new Ext.form.TextField({
            fieldLabel: '',
            hideLabel:true,
            name: 'o-other'});


        //set form reader for loading record data
        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            root: 'data',
            fields: ['index','chain','chain-desc','cmt',
                'j',
                'to-ports-src','to-ports-from','to-ports-to',
                'masq-src','masq-from','masq-to',
                'to-destination-src','to-destination-from','to-destination-to',
                'to-destination-port-from','to-destination-port-to',
                'to-source-src','to-source-from','to-source-to',
                'to-source-port-from','to-source-port-to',
                'reject-with-src',
                'reject-with',
                's','s-c',
                'd','d-c',
                'i','i-c',
                'o','o-c',
                'f','p','p-c',
                'sports','sport-from','sport-to','sport-c','sport-src',
                'dports','dport-from','dport-to','dport-c','dport-src',
                'ports','ports-c',
                'tcp-flags-c','syn','syn-set','ack','ack-set','fin','fin-set','rst','rst-set','urg','urg-set','psh','psh-set',
                'tcp-option','tcp-option-c',
                'icmp-type','icmp-type-c',
                'mac-source','mac-source-c',
                'limit','limit-c','limit-time',
                'limit-burst','limit-burst-c',
                'state','state-c',
                'tos','tos-c','args'
            ]

        });

        this.j_run = new Ext.form.Radio({name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Run chain',inputValue: '*'});
        this.j_run_txt = new Ext.form.TextField({ name:'j-run',fieldLabel:'',hideLabel:true});

        // on loadRecord finished bind data to form correctly....
        this.on('actioncomplete',function(){

            var data = reader.jsonData.data[0]; // get first record. The reader should only have on record

            //check if the 'actiont to take' is a 'Run chain'....
            var default_action = [];
            var is_default_action = 0;

            if(this.table_=='nat') default_action = ['ACCEPT','DROP','MASQUERADE','SNAT','REDIRECT','DNAT'];
            else default_action = ['ACCEPT','DROP','REJECT','QUEUE','RETURN','LOG'];

            for (key in default_action) {
                if (default_action[key] == data.j) {
                    is_default_action = 1;
                    break;
                }
            }

            if(!is_default_action && data.j){
                this.j_run_txt.setValue(data.j);
                this.j_run.setValue('*');
            }

            // input interface
            if(data.i)
                if(this.i_intfCombo.getStore().find('fullname',data.i)==-1){
                    this.i_intfCombo.setValue('Other...');
                    this.i_intf_other.setValue(data.i);
                }else this.i_intfCombo.setValue(data.i);


            // outgoing interface
            if(data.o)
                if(this.o_intfCombo.getStore().find('fullname',data.o)==-1){
                    this.o_intfCombo.setValue('Other...');
                    this.o_intf_other.setValue(data.o);
                }else this.o_intfCombo.setValue(data.o);


            //network proto. set combo or textfield value
            if(data.p)
                if(this.protoCombo.getStore().find('value',data.p)==-1){
                    this.protoCombo.setValue('Other...');
                    this.proto_other.setValue(data.p);
                }else this.protoCombo.setValue(data.p);
            //}else this.protoCombo.setValue(this.protoCombo.originalValue);

        });//end actioncomplete

        this.saveBtn = new Ext.Button({text:'Save'
            ,scope:this
            ,handler:this.onSave
        });

        this.cloneBtn = new Ext.Button({text:'Clone'
            ,scope:this
            ,handler:this.onClone
        });

        this.chain_desc = new Ext.form.TextField({'fieldLabel':'Part of chain',disabled:true,width:220,name: 'chain-desc'});

        var params = Ext.decode(this.parent_grid.baseParams.params);
        this.table_ = params.table;
        this.chain_ = params.chain;
        this.chain_desc_ = this.parent_grid.title;

        if(this.table_=='nat') this.action_fieldset = this.buildUINat();
        else this.action_fieldset = this.buildUIDefault();

        //form layout configuration
        var config = {
            defaultType:'textfield'
            ,defaults:{anchor:'-24'}
            ,monitorValid:true
            ,autoScroll:true
            ,reader: reader
            // ,buttonAlign:'right'
            ,items:[
                    this.action_fieldset,
                    {
                    xtype:'panel',
                    html:'The action selected above will only be carried out if all the conditions below are met.<br>'
                   },
                   // condition details fieldset
                   {
                    xtype:'fieldset',
                    collapsible:true,
                    title: 'Conditions details',
                    items :[
                            //source address
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'s-c',hiddenName:'s-c',fieldLabel:'Source address or network'})]},
                                     {layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 's'}]}]
                            },
                            // destination address
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'d-c',hiddenName:'d-c',fieldLabel:'Destination address or network'})]},
                                     {layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'd'}]}]
                            },
                            // incoming interface
                            {
                             layout:'table',
                             layoutConfig: {columns: 3},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'i-c',hiddenName:'i-c',fieldLabel:'Incoming interface'})]},
                                     {layout:'form',items:[this.i_intfCombo]},
                                     {layout:'form',items:[this.i_intf_other]}]
                            },
                            // outgoing interface
                            {
                             layout:'table',
                             layoutConfig: {columns: 3},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'o-c',hiddenName:'o-c',fieldLabel:'Outgoing interface'})]},
                                     {layout:'form',items:[this.o_intfCombo]},
                                     {layout:'form',items:[this.o_intf_other]}]
                            },
                            // fragmentation
                            {
                             layout:'table',layoutConfig: {columns: 3},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'f', fieldLabel:'Fragmentation',boxLabel:'Ignored',checked:true, inputValue: 'ignored'}]},
                                     {layout:'form',items:[{xtype:'radio', name:'f', fieldLabel:'',hideLabel:true,boxLabel:'Is fragmented',inputValue: ''}]},
                                     {layout:'form',items:[{xtype:'radio', name:'f', fieldLabel:'',hideLabel:true,boxLabel:'Is not fragmented',inputValue: '! '}]}]
                            },
                            // network protocol
                            {
                             layout:'table',
                             layoutConfig: {columns: 3},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'p-c',hiddenName:'p-c',fieldLabel:'Network protocol'})]},
                                     {layout:'form',items:[this.protoCombo]},
                                     {layout:'form',items:[this.proto_other]}]
                            },
                            // Source TCP or UDP port
                            {
                             layout:'table',
                             layoutConfig: {columns: 6},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'sport-c',hiddenName:'sport-c',fieldLabel:'Source TCP or UDP port'})]}
                                    ,{layout:'form',bodyStyle:'padding-right:5px;',items:[{xtype:'radio', name:'sport-src', fieldLabel:'',hideLabel:true,boxLabel:'Port(s)',inputValue: ''}]}
                                    ,{layout:'form',items:[{xtype:'textfield',width:80,fieldLabel: '',hideLabel:true,name: 'sports'}]}
                                    ,{layout:'form',bodyStyle:'padding-right:5px;',items:[{xtype:'radio', name:'sport-src', fieldLabel:'',hideLabel:true,boxLabel:'Port range',inputValue: 'range'}]}
                                    ,{layout:'form',items:[{xtype:'textfield',width:40,fieldLabel: '',hideLabel:true,name: 'sport-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',width:40,fieldLabel: 'to',name: 'sport-to'}]}]
                            },
                            // Destination TCP or UDP port
                            {
                             layout:'table',
                             layoutConfig: {columns: 6},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'dport-c',hiddenName:'dport-c',fieldLabel:'Destination TCP or UDP port'})]}
                                    ,{layout:'form',bodyStyle:'padding-right:5px;',items:[{xtype:'radio', name:'dport-src', fieldLabel:'',hideLabel:true,boxLabel:'Port(s)',inputValue: ''}]}
                                    ,{layout:'form',items:[{xtype:'textfield',width:80,fieldLabel: '',hideLabel:true,name: 'dports'}]}
                                    ,{layout:'form',bodyStyle:'padding-right:5px;',items:[{xtype:'radio', name:'dport-src', fieldLabel:'',hideLabel:true,boxLabel:'Port range',inputValue: 'range'}]}
                                    ,{layout:'form',items:[{xtype:'textfield',width:40,fieldLabel: '',hideLabel:true,name: 'dport-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',width:40,fieldLabel: 'to',name: 'dport-to'}]}]
                            },
                            // Source and destination port(s)
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'ports-c',hiddenName:'ports-c',fieldLabel:'Source and destination port(s)'})]},
                                     {layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'ports'}]}]
                            },
                            // TCP flags set
                            {
                             layout:'table',
                             layoutConfig: {columns: 7},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'tcp-flags-c',hiddenName:'tcp-flags-c',fieldLabel:'TCP flags set'})]}
                                    ,{layout:'form',items:[{xtype:'checkbox', name:'syn', fieldLabel:'',hideLabel:true,boxLabel:'SYN',inputValue: 'SYN'}
                                                          ,{xtype:'checkbox', name:'syn-set', fieldLabel:'',hideLabel:true,boxLabel:'SYN',inputValue: 'SYN'}]}
                                    ,{layout:'form',items:[{xtype:'checkbox', name:'ack', fieldLabel:'',hideLabel:true,boxLabel:'ACK',inputValue: 'ACK'}
                                                          ,{xtype:'checkbox', name:'ack-set', fieldLabel:'',hideLabel:true,boxLabel:'ACK',inputValue: 'ACK'}]}
                                    ,{layout:'form',items:[{xtype:'checkbox', name:'fin', fieldLabel:'',hideLabel:true,boxLabel:'FIN',inputValue: 'FIN'}
                                                          ,{xtype:'checkbox', name:'fin-set', fieldLabel:'',hideLabel:true,boxLabel:'FIN',inputValue: 'FIN'}]}
                                    ,{layout:'form',items:[{xtype:'checkbox', name:'rst', fieldLabel:'',hideLabel:true,boxLabel:'RST',inputValue: 'RST'}
                                                          ,{xtype:'checkbox', name:'rst-set', fieldLabel:'',hideLabel:true,boxLabel:'RST',inputValue: 'RST'}]}
                                    ,{layout:'form',items:[{xtype:'checkbox', name:'urg', fieldLabel:'',hideLabel:true,boxLabel:'URG',inputValue: 'URG'}
                                                          ,{xtype:'checkbox', name:'urg-set', fieldLabel:'',hideLabel:true,boxLabel:'URG',inputValue: 'URG'}]}
                                    ,{layout:'form',items:[{xtype:'checkbox', name:'psh', fieldLabel:'',hideLabel:true,boxLabel:'PSH out of',inputValue: 'PSH'}
                                                          ,{xtype:'checkbox', name:'psh-set', fieldLabel:'',hideLabel:true,boxLabel:'PSH',inputValue: 'PSH'}]}]
                            },
                            // TCP option number is set
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'tcp-option-c',hiddenName:'tcp-option-c',fieldLabel:'TCP option number is set'})]},
                                     {layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'tcp-option'}]}]
                            },
                            // ICMP packet type
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'icmp-type-c',hiddenName:'icmp-type-c',fieldLabel:'ICMP packet type'})]},
                                     {layout:'form',items:[icmpCombo]}]
                            },
                            // Ethernet address
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'mac-source-c',hiddenName:'mac-source-c',fieldLabel:'Ethernet address'})]},
                                     {layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'mac-source'}]}]
                            },
                            // Packet flow rate
                            {
                             layout:'table',
                             layoutConfig: {columns: 3},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_ABCombo({name:'limit-c',hiddenName:'limit-c',fieldLabel:'Packet flow rate'})]},
                                     {layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'limit'}]},
                                     {layout:'form',labelWidth:1,items:[{xtype:'combo',width:80,fieldLabel: '/',labelSeparator:'',name: 'limit-time',
                                                        store: ['second','minute','hour','day'],
                                                        forceSelection: true,
                                                        value:'second',
                                                        triggerAction: 'all',
                                                        editable:false,
                                                        validator:function(value){
                                                                    if(value=='') this.setValue(this.originalValue);
                                                                    return true;},
                                                        scope:this,
                                                        selectOnFocus:true}]}]
                            },
                            // Packet burst rate
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_ABCombo({name:'limit-burst-c',hiddenName:'limit-burst-c',fieldLabel:'Packet burst rate'})]},
                                     {layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'limit-burst'}]}]
                            },
                            // Connection states
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'state-c',hiddenName:'state-c',fieldLabel:'Connection states'})]},
                                     {layout:'form',items:[{
                                                        xtype:"multiselect",
                                                        fieldLabel:'',
                                                        hideLabel:true,
                                                        name:"state",                                                        
                                                        width:205,
                                                        height:100,
                                                        allowBlank:true,
                                                        store:[['NEW','New connection (NEW)'],['ESTABLISHED','Existing connection (ESTABLISHED)'],
                                                              ['RELATED','Related to existing (RELATED)'],['INVALID','Not part of any connection (INVALID)'],
                                                              ['UNTRACKED','Not tracked']]
                                                      }]}]
                            },
                            // Type of service
                            {
                             layout:'table',
                             layoutConfig: {columns: 2},
                             items: [
                                     {layout:'form',items:[new ETFW.Firewall.Form_EqualCombo({name:'tos-c',hiddenName:'tos-c',fieldLabel:'Type of service'})]},
                                     {layout:'form',items:[tosCombo]}]
                            },
                            {
                             xtype:'textfield',
                             fieldLabel: 'Additional parameters',
                             name: 'args'
                            }]}
             ]
            ,buttons:[this.cloneBtn,this.saveBtn]
        }; // eo config object


        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));


        this.on('beforerender',function(){
            Ext.getBody().mask('Loading form data...');}
            ,this
        );

        this.on('render',function(){
            Ext.getBody().unmask();}
            ,this
            ,{delay:10}
        );


        // call parent
        ETFW.Firewall.Form.superclass.initComponent.apply(this, arguments);



    } // eo function initComponent
    ,buildUIDefault:function(){
        return {
                    xtype:'fieldset',
                    collapsible:true,
                    title: 'Chain and action details',
                    items :[
                            this.chain_desc,
                            {xtype:'textfield',fieldLabel: 'Rule comment',width:250,name: 'cmt'},
                            //action to take (1st row)
                            {
                             layout:'table',
                             layoutConfig: {columns: 5},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'Action to take',boxLabel:'Do nothing',checked:true,inputValue: ''}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Accept',inputValue: 'ACCEPT'}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Drop',inputValue: 'DROP'}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Reject',inputValue: 'REJECT'}]},
                                     {layout:'form',items:[{xtype:'radio'  ,name:'j', fieldLabel:'',hideLabel:true,boxLabel:'User space',inputValue: 'QUEUE'}]}]
                            },
                            //action to take (2nd row)
                            {
                             layout:'table',
                             layoutConfig: {columns: 4},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',boxLabel:'Exit chain',inputValue: 'RETURN'}]},
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Log packet',inputValue: 'LOG'}]},
                                    {layout:'form',bodyStyle:'padding-right:5px;',items:[this.j_run]},
                                    {layout:'form',items:[this.j_run_txt]}]
                            },
                            //reject with icmp type
                            {
                             layout:'table',
                             layoutConfig: {columns: 3},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                    {layout:'form',items:[{xtype:'radio', name:'reject-with-src', fieldLabel:'Reject with ICMP type',boxLabel:'Default',checked:true,inputValue: 'default'}]},
                                    {layout:'form',bodyStyle:'padding-right:5px;',items:[{xtype:'radio', name:'reject-with-src', fieldLabel:'',hideLabel:true,boxLabel:'Type',inputValue: 'type'}]},
                                    {layout:'form',items:[{
                                            name:'reject-with'
                                            ,fieldLabel:'',hideLabel:true
                                            ,xtype:'combo'
                                            ,triggerAction:'all'
                                            ,mode:'local'
                                            ,editable:false
                                            ,store:["icmp-net-unreachable"
                                                ,"icmp-host-unreachable"
                                                ,"icmp-port-unreachable"
                                                ,"icmp-proto-unreachable"
                                                ,"icmp-net-prohibited"
                                                ,"icmp-host-prohibited"
                                                ,"echo-reply"
                                                ,"tcp-reset"]
                                            ,value:'icmp-net-unreachable'
                                            ,validator:function(value){
                                                        if(value=='') this.setValue(this.originalValue);
                                                        return true;
                                            }
                                            ,scope:this}]}]
                            }
               ]};//end first fieldset
     }
    ,buildUINat:function(){
        var comment = [{xtype:'textfield',width:250,fieldLabel: 'Rule comment',name: 'cmt'}];
        var action_take =  [{
                             layout:'table',
                             layoutConfig: {columns: 5},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'Action to take',boxLabel:'Do nothing',checked:true,inputValue: ''}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Accept',inputValue: 'ACCEPT'}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Drop',inputValue: 'DROP'}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Masquerade',inputValue: 'MASQUERADE'}]},
                                     {layout:'form',items:[{xtype:'radio'  ,name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Source NAT',inputValue: 'SNAT'}]}]
                            },
                            {
                             layout:'table',
                             layoutConfig: {columns: 4},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',boxLabel:'Redirect',inputValue: 'REDIRECT'}]},
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Destination NAT',inputValue: 'DNAT'}]},
                                    {layout:'form',bodyStyle:'padding-right:0px;',items:[this.j_run]},
                                    {layout:'form',bodyStyle:'padding-bottom:5px;',items:[this.j_run_txt]}]
                            }];
        var target_ports = [{
                             layout:'table',
                             layoutConfig: {columns: 4},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'to-ports-src', fieldLabel:'Target ports for redirect',boxLabel:'Default',checked:true,inputValue: ''}]}
                                    ,{layout:'form',items:[{xtype:'radio', name:'to-ports-src', fieldLabel:'',hideLabel:true,boxLabel:'Port range',inputValue: 'range'}]}
                                    ,{layout:'form',items:[{xtype:'textfield',width:40,fieldLabel: '',hideLabel:true,name: 'to-ports-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',width:40,fieldLabel: 'to',name: 'to-ports-to'}]}]
                            }];

        var masquerading = [{
                             layout:'table',
                             layoutConfig: {columns: 4},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'masq-src', fieldLabel:'Source ports for masquerading',boxLabel:'Any',checked:true,inputValue: ''}]}
                                    ,{layout:'form',items:[{xtype:'radio', name:'masq-src', fieldLabel:'',hideLabel:true,boxLabel:'Port range',inputValue: 'range'}]}
                                    ,{layout:'form',items:[{xtype:'textfield',width:40,fieldLabel: '',hideLabel:true,name: 'masq-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',width:40,fieldLabel: 'to',name: 'masq-to'}]}]
                            }];

        var dnat = [{
                             layout:'table',
                             layoutConfig: {columns: 6},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'to-destination-src', fieldLabel:'IPs and ports for DNAT',boxLabel:'Default',checked:true,inputValue: ''}]}
                                    ,{layout:'form',items:[{xtype:'radio', name:'to-destination-src', fieldLabel:'',hideLabel:true,boxLabel:'IP range',inputValue: 'range'}]}
                                    ,{layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'to-destination-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',fieldLabel: 'to',name: 'to-destination-to'}]}
                                    ,{layout:'form',labelWidth:60,items:[{xtype:'textfield',fieldLabel: 'Port range',width:40,name: 'to-destination-port-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',width:40,fieldLabel: 'to',name: 'to-destination-port-to'}]}]
                            }];

        var snat =[{
                             layout:'table',
                             layoutConfig: {columns: 6},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'to-source-src', fieldLabel:'IPs and ports for SNAT',boxLabel:'Default',checked:true,inputValue: ''}]}
                                    ,{layout:'form',items:[{xtype:'radio', name:'to-source-src', fieldLabel:'',hideLabel:true,boxLabel:'IP range',inputValue: 'range'}]}
                                    ,{layout:'form',items:[{xtype:'textfield',fieldLabel: '',hideLabel:true,name: 'to-source-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',fieldLabel: 'to',name: 'to-source-to'}]}
                                    ,{layout:'form',labelWidth:60,items:[{xtype:'textfield',fieldLabel: 'Port range',width:40,name: 'to-source-port-from'}]}
                                    ,{layout:'form',labelWidth:15,items:[{xtype:'textfield',width:40,fieldLabel: 'to',name: 'to-source-port-to'}]}]
                            }];

        switch(this.chain_){
          case 'PREROUTING':
              case 'OUTPUT':

                action_take =  [{
                             layout:'table',
                             layoutConfig: {columns: 3},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'Action to take',boxLabel:'Do nothing',checked:true,inputValue: ''}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Accept',inputValue: 'ACCEPT'}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Drop',inputValue: 'DROP'}]}
                                    ]
                            },
                            {
                             layout:'table',
                             layoutConfig: {columns: 4},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',boxLabel:'Redirect',inputValue: 'REDIRECT'}]},
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Destination NAT',inputValue: 'DNAT'}]},
                                    {layout:'form',bodyStyle:'padding-right:0px;',items:[this.j_run]},
                                    {layout:'form',bodyStyle:'padding-bottom:5px;',items:[this.j_run_txt]}]
                            }

                        ];

                        return {
                            xtype:'fieldset',
                            collapsible:true,
                            title: 'Chain and action details',
                            items :[
                                this.chain_desc,
                                comment,
                                action_take,target_ports,dnat]
                        };

                        break;
       case 'POSTROUTING':
                       action_take =  [{
                             layout:'table',
                             layoutConfig: {columns: 3},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'Action to take',boxLabel:'Do nothing',checked:true,inputValue: ''}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Accept',inputValue: 'ACCEPT'}]},
                                     {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Drop',inputValue: 'DROP'}]}
                                    ]
                            },
                            {
                             layout:'table',
                             layoutConfig: {columns: 4},
                             defaults:{bodyStyle:'padding-right:15px;'},
                             items: [
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',boxLabel:'Masquerade',inputValue: 'MASQUERADE'}]},
                                    {layout:'form',items:[{xtype:'radio', name:'j', fieldLabel:'',hideLabel:true,boxLabel:'Source NAT',inputValue: 'SNAT'}]},
                                    {layout:'form',bodyStyle:'padding-right:0px;',items:[this.j_run]},
                                    {layout:'form',bodyStyle:'padding-bottom:5px;',items:[this.j_run_txt]}]
                            }

                        ];

                        return {

                            xtype:'fieldset',
                            collapsible:true,
                            title: 'Chain and action details',
                            items :[
                                this.chain_desc,
                                comment,
                                action_take,masquerading,snat]
                        };

                        break;
                default:
                        return {

                            xtype:'fieldset',
                            collapsible:true,
                            title: 'Chain and action details',
                            items :[
                                this.chain_desc,
                                comment,
                                action_take,masquerading,target_ports,snat,dnat]
                        };
                        break;
        }


    }
    /**
     * Form onRender override
     */
    ,onRender:function() {
        // call parent
        ETFW.Firewall.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();

        if(this.reset){
            this.saveBtn.setText('Create');
            this.saveBtn.setHandler(this.onCreate,this);
            this.cloneBtn.setVisible(false);

            this.chain_desc.setValue(this.chain_desc_);

        }

        // loads form after initial layout
        if(!this.reset) this.on('afterlayout', this.loadRecord, this, {single:true});




    } // eo function onRender

    ,loadRecord:function() {
        
        var record = this.parent_grid.getSelectionModel().getSelected();
        this.index = record.data.index;
        this.load({
            url:this.url
            ,waitMsg:'Loading...'
            ,params:{id:this.service_id,
                     params:Ext.encode({"table":this.table_,"index":this.index}),
                     method:'get_rule'}
        });

    } // eo function loadRecord
    ,onClone:function(){
        this.setTitle('Clone Rule');
        this.cloneBtn.setVisible(false);
        this.saveBtn.setText('Create');
        this.saveBtn.setHandler(this.onCreate,this);

    }
    ,getDataSubmit:function(){
        var form_values = this.getForm().getValues();
        var send_data = new Object();
        send_data.table = this.table_;
        send_data.chain = this.chain_;

        //comment
        if(form_values['cmt']) send_data.cmt = form_values['cmt'];
        //action to take
        switch(form_values['j']){
                    case '*' :
                                send_data.j = form_values['j-run'];
                                break;
            case 'MASQUERADE':
                                if(form_values['masq-src']=='range'){
                                    send_data['to-ports'] = form_values['masq-from']+'-'+form_values['masq-to'];
                                    send_data.j = form_values['j'];
                                }
                                break;
              case 'REDIRECT':
                                send_data.j = form_values['j'];
                                if(form_values['to-ports-src']=='range')
                                    send_data['to-ports'] = form_values['to-ports-from']+'-'+form_values['to-ports-to'];

                                break;
                  case 'DNAT':
                                if(form_values['to-destination-src']=='range'){
                                    var to_destination_port = form_values['to-destination-port-from'];
                                    var to_destination = form_values['to-destination-from'];

                                    if(form_values['to-destination-port-to']) to_destination_port += '-'+form_values['to-destination-port-to'];
                                    if(form_values['to-destination-to']) to_destination += '-'+form_values['to-destination-to'];

                                    if(to_destination_port)
                                        send_data['to-destination'] = to_destination+':'+to_destination_port;
                                    else
                                        send_data['to-destination'] = to_destination;

                                    send_data.j = form_values['j'];
                                }
                                break;

                  case 'SNAT':
                                if(form_values['to-source-src']=='range'){
                                    var to_source_port = form_values['to-source-port-from'];
                                    var to_source = form_values['to-source-from'];

                                    if(form_values['to-source-port-to']) to_source_port += '-'+form_values['to-source-port-to'];
                                    if(form_values['to-source-to']) to_source += '-'+form_values['to-source-to'];

                                    if(to_source_port)
                                        send_data['to-source'] = to_source+':'+to_source_port;
                                    else
                                        send_data['to-source'] = to_source;

                                    send_data.j = form_values['j'];
                                }
                                break;
                  case 'REJECT':
                                //reject with
                                switch(form_values['reject-with-src']){
                                    case 'type' :
                                        send_data['reject-with'] = form_values['reject-with'];
                                        send_data.j = form_values['j'];
                                        break;
                                    default:
                                        break;
                                }
                                break;
                      default:
                                send_data.j = form_values['j'];
                                break;
        }





        //source address
        switch(form_values['s-c']){
            case '=' : send_data.s = form_values['s'];
                break;
            case '!' : send_data.s = '! '+form_values['s'];
                break;
            default:break;
        }

        //destination address
        switch(form_values['d-c']){
            case '=' : send_data.d = form_values['d'];
                break;
            case '!' : send_data.d = '! '+form_values['d'];
                break;
            default:break;
        }


        //incoming interface
        var i_intf = this.i_intfCombo.getValue();
        if(i_intf=='Other...') i_intf = this.i_intf_other.getValue();

        switch(form_values['i-c']){
            case '=' : send_data.i = i_intf;
                break;
            case '!' : send_data.i = '! '+i_intf;
                break;
            default:break;
        }

        //outgoing interface
        var o_intf = this.o_intfCombo.getValue();
        if(o_intf=='Other...') o_intf = this.o_intf_other.getValue();

        switch(form_values['o-c']){
            case '=' : send_data.o = o_intf;
                break;
            case '!' : send_data.o = '! '+o_intf;
                break;
            default:break;
        }

        //fragmentation
        switch(form_values['f']){
            case '' : send_data.f = '';
                break;
            case '! ' : send_data.f = '!';
                break;
            default:break;
        }
        //send_data.f = form_values['f'];

        //network proto
        var p = this.protoCombo.getValue();
        if(p=='Other...') p = this.proto_other.getValue();

        switch(form_values['p-c']){
            case '=' : send_data.p = p;
                break;
            case '!' : send_data.p = '! '+p;
                break;
            default:break;
        }

        //source ports
        var sportvalue = form_values['sports'];

        if(form_values['sport-src']=='range'){
            sportvalue = form_values['sport-from']+':'+form_values['sport-to'];
        }

        var multisport = 0;
        if(sportvalue.match(/[,;]/)) multisport = 1;
        switch(form_values['sport-c']){
            case '=' :
                if(multisport) send_data.sports = sportvalue;
                else send_data.sport = sportvalue;
                break;
            case '!' :
                if(multisport) send_data.sports = '! '+sportvalue;
                else send_data.sport = '! '+sportvalue;
                break;
            default:break;
        }

        //destination ports
        var dportvalue = form_values['dports'];

        if(form_values['dport-src']=='range'){
            dportvalue = form_values['dport-from']+':'+form_values['dport-to'];
        }

        var multidport = 0;
        if(dportvalue.match(/[,;]/)) multidport = 1;
        switch(form_values['dport-c']){
            case '=' :
                if(multidport) send_data.dports = dportvalue;
                else send_data.dport = dportvalue;
                break;
            case '!' :
                if(multidport) send_data.dports = '! '+dportvalue;
                else send_data.dport = '! '+dportvalue;
                break;
            default:break;
        }

        //source and destination ports
        switch(form_values['ports-c']){
            case '=' :
                send_data.ports = form_values['ports'];
                break;
            case '!' :
                send_data.ports = '! '+form_values['ports'];
                break;
            default:break;
        }


        //tcp_flags

        var send_tcp_flags = this.build_tcp_flags(form_values);
        if(send_data.p=='tcp')
            switch(form_values['tcp-flags-c']){
                case '=' :
                    send_data['tcp-flags'] = send_tcp_flags.flags_set+' '+send_tcp_flags.flags;
                    break;
                case '!' :
                    send_data['tcp-flags'] = '! '+send_tcp_flags.flags_set+' '+send_tcp_flags.flags;
                    break;
                default:break;
            }


        //tcp_option
        switch(form_values['tcp-option-c']){
            case '=' :
                send_data['tcp-option'] = form_values['tcp-option'];
                break;
            case '!' :
                send_data['tcp-option'] = '! '+form_values['tcp-option'];
                break;
            default:break;
        }

        //icmp type
        if(send_data.p=='icmp')
            switch(form_values['icmp-type-c']){
                case '=' :
                    send_data['icmp-type'] = form_values['icmp-type'];
                    break;
                case '!' :
                    send_data['icmp-type'] = '! '+form_values['icmp-type'];
                    break;
                default:break;
            }

        //ethernet address
        switch(form_values['mac-source-c']){
            case '=' :
                send_data['mac-source'] = form_values['mac-source'];
                break;
            case '!' :
                send_data['mac-source'] = '! '+form_values['mac-source'];
                break;
            default:break;
        }



        //packet flow rate
        switch(form_values['limit-c']){
            case '=' :
                send_data['limit'] = form_values['limit']+'/'+form_values['limit-time'];
                break;
            case '!' :
                send_data['limit'] = '! '+form_values['limit']+'/'+form_values['limit-time'];
                break;
            default:break;
        }

        //packet burst rate
        switch(form_values['limit-burst-c']){
            case '=' :
                send_data['limit-burst'] = form_values['limit-burst'];
                break;
            case '!' :
                send_data['limit-burst'] = '! '+form_values['limit-burst'];
                break;
            default:break;
        }

        //connection states
        switch(form_values['state-c']){
            case '=' :
                send_data['state'] = form_values['state'];
                break;
            case '!' :
                send_data['state'] = '! '+form_values['state'];
                break;
            default:break;
        }

        //type of service
        switch(form_values['tos-c']){
            case '=' :
                send_data['tos'] = form_values['tos'];
                break;
            case '!' :
                send_data['tos'] = '! '+form_values['tos'];
                break;
            default:break;
        }

        //additional params
        if(form_values['args']) send_data['args'] = form_values['args'];

        return send_data;



    }
    ,onCreate:function(){

        var send_data = this.getDataSubmit();

        //if index is defined then rule is added at index position
        if(this.index!='undefined')
            send_data.index = this.index;


        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Creating rule...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'add_rule',params:Ext.encode(send_data)},
            failure: function(resp,opt){

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                this.ownerCt.close();
                var msg = 'Rule successfully added';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                if(this.index){

                    var top_panel = Ext.getCmp(this.parent_grid.ownerCt.id);

                    top_panel.items.each(function(f){
                        if(f.isXType('etfw_firewall_gridChain')) f.reload();
                    });

                }else this.parent_grid.reload();
            },scope:this
        });// END Ajax request

    },scope:this
    ,onSave:function() {
        var send_data = this.getDataSubmit();
        send_data.index = this.index;
        


        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Updating rule info...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn


        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'set_rule',params:Ext.encode(send_data)},
            failure: function(resp,opt){

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                this.ownerCt.close();
                var msg = 'Rule edited successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.parent_grid.reload();
            },scope:this
        });// END Ajax request



    } // eo function
    ,build_tcp_flags:function(form_values){
        var flags_array = [];
        if(form_values['syn']) flags_array.push(form_values['syn']);
        if(form_values['ack']) flags_array.push(form_values['ack']);
        if(form_values['fin']) flags_array.push(form_values['fin']);
        if(form_values['rst']) flags_array.push(form_values['rst']);
        if(form_values['urg']) flags_array.push(form_values['urg']);
        if(form_values['psh']) flags_array.push(form_values['psh']);

        var flags_set_array = [];
        if(form_values['syn-set']) flags_set_array.push(form_values['syn-set']);
        if(form_values['ack-set']) flags_set_array.push(form_values['ack-set']);
        if(form_values['fin-set']) flags_set_array.push(form_values['fin-set']);
        if(form_values['rst-set']) flags_set_array.push(form_values['rst-set']);
        if(form_values['urg-set']) flags_set_array.push(form_values['urg-set']);
        if(form_values['psh-set']) flags_set_array.push(form_values['psh-set']);

        var send_tcp_flags = new Object();
        send_tcp_flags.flags = flags_array;
        send_tcp_flags.flags_set = flags_set_array;

        return  send_tcp_flags;
    }




}); // eo extend



// grid chain definition
ETFW.Firewall.Grid = Ext.extend(Ext.grid.GridPanel, {
    initComponent:function() {

        // show check boxes
        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        this.action = new Ext.ux.grid.RowActions({
            header:'Actions'
            ,keepSelection:true
            ,actions:[{
                    iconCls:'icon-down'
                    ,tooltip:'Move rule down'
                },{
                    iconCls:'icon-up'
                    ,tooltip:'Move rule up'
                },{
                    iconCls:'icon-bottom'
                    ,tooltip:'Add rule after this rule'
                },
                  {
                    iconCls:'icon-top'
                    ,tooltip:'Add rule before this rule'
                }]
            ,scope:this
        });


        this.action.on({

            action:function(grid, record, action, row, col) {

                var record = this.store.getAt(row);
                var total_rows = grid.getStore().getCount();

                switch(action){
                        case 'icon-up':
                                        if(row!=0){
                                            var prev_record = this.store.getAt(row-1);
                                            this.moveRule(record.data.index,prev_record.data.index);
                                        }
                                        break;
                      case 'icon-down':
                                        if(row!=(total_rows-1)){
                                            var next_record = this.store.getAt(row+1);
                                            this.moveRule(record.data.index,next_record.data.index);
                                        }
                                        break;
                       case 'icon-top':
                                        var cur_index = record.data.index;
                                        var insert_at = cur_index;
                                        if(!(insert_at==0)) insert_at = cur_index-1;

                                        var form = new ETFW.Firewall.Form({title:'daa',parent_grid:this,index:insert_at,reset:true});

                                        // create and show window
                                        var win = new Ext.Window({
                                            title:'de'
                                            ,layout:'fit'
                                            ,width:800
                                            ,modal:true
                                            ,height:320
                                            ,closable:true
                                            ,border:false
                                            ,items:form
                                        });
                                        win.show();
                                        break;
                    case 'icon-bottom':
                                        var cur_index = record.data.index;
                                        var insert_at = cur_index+1;
                                        var form = new ETFW.Firewall.Form({title:'daa',parent_grid:this,index:insert_at,reset:true});

                                        // create and show window
                                        var win = new Ext.Window({
                                            title:'de'
                                            ,layout:'fit'
                                            ,width:800
                                            ,modal:true
                                            ,height:320
                                            ,closable:true
                                            ,border:false
                                            ,items:form
                                        });
                                        win.show();
                                        break;
                               default: break;
                }

            },scope:this
        },this);



        // column model
        var cm = new Ext.grid.ColumnModel([
            selectBoxModel,
            this.action,
            {header: "Id", width:100,dataIndex: 'index',sortable: true},
            {header: "Action", width:100,dataIndex: 'action',sortable: true},
            {header: "Condition", width:500,dataIndex: 'condition',sortable: true}
        ]);


        var dataStore = new Ext.data.JsonStore({
            url:this.url,
            baseParams:this.baseParams,
            //   id: 'index',
            remoteSort: false,
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'index'},{name:'action'},{name:'condition'}] // initialized from json metadata
        });
        dataStore.setDefaultSort('index', 'ASC');

        //dataStore.loadData(this.defaultData);

        var config = {
            cls:'gridWrap etfw-grid-rules',
            store:dataStore
            ,cm:cm
            ,sm:selectBoxModel
            ,viewConfig:{
                emptyText: 'Empty!',  //  emptyText Message
                deferEmptyText:false
            }
            //  ,viewConfig:{forceFit:true}
            ,loadMask:true
            ,autoHeight:true
            ,plugins:[this.action]

        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });

        this.tbar = [{
                    text:'Add rule',
                    tooltip:'Add a New Rule',
                    iconCls:'add',
                    handler: function(){

                        var form = new ETFW.Firewall.Form({title:'Add Rule',service_id:this.service_id,network_dispatcher:this.network_dispatcher,parent_grid:this,reset:true});
                        // create and show window
                        var win = new Ext.Window({
                            title:'Rule information'
                            ,layout:'fit'
                            ,width:820
                            ,modal:true
                            ,height:420
                            ,closable:true
                            ,border:false
                            ,items:form
                        });
                        win.show();
                    },scope:this
                   },
                   '-',
                   {
                    text:'Edit',
                    ref: '../editBtn',
                    tooltip:'Edit selected rule',
                    disabled:true,
                    handler: function(){
                        var record = this.getSelectionModel().getSelected();
                        var form = new ETFW.Firewall.Form({title:'Edit Rule',service_id:this.service_id,network_dispatcher:this.network_dispatcher,parent_grid:this});
                        // create and show window
                        var win = new Ext.Window({
                            // id:'formloadsubmit-win'
                            title:'Rule information'
                            ,layout:'fit'
                            ,width:820
                            ,modal:true
                            ,height:420
                            ,closable:true
                            ,border:false
                            ,items:form
                        });
                        win.show();

                    },scope:this
                   },
                   '-',
                   {
                    text:'Delete',
                    ref: '../removeBtn',
                    tooltip:'Delete the selected rule(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
        }];


        this.defaultActionStore = new Ext.data.ArrayStore({
            fields:['value','name'],
            data:[['ACCEPT','Accept'],['DROP','Drop'],['QUEUE','Userspace'],['RETURN','Exit chain']]
        });

        this.defaultActionCmb = new Ext.form.ComboBox({
            editable:false,
            width:160,
            valueNotFoundText:'Policy unknown!',
            mode:'local',
            store: this.defaultActionStore,
            value:this.defaultAction,
            triggerAction: 'all',
            //  minChars:3,
            //    minListWidth:250,
            valueField: 'value',
            displayField: 'name',
            forceSelection: true
            //emptyText: 'Enter product name'
        });

        if(this.defaultAction == '-')
            this.bbar = [{tooltip:'Click here to delete chain',
                        text:'Delete chain',
                        handler:this.delChain,
                        scope:this

                    },
                '->',new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        })];
        else
            this.bbar = [
                {tooltip:'Click here to submit changes',
                    text:'Set Default Action',
                    handler:this.setPolicy,
                    scope:this

                },this.defaultActionCmb,'->',new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        })];


        // call parent
        ETFW.Firewall.Grid.superclass.initComponent.apply(this, arguments);

        this.getSelectionModel().on('selectionchange', function(sm){
            this.editBtn.setDisabled(sm.getCount() < 1);
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        // load the store at the latest possible moment
        this.on({
            afterlayout:{scope:this, single:true, fn:function() {

                    //this.store.load();
                    dataStore.loadData(this.defaultData);
                }}
        });




        /************************************************************
         * handle contextmenu event
         ************************************************************/
        this.addListener("rowcontextmenu", onContextMenu, this);
        function onContextMenu(grid, rowIndex, e) {
            grid.getSelectionModel().selectRow(rowIndex);
            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Edit',
                            tooltip:'Edit the selected item',
                            iconCls:'editItem',
                            handler: function(){
                                var form = new ETFW.Firewall.Form({title:'Edit Rule',service_id:this.service_id,network_dispatcher:this.network_dispatcher,parent_grid:this});
                                // create and show window
                                var win = new Ext.Window({
                                    title:'Rule information'
                                    ,layout:'fit'
                                    ,width:820
                                    ,modal:true
                                    ,height:420
                                    ,closable:true
                                    ,border:false
                                    ,items:form
                                });
                                win.show();

                            },scope:this
                        },{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: this.id});
                            },scope:this
                        }]
                });
            }
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }




    } // eo function initComponent
    ,moveRule:function(oldIndex,newIndex){
        var params = Ext.decode(this.baseParams.params);
        var table = params.table;

        var send_data = {"table":table,"index":oldIndex,"to":newIndex};

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Updating rules order...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'move_rule',params:Ext.encode(send_data)},
            failure: function(resp,opt){

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = 'Rules order successfully modified';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

                var top_panel = Ext.getCmp(this.ownerCt.id);
                top_panel.items.each(function(f){
                                if(f.isXType('etfw_firewall_gridChain')) f.reload();
                });

            },scope:this
        });// END Ajax request

    },
    delChain: function(){
        var params = Ext.decode(this.baseParams.params);
        var table = params.table;
        var chain = params.chain;

        var send_data = {"table":table,"chain":chain};

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Deleting chain...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_chain',params:Ext.encode(send_data)},
            failure: function(resp,opt){

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = 'Chain <b>'+chain+'</b> deleted successfully';

                var top_panel = Ext.getCmp(this.ownerCt.id);
                this.ownerCt.remove(this);

                top_panel.items.each(function(f){
                                if(f.isXType('etfw_firewall_gridChain')) f.reload();
                });

                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

            },scope:this
        });// END Ajax request

    },
    setPolicy: function(){
        var policy = this.defaultActionCmb.getValue();
        var params = Ext.decode(this.baseParams.params);
        var table = params.table;
        var chain = params.chain;

        var send_data = {"table":table,"chain":chain,"policy":policy};

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Updating default policy action...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'set_policy',params:Ext.encode(send_data)},
            failure: function(resp,opt){

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = 'Default action for chain <b>'+chain+'</b> updated successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                // this.store.reload();
            },scope:this
        });// END Ajax request

    },
    deleteData:function(items){
        var params = Ext.decode(this.baseParams.params);
        var table = params.table;

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Removing rule(s)...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        var rules = [];

        for(var i=0,len = items.length;i<len;i++){
            rules[i] = {index:items[i].data.index};
        }

        var data = {"table":table,"rules":rules};

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_rules',params:Ext.encode(data)},
            failure: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = 'Rule(s) deleted';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

                var top_panel = Ext.getCmp(this.ownerCt.id);
                top_panel.items.each(function(f){
                        if(f.isXType('etfw_firewall_gridChain')) f.reload();
                });



            },scope:this

        });// END Ajax request

    }
    ,reload:function(){this.store.reload();}
});

Ext.reg('etfw_firewall_gridChain',ETFW.Firewall.Grid);





ETFW.Firewall.Main = function(config){

    Ext.apply(this,config);

    var service_id = this.service_id;
    
    var table_panels = [];    
    this.url = <?php echo json_encode(url_for('etfw/json'))?>;

    this.firewall_imgs = new Object();
    this.firewall_imgs['filter'] =
        {
                            xtype:'box'
                            ,autoEl:{
                                tag:'div',
                                children:[

                                    {
                                     tag:'map'
                                     ,name:'etfw-firewall-rules-'+service_id+'-filter-imgmap'
                                     ,id:'etfw-firewall-rules-'+service_id+'-filter-imgmap'
                                     ,children:[
                                      {
                                          tag:'area'
                                          ,shape:'rect'
                                          ,qtip:'Click here to show INPUT chain'
                                          ,coords:'14,98,62,112'
                                          ,chain:'etfw-firewall-rules-'+service_id+'-filter_INPUT'
                                          ,href:'#'
                                      },
                                      {
                                          tag:'area'
                                          ,shape:'rect'
                                          ,qtip:'Click here to show INPUT chain'
                                          ,coords:'219,98,277,112'
                                          ,chain:'etfw-firewall-rules-'+service_id+'-filter_OUTPUT'
                                          ,href:'#'
                                      }
                                     ]

                                    },
                                    {
                                        tag:'img'
                                        ,qtip:'Click on image chain to display it'
                                        ,src:'/images/firewall_rules.png'
                                        ,usemap:'#etfw-firewall-rules-'+service_id+'-filter-imgmap'
                                        ,style:'margin:10px 0px 10px 10px'}]
                            }
                            };

    ETFW.Firewall.Main.superclass.constructor.call(this, {
                                    stateEvents: ['tabchange'],
                                    activeTab: Ext.state.Manager.get('etfw-firewall-rules-'+service_id+'_active_tab', 0),
                                    listeners: {
                                                'tabchange': function(){
                                                        if(this.getActiveTab()) Ext.state.Manager.set('etfw-firewall-rules-'+service_id+'_active_tab', this.getActiveTab().getId());
                                                }
                                    },
                                    border:false,
                                    items:[]
                                  //  items:table_panels.reverse()
                                    });
    this.on('beforeshow',function(){
        alert('before showing');
    });

    this.on('render',function(){
        this.buildRulesPanels();
    });



}//eof

// define public methods
Ext.extend(ETFW.Firewall.Main, Ext.TabPanel,{
    buildRulesPanel:function(data){
        var table = data['table'];
        var table_string = '';
        var table_data = data['table_data'];
        var boot_active = data['boot_active'];        
        var grids = [];

        switch(table)
        {
            case 'filter' : table_string = 'Packet filtering ('+table+')';
                            break;
            case 'mangle' : table_string = 'Packet alteration ('+table+')';
                            break;
            case 'nat' :    table_string = 'Network address translation ('+table+')';
                            break;
            default:        table_string = 'Not implemented yet!';
        }
        

        for(var chain in table_data){

            var chain_data = table_data[chain];

            var rules = chain_data['rules'];
            var chain_desc = chain_data['chain_desc'];
            var default_action = chain_data['default'];
            var defaultData = rules;

            var grid = new ETFW.Firewall.Grid({
                    title: chain_desc,
                    collapsed:true,
                    //viewConfig: {forceFit: true},
                    url:this.url,
                    defaultAction: default_action,
                    defaultData:defaultData,
                    layout:'fit',
                    service_id:this.service_id,
                    network_dispatcher:this.network_dispatcher,
                    //  autoExpandColumn : 'condition',
                    // autoScroll:true,
                    baseParams:{id: this.service_id,method:'list_rules',params:Ext.encode({"table":table,"chain":chain})}});
            grids.push(grid);

        }


        /*
         * Chains panel
         */

        var accordion_panel = {
                ref:'accordion_panel',
            //    id:table_panel+'_accordion',
                layout:'accordion',
                defaults: {
                    bodyStyle: 'padding:15px',
                    collapsed: true
                },
                layoutConfig: {
                    titleCollapse: false
                    //,activeOnTop: true
                },
                items:grids
        };




        /*
         * Panel top
         */
        var table_img = (this.firewall_imgs[table]) ? this.firewall_imgs[table] : [] ;
        var top = [{
                layout:'table',
                frame:true,
                layoutConfig: {columns: 2},
                items:[{xtype:'button',text:'Add new chain',handler:function(){
                            var tab = this.getActiveTab();
                            var new_chain = tab.newchain;

                            if(new_chain.isValid() && new_chain.getValue())
                                this.addChain(tab.table,new_chain.getValue());
                            },scope:this},
                        {xtype:'textfield',ref:'../newchain',vtype: 'newchain'}]
                }
                ,table_img];


        /*
         * Panel bottom
         *
         */

        var bottom = {
                //id:table_panel+'_bottom',
                layout:'form',frame:true,
                labelAlign:'top',
                items:[
                    {xtype:'button',action:'apply_config',handler:this.applyConfiguration,fieldLabel:'Click this button to make the firewall configuration listed above active. Any firewall rules currently in effect will be flushed and replaced',text:'Apply Configuration',scope:this},
                    {xtype:'button',action:'revert_config',handler:this.applyConfiguration,fieldLabel:'Click this button to reset the configuration listed above to the one that is currently active.',text:'Revert Configuration',scope:this},
                    {xtype:'button',action:'reset_config',handler:this.applyConfiguration,fieldLabel:'Click this button to clear all existing firewall rules and set up new rules for a basic initial configuration.',text:'Reset firewall',scope:this},
                    {xtype:'displayfield', fieldLabel:'Change this option to control whether your firewall is activated at boot time or not.'},
                    {layout:'table',layoutConfig: {columns: 3},
                        items: [
                           // applyBootBtn,
                            {xtype:'button',ref:'activate_bootBtn',text:'Activate on boot',action:'',handler:this.applyConfiguration,style:'padding-right:5px;padding-bottom:10px;',scope:this},
                            {layout:'form',items:[{xtype:'radio', name:'boot_active_'+table+'_'+this.service_id, fieldLabel:'',listeners:{check:function(chkbox,isChecked){if(isChecked) ((chkbox.ownerCt).ownerCt.activate_bootBtn).action='activate_onboot';}},hideLabel:true,boxLabel:'Yes',inputValue: '1', checked: boot_active==1}]},
                            {layout:'form',items:[{xtype:'radio', name:'boot_active_'+table+'_'+this.service_id, fieldLabel:'',listeners:{check:function(chkbox,isChecked){if(isChecked) ((chkbox.ownerCt).ownerCt.activate_bootBtn).action='deactivate_onboot';}},hideLabel:true,boxLabel:'No',inputValue: '0', checked: boot_active==0}]}
                        ]
                    }]
        }; // end bottom panel



        var panel = {
                        autoScroll: true,
                        table: table,
                        title: table_string,
                        bodyStyle: 'padding:0px 0px 0px 0px;',
                        items:[top, accordion_panel, bottom],
                        listeners:{
                            afterlayout:{single:true,scope:this,fn:function(){
                                        var tab = this.getActiveTab();
                                        var imgmap = Ext.get(tab.id+'-imgmap');
                                        if(imgmap)
                                            imgmap.on('click',function(event,html,object){
                                                Ext.getCmp(html.attributes.chain.value).expand();
                                                var grid = Ext.getCmp(html.attributes.chain.value);
                                                grid.getView().focusEl.focus();
                                            });
                            }}
                        }
                    };
                    
        return panel;




        

    }
    ,reload:function(){        
        this.removeAll(true);
        this.buildRulesPanels();
    }
    ,buildRulesPanels:function(){        

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){

                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: <?php echo json_encode(__('Retrieving data...')) ?>,
                        width:300,
                        wait:true,
                        modal: true
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        conn.request({
            url:<?php echo json_encode(url_for('etfw/json'))?>,
            params:{id:this.service_id,method:'get_config_rules'},
            //params:{id:this.serviceId,method:'add_chain',params:Ext.encode(send_data)},
            failure: function(resp,opt){
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var table_panels = [];
                var resp_decoded = Ext.decode(resp.responseText);

                var table_rules = resp_decoded['rules'];                
                var boot_active = resp_decoded['boot_active'];                
                
                for(var table in table_rules)
                    table_panels.push(this.buildRulesPanel({table:table,boot_active:boot_active,table_data:table_rules[table]}));
                
                this.add(table_panels);
                this.doLayout();
                this.setActiveTab(0);

            },scope:this
        });// END Ajax request

        
        

    }
    ,addChain:function(table,chain){
        var send_data = {table:table,chain:chain};
        var tab = this.getActiveTab();        
        var accordion_panel = tab.accordion_panel;

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Adding chain...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'add_chain',params:Ext.encode(send_data)},
            failure: function(resp,opt){

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = 'Added new chain';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

                var defaultData = {"total":0,"data":[]};

                var newGrid = new ETFW.Firewall.Grid({
                    title:'Chain '+chain,
                    collapsed:true,
                    //viewConfig: {forceFit: true},
                    url:this.url,
                    defaultAction: '-',
                    defaultData:defaultData,
                    layout:'fit',
                    service_id:this.service_id,
                    network_dispatcher:this.network_dispatcher,
                    baseParams:{id: this.service_id,method:'list_rules',params:Ext.encode({"table":table,"chain":chain})}});           

                accordion_panel.add(newGrid);

                tab.doLayout();

            },scope:this
        });// END Ajax request
    },
    applyConfiguration:function(b,e){
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Applying...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        conn.request({
            url: this.url,
            params:{id:this.service_id,method:b.action},
            failure: function(resp,opt){

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = b.text+' successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                if(b.action=='revert_config' || b.action=='reset_config' || b.action=='activate_onboot' || b.action=='deactivate_onboot'){

                    this.reload();                    
                }

            },scope:this
        });// END Ajax request
    }
});

</script>