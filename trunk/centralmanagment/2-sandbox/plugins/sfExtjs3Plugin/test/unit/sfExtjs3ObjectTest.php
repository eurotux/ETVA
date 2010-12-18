<?php

/*
 * (c) 2009 Leon van der Ree <leon@fun4me.demon.nl>
 *
 */

require_once(dirname(__FILE__).'/../bootstrap/unit.php');

$t = new lime_test(7, new lime_output_color());

$t->diag('Create a new sfExtjs3Object-instance without namespace,based on other class');
$jsObj = new sfExtjs3Object('TestStore', 'Ext.data.Store');

$expectedResult = "TestStore = Ext.extend(Ext.data.Store, {});
";
$t->is($jsObj->renderDefinition(), $expectedResult, 'Empty class without namespace, based on other class renders as expected');

$t->diag('Create a new sfExtjs3Object-instance with namespace, based on other class');
$jsObj = new sfExtjs3Object('Ext.app.sx.TestStore', 'Ext.data.Store');

$expectedResult = "Ext.app.sx.TestStore = Ext.extend(Ext.data.Store, {});
";
$t->is($jsObj->renderDefinition(), $expectedResult, 'Empty class with namespace and base renders as expected');

$t->diag('Add constructor to the object');
$emptyFunction = new sfExtjs3Function(array('c'), '');
$jsObj->addFunction('constructor', $emptyFunction);

$expectedResult = "Ext.app.sx.TestStore = Ext.extend(Ext.data.Store, {
constructor : function(c) {}
});
";
$t->is($jsObj->renderDefinition(), $expectedResult, 'Class with Constructor renders as expected');

$t->diag('Add an extra function to the object');
$extraFunction = new sfExtjs3Function(array(), 'alert("test");');
$jsObj->addFunction('extraFunction', $extraFunction);

$expectedResult = "Ext.app.sx.TestStore = Ext.extend(Ext.data.Store, {
constructor : function(c) {},
extraFunction : function() {
alert(\"test\");
}
});
";
$t->is($jsObj->renderDefinition(), $expectedResult, 'Class with Constructor and extraFunction renders as expected');


$t->diag('Call some function of this object');
$result = $jsObj->renderFunctionCall('superclass.someFunction');

$expectedResult = "Ext.app.sx.TestStore.superclass.someFunction()";
$t->is($result, $expectedResult, 'Function call of this Class renders as expected');


$t->diag('Call some function of this object with argument');
$result = $jsObj->renderFunctionCall('superclass.someFunction', array("arg1"));

$expectedResult = "Ext.app.sx.TestStore.superclass.someFunction(arg1)";
$t->is($result, $expectedResult, 'Function call with argument renders as expected');


$t->diag('render the Registration of the xtype');
$result = $jsObj->registerXtypeAs('teststore');

$expectedResult = "Ext.reg('teststore', Ext.app.sx.TestStore);
";
$t->is($result, $expectedResult, 'registering the xtype renders as expected');

