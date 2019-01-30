<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_tags_similar
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

$header_tag = $params->get('header', 'h3');

//Slideshow
$slideshow_animation = $params->get('slideshow_animation', 'slide');
$slideshow_autoplay = $params->get('slideshow_autoplay', 'true');
$slideshow_pause_on_hover = $params->get('slideshow_pause_on_hover', 'true');
$slideshow_interval = $params->get('slideshow_interval', 5000);
$slideshow_viewportheight = intval($params->get('slideshow_viewportheight', '0'));
$slideshowminheight = intval($params->get('slideshowminheight', '300'));
if($slideshow_viewportheight){
	$viewportsetup = 'uk-height-viewport="min-height:'.$slideshowminheight.'"';
}else{
	$viewportsetup = '';
}

//Grid
$grid_columns = $params->get('grid_columns', '3');
$grid_cutter = $params->get('grid_cutter', 'uk-grid-small');
if(intval($params->get('grid_match'))){
	$grid_match = ' uk-grid-match';
}else{
	$grid_match = '';
};
if($params->get('grid_divider')){
	$grid_divider = ' uk-grid-divider ';
}else{
	$grid_divider = '';
};
$element_layout = $params->get('element_layout', 'image_card');
$card_style = $params->get('card_style', 'default');
$displayImg = intval($params->get('display_image_on_card', '1'));
$mediapos = $params->get('image_position', 'top');

// Link
$linktype = $params->get('linktype', 'full');
$linktarget = $params->get('linktarget', '_parent');
$buttontext = $params->get('buttontext','Button');
$buttonmargin = $params->get('buttonmargin', 'uk-padding-remove');
$buttonstyle = $params->get('buttonstyle', 'default');

//Overlay & Content
$content_text_truncate = intval($params->get('content_text_truncate', '0'));
$content_sentence_truncate = intval($params->get('content_sentence_truncate', '5'));
$alignement = $params->get('content_alignement', 'center');
$overlay_width = $params->get('overlay_width', 'medium');
$overlay_style = $params->get('overlay_style', 'default');
$overlay_transition = $params->get('overlay_transition','uk-transition-fade');
$customcss = $params->get('customcss', '');
$moduleclass_sfx 	= htmlspecialchars($params->get('moduleclass_sfx'));

// Customfields
//$cfields_to_render = explode(" ", $params->get('fields_to_render'));		// Array of field-names which are used in rendering

$nxdebug = intval($params->get('nxdebug','0'));

$layout = $params->get('layoutChoice');
$items = ModTagsselectedHelper::getContentList($params);
require JModuleHelper::getLayoutPath('mod_tags_selected_advanced', $layout);
