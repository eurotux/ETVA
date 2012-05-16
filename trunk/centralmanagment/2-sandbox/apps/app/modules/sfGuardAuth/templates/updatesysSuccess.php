
<?php
use_stylesheet('main.css');

$sfExtjs3Plugin = new sfExtjs3Plugin(array('theme'=>'blue'),array('js' => sfConfig::get('sf_extjs3_js_dir').'src/locale/ext-lang-'.$sf_user->getCulture().'.js'));
$sfExtjs3Plugin->load();

?>

<script type='text/javascript'>

Ext.MessageBox.show({
    title: <?php echo json_encode(__('Warning - Central Management')) ?>,
    msg: <?php echo json_encode(__('Please update Central Management DB')) ?>,
    minWidth: 300,
    renderTo: Ext.get('updatedb-id'),
    //buttons: Ext.MessageBox.OK,
    //fn: Login.focusForm,
    icon: Ext.MessageBox.ERROR
});

</script>


<div class="header-login <?php echo sfConfig::get('config_acronym'); ?>">
</div>
<div id='updatedb-id'/>


<div class="footer-login"></div>
