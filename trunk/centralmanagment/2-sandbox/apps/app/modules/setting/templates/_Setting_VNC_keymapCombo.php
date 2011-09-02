<script>
Ext.ns("Setting.VNC");

/*
 * VNC Keymap combo with default values
 */
Setting.VNC.keymapCombo = Ext.extend(Ext.form.ComboBox, {
    fieldLabel:     'VNC keymap',
    width:          80,
    displayField:   'kmap',
    name:           'vnc_keymap',
    hiddenName:     'vnc_keymap',
    mode:           'local',
    forceSelection: true,
    triggerAction:  'all',
    editable:       false,
    initComponent:  function(){

        this.store = new Ext.data.ArrayStore({
                                    fields: ['kmap'],
                                    data : [
                                        ['en-gb'],
                                        ['en-us'],
                                        ['es'],
                                        ['fr'],
                                        ['pt'],
                                        ['it']],
                                    sortInfo:{field:'kmap',direction:'ASC'}});



        Setting.VNC.keymapCombo.superclass.initComponent.call(this);

    }//Fim init
});

</script>