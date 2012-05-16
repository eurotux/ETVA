<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
use_helper('Extjs');

?>
<script>
Ext.ns('Node.ViewUnassigned');

Node.ViewUnassigned.Main = function(config) {

    Ext.apply(this,config);    
    
    Node.ViewUnassigned.Main.superclass.constructor.call(this, {
        activeTab:0,
        items: [ ]
    });

};

// define public methods
Ext.extend(Node.ViewUnassigned.Main, Ext.TabPanel,{
});

</script>
