<?php
/*
Plugin Name: Easy Digital Downloads Export to Ecomail
Plugin URL: https://cleverstart.cz
Description: Export emails given by the customers when downloading to Ecomail
Version: 0.0.26
Author: Pavel Janíček
Author URI: http://cleverstart.cz
*/

include_once __DIR__ . '/vendor/autoload.php';
/*$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://plugins.cleverstart.cz/?action=get_metadata&slug=edd-ecomail',
	__FILE__, //Full path to the main plugin file or functions.php.
	'edd-ecomail'
);*/

require_once  __DIR__ . '/libs/class_edd_ecomail.php';

$mautic = new Clvr_EDD_Ecomail();

