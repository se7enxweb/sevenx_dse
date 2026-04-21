<?php
/**
 * @package GitManager
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    26 Sep 2013
 **/

$Module = array(
	'name'      => 'DSE',
	'functions' => array()
);

$ViewList = array(
	'dashboard' => array(
		'script'                  => 'dashboard.php',
		'functions'               => array( 'dse' ),
		'params'                  => array(),
		'default_navigation_part' => 'ezsetupnavigationpart'
	),
	'adminneo' => array(
		'script'                  => 'adminneo.php',
		'functions'               => array( 'dse' ),
		'params'                  => array(),
		'default_navigation_part' => 'ezsetupnavigationpart'
	)
);

$FunctionList = array(
	'dse' => array(),
	'dump' => array()
);