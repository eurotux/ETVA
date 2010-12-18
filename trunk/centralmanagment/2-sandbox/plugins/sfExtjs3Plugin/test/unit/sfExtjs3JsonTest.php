<?php

/*
 * (c) 2009 Leon van der Ree <leon@fun4me.demon.nl>
 *
 */

require_once(dirname(__FILE__).'/../bootstrap/unit.php');

$t = new lime_test(3, new lime_output_color());

$t->diag('Create an empty sfExtjs3Json-instance');
$jsJson = new sfExtjs3Json(array());

$expectedResult = "{}";
$t->is($jsJson->render(), $expectedResult, 'empty sfExtjs3Json-instance renders as expected');


$t->diag('Create a new sfExtjs3Json-instance, associative');
$jsJson = new sfExtjs3Json(array('a' => 'one', 'b' => 'two', 'c' => 'three'));

$expectedResult = "{
a : one,
b : two,
c : three
}";
$t->is($jsJson->render(), $expectedResult, 'sfExtjs3Json-instance, associative renders as expected');


$t->diag('Create a new sfExtjs3Json-instance, associative with assigning');
$jsJson = new sfExtjs3Json(array('a' => 'one', 'b' => 'two', 'c' => 'three'), 'test');

$expectedResult = "test = {
a : one,
b : two,
c : three
};";
$t->is($jsJson->render(), $expectedResult, 'sfExtjs3Json-instance, associative renders as expected');
