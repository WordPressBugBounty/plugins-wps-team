<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

$group_single_name 			= Utils::get_setting( 'group_single_name' );
$location_single_name 		= Utils::get_setting( 'location_single_name' );
$language_single_name 		= Utils::get_setting( 'language_single_name' );
$specialty_single_name 		= Utils::get_setting( 'specialty_single_name' );
$gender_single_name 		= Utils::get_setting( 'gender_single_name' );
$extra_one_single_name 		= Utils::get_setting( 'extra_one_single_name' );
$extra_two_single_name 		= Utils::get_setting( 'extra_two_single_name' );
$extra_three_single_name 	= Utils::get_setting( 'extra_three_single_name' );
$extra_four_single_name  	= Utils::get_setting( 'extra_four_single_name' );
$extra_five_single_name  	= Utils::get_setting( 'extra_five_single_name' );

$filter_txt         = _x( 'Filter', 'Admin', 'wpspeedo-team' );
$label_txt          = _x( 'Label', 'Admin', 'wpspeedo-team' );
$select_txt         = _x( 'Select', 'Admin', 'wpspeedo-team' );
$filter_all_txt     = _x( 'Filter All Text', 'Admin', 'wpspeedo-team' );
$search_filter_txt  = _x( 'Search Filter Text', 'Admin', 'wpspeedo-team' );
$include_by_txt     = _x( 'Include by', 'Admin', 'wpspeedo-team' );
$exclude_by_txt     = _x( 'Exclude by', 'Admin', 'wpspeedo-team' );