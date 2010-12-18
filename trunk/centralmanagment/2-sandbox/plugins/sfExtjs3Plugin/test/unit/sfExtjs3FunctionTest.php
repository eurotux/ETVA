<?php

/*
 * (c) 2009 Leon van der Ree <leon@fun4me.demon.nl>
 *
 */

require_once(dirname(__FILE__).'/../bootstrap/unit.php');

$t = new lime_test(5, new lime_output_color());

$t->diag('Create an empty sfExtjs3Function-instance, no arguments');
$jsFunc = new sfExtjs3Function(array(), '');

$expectedResult = "function() {}";
$t->is($jsFunc->render(), $expectedResult, 'Empty function, no arguments renders as expected');


$t->diag('Create an empty sfExtjs3Function-instance, one argument');
$jsFunc = new sfExtjs3Function(array('first'), '');

$expectedResult = "function(first) {}";
$t->is($jsFunc->render(), $expectedResult, 'Empty function, one argument renders as expected');


$t->diag('Create an empty sfExtjs3Function-instance, two arguments');
$jsFunc = new sfExtjs3Function(array('first', "'value'"), '');

$expectedResult = "function(first, 'value') {}";
$t->is($jsFunc->render(), $expectedResult, 'Empty function, two arguments renders as expected');


$t->diag('Create a new string sfExtjs3Function-instance, no arguments');
$jsFunc = new sfExtjs3Function(array(), "alert('hello');");

$expectedResult = "function() {
alert('hello');
}";
$t->is($jsFunc->render(), $expectedResult, 'string function, no arguments renders as expected');


$t->diag('Create a new array sfExtjs3Function-instance, no arguments');
$jsFunc = new sfExtjs3Function(array(), array("alert('hello');", "alert('hello');"));

$expectedResult = "function() {
alert('hello');
alert('hello');
}";
$t->is($jsFunc->render(), $expectedResult, 'array function, no arguments renders as expected');