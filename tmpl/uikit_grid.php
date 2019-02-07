<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('TagsHelperRoute', JPATH_BASE . '/components/com_tags/helpers/route.php');
include_once('helpers/substring_sentence.php');

$nxdebug = $params->get('nxdebug', 0);
$errors = array();
class ErrorMsg{
	function __construct($type,$msg)
    { 
		$this->type = $type;
		$this->msg = $msg;

		$errors[] = $this;
	}

	public function pushObject() {
        return $this;
    }
}
/*
$a = new ErrorMsg('img','Eine Message');
$errors[] = $a->pushObject();
$b = new ErrorMsg('title','Eine andere Message');
$errors[] = $b->pushObject();
*/



function firstWord($string){
	$arr = explode(' ',trim($string));
	return $arr[0];
}; 

function secondWord($string){
	$arr = explode(' ',trim($string));
	return $arr[1];
};

function applyBasicRule($input, $setup){
	switch($setup->mode){
		case 'before':
			$string = $setup->value . trim($input->fieldvalue);
			break;
		case 'after':
			$string = trim($input->fieldvalue) . $setup->value;
			break;
		default:
			$string = trim($input->fieldvalue);
	};
	return $string;
};

function customRules($input, $rules){
	// This function manipulates the string if setted up
	// It appends strings in before or after the string (example: 21 --> #21)
	// Input as Object {'fieldname': 'name-of-field', 'fieldvalue': 'Ein String'}
	// Setup as object {'fieldname': 'name-of-field', 'mode': ['before', 'after'], 'value': '#'}
	// returns string: #Ein String
	
	foreach($rules as $rule){
		$rule_fieldname  = $rule->customfield_for_rule;

		if($rule_fieldname === $input->fieldname){
			// Break if we are not in the correct context (modal / card / always)
			if($rule->rule_target !== 'always' && $rule->rule_target !== $input->context) break;
			$setup = (object) ['mode' => $rule->rule_type, 'value' => $rule->rule_string_to_add];
			// As function because we could make it bigger if necessary
			$html = applyBasicRule($input, $setup);
		
			break;
		};
	};
	
	if(empty($html)) $html = trim($input->fieldvalue);

	return $html;

};

function multiexplode ($delimiters,$string) {
	//  php at metehanarslan dot com ¶
	//  http://php.net/manual/de/function.explode.php
	$ready = str_replace($delimiters, $delimiters[0], $string);
	$launch = explode($delimiters[0], $ready);
	return  $launch;
};

function getImageUrl($item, $src_setup, $fieldname = null){
	$img_url = '';
	$img_err = '';
	switch($src_setup){
		case 'customfield':
			if(!empty($fieldname)){
				if (array_key_exists($fieldname, $item->fields)) {
					$img_url = $item->fields[$fieldname]->rawvalue;
				}else{
					$img_err .= 'Defined Image Field (<i>'.$fieldname.'</i>) not found<br/>';
					$error = new ErrorMsg('img', $img_err);
					$errors[] = $error->pushObject();
				}
			}else{
				$img_err .= 'Define Image Field for elements in Module Backend<br/>';
			}
		break;
		case 'image_intro':
		case 'image_fulltext':
			$core_images = json_decode($item->core_images);
			$img_url = $core_images->$src_setup;
		break;
		case 'none':
		default:
		// nope
	};
	return [$img_url, $img_err];
};

function imagebysetup($item, $context, $params, &$errors){
	// returns the correct image URL & Error based on article / customfield & backend setup
	// get debug state
	$nxdebug = $params->get('nxdebug', 0);
	// Create the img object
	$img = new stdClass();


	//First we check if there is an image field given if not we take our fallback image person.png
	$backup_img = 'modules/mod_tags_selected_advanced/tmpl/assets/img/person.png';
	$img_err = '';

	// Call the getURL Function based on context (for card or modal)
	switch ($context){
		case 'modal':
			$src = $params->get('image_in_modal');
			$image_in_modal = ($params->get('image_in_modal') == 'same') ? $params->get('image_source') : $params->get('image_in_modal'); // if its same use image_source as source
			$url = getImageUrl($item, $image_in_modal, $params->get('customfield_for_modal_image', null) );
			$img_src_setup = $params->get('customfield_for_modal_image');
			break;
		case 'card':
		default:
			$src = $params->get('image_source');
			$url = getImageUrl($item, $params->get('image_source','image_intro'), $params->get('customfield_for_image', null) );
			$img_src_setup = $params->get('customfield_for_image');
	};
	$use_img = ($src == 'none') ? false : true;
	$img_url = $url[0];
	$img_err .= $url[1];

	// Last check if image exists, if not we print out an error
	if (isset($img_url) && !file_exists($img_url) && !empty($simg_url)) {
		$img_err .= 'Die Datei "'.$img_url.'" existiert nicht Datei "'.$backup_img.'" wird geladen!';
		$error = new ErrorMsg('img', $img_err);
		$errors[] = $error->pushObject();
		$img_url = $backup_img;
	}
	elseif(empty($img_url) || !isset($img_url))
	{
		if($nxdebug)
		{
			$img_err .= 'Keine Bildinformationen hinterlegt im Feld '.$img_src_setup.' Datei "'.$backup_img.'" wird geladen!';
			$error = new ErrorMsg('img', $img_err);
			$errors[] = $error->pushObject();
		}
		$img_url = $backup_img;
	};


	// insert the data into the object
	$img->use = $use_img;
	$img->url = $img_url;
	$img->src = $img_src_setup;
	$img->pos = $params->get('image_pos', 'top');
	$img->error = '<div class="uk-position-top uk-position-z-index"><div class="uk-alert uk-alert-warning">'.$img_err.'</div></div>';

	return $img;
};


class CardField{

	public static function buildSimpleTable($array_of_fields){
		$construct = '<table class="uk-table uk-table-divider"><tbody>';
		foreach($array_of_fields as $fieldname){
	
			if(strpos($fieldname, '%') !== false){
				// Spacer
				echo ' '.$fieldname.' hat % drin.';
			}else{
				if(array_key_exists($fieldname, $item->fields)){
					if($display_label){
						$label = $item->fields[$fieldname]->label;
						$construct .= '<tr><td>'.$label.'</td>';
					}
					$value = $item->fields[$fieldname]->value;
					$construct .= '<td>'.$value.'</td>';
	
					if($nxdebug) $construct.= '<td class=" uk-table-shrink uk-alert uk-alert-warning">'.$fieldname.'</td>';
				}else{
					// Fieldname is not setted in content
				}
			}
		};
		$construct .= '</tbody></table>';
		return $construct;
	}
	
	public static function buildResultGrid($fieldsString, $item, $params){
		// there are grouped fields
		$outer = '<div class="uk-child-width-1-2 uk-grid-collapse" uk-grid>';
		//echo 'fieldsString hat groups';
		$array_of_groups = $array_of_groups = explode(',',$fieldsString);
		//print_r($array_of_groups);
		foreach($array_of_groups as $group){
			$array_of_fields = multiexplode(array(" ","\r\n"), trim($group));
			$g_container = '<div class="nx-group"> <div class="uk-child-width-auto uk-grid-collapse uk-flex uk-flex-center" uk-grid>';
	
			foreach($array_of_fields as $fieldname){
				if(array_key_exists($fieldname, $item->fields)){
					$value = $item->fields[$fieldname]->value;
					$g_container .= '<div><div class="uk-padding-small">'.$value.'</div></div>';
				}else{
					$g_container .= '<div><div class="uk-padding-small">Customfield not found</div></div>';
				}
			};
	
			$g_container .= '</div></div>';
			$outer .= $g_container;
		}
		$outer .= '</div>';
		return $outer;
	}

};


function cardFieldsRender($item, $params, &$errors){
	// Setup
	$html = ''; // Container for Output
	$nxdebug = $params->get('nxdebug', 0);
	$display_label = intval($params->get('fields_front_display_label',0));
	
	if(!empty($params->get('fields_to_render_front')))
	{
		$fieldsString = trim($params->get('fields_to_render_front'));

		// Grouped Fields
		// Check if there are grouped Fields to render (by comma)
		if(strpos($fieldsString, ',') !== false){
			// create resultgrid
			$html = CardField::buildResultGrid($fieldsString, $item, $params);
		}else{
			// No grouped fields just space separated fieldnames
			$array_of_fields = multiexplode(array(" ","\r\n"), $fieldsString);
			$html = CardField::buildSimpleTable($array_of_fields);
		};
		
	}else{
		// No fields to render defined - so no rendering happens
	}

	return $html;
};

function textCardRender($item, $params, &$errors){
	// Renders the Title and the Textfield on the Cards (Frontview)
	// Prepare Content based on setup
	$nxdebug = $params->get('nxdebug', 0);
	$alignement = $params->get('content_alignement', 'left');
	$title_tag = $params->get('title_tag','h3');
	$title_cls= $params->get('title_cls','');
	$text_tag = $params->get('text_tag','span');
	$text_cls= $params->get('text_cls','');
	// Customfield rules
	$rules = $params->get('customfield_rules',[]);
	$html = '';
	$error = '';
	// Prepare title based on setup
	switch($params->get('element_title_src')){
		case 'customfield':
			if(!empty($params->get('customfield_for_title')))
			{
				$cf_name_field = $params->get('customfield_for_title');
				$cf_name = explode(" ", $cf_name_field);
				$title = '';
				foreach($cf_name as $fieldname){
					if(!empty($error)) continue;
					if(strlen($title) > 1) $title .= ' ';
					if(array_key_exists($fieldname, $item->fields))
					{
						// Object for customfield rules
						$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => 'card']; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
						$title .= customRules($cf_object, $rules); 																			//$item->fields[$fieldname]->value;
					}else{	
						$error .= 'Given Customfield ' . $fieldname . ' for Title not found!';
						if(strlen($title) == 0) {
							$error .= ' - Article Title used instead!';
							$title = $item->core_title;
						};
					};
				}
			}else
			{
				$title = $item->core_title;
				$error .= 'Customfield for Title is not configured - Article Title used instead!';
			};
			break;
		case 'article_title':
		default:
			$title = $item->core_title;
	};
	// prepare text based on setup
	$text = '';
	switch($params->get('element_text_src')){
		case 'none':
			break;
		case 'customfield':
			if(!empty($params->get('customfield_for_text')))
			{
				$cf_name_field = $params->get('customfield_for_text');
				$cf_name = explode(" ", $cf_name_field);

				foreach($cf_name as $fieldname){
					if(!empty($error)) continue;
					if(strlen($text) > 1) $text .= ' ';
					if(array_key_exists($fieldname, $item->fields))
					{
						// Object for customfield rules
						$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => 'card']; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
						$text .= customRules($cf_object, $rules);  //$item->fields[$fieldname]->value;
					}else{	
						$error .= 'Given Customfield ' . $fieldname . ' for Text not found!';
						if(strlen($text) == 0) {
							$error .= ' - Article Text used instead!';
							$text = $item->core_body;
						};
					};
				};
			}else
			{
				$text = $item->core_body;
				$error .= 'Customfield for text is not configured - Article Text used instead!';
			}
		break;
		case 'article_text';
		default:
			$text = $item->core_body;
	};
	// Truncate textstring if setted up
	if(intval($params->get('content_text_truncate'))){
		$text = sentenceTrim($text, intval($params->get('content_text_truncate')) );
	};
	// build the HTML
	switch($params->get('element_layout')){
		case 'image_card':
			$html .=	'<div class="uk-position-relative uk-text-'.$alignement.'">';
			if($nxdebug && !empty($error)) $html .= '<div class=""><div class="uk-alert uk-alert-danger">'.$error.'</div></div>';
			$html .= 		'<'.$title_tag.' class="'.$title_cls.'">'.$title.'</'.$title_tag.'>';
			$html .=		'<div class="uk-position-relative">';
			$html .=			'<'.$text_tag.' class="'.$text_cls.'">';
			$html .= 				$text;
			$html .=			'</'.$text_tag.'>';
			$html .=		'</div>';
			$html .=	'</div>';
			break;
		case 'default_card':
		default:
			$html .=	'<div class="uk-card-body uk-text-'.$alignement.' uk-position-relative">';
			if($nxdebug && !empty($error)) $html .= '<div class=""><div class="uk-alert uk-alert-danger">'.$error.'</div></div>';
			$html .= 	'<'.$title_tag.' class="'.$title_cls.'">'.$title.'</'.$title_tag.'>';
			$html .=		'<'.$text_tag.' class="'.$text_cls.'">';

			$html .=			$text.'</'.$text_tag.'>';
			$html .=	'</div>';
	};

	return $html;
};

function imageCardRender($item, $img, $params, &$errors){
	$html = '';
	$nxdebug = $params->get('nxdebug',0);

	// Display message if something isn't right about the image configuration
	if(!empty($img->error) && $nxdebug){
		$html .=		'<div class="uk-position-top uk-padding-small">'.$img->error.'</div>';
	};
	$cover = intval($params->get('image_cover',0));
	$height = '';
	if($params->get('image_container_height') !== 'none'){
		$height = 'uk-height-'.$params->get('image_container_height');
	}

	switch($params->get('element_layout')){
		case 'image_card':
			$overlay_type = $params->get('overlay_type','bottom');
			$overlay_style = $params->get('overlay_style','default');
			$overlay_content_style = $params->get('overlay_content_style','light');
			$overlay_content_position = $params->get('overlay_content_position','center');		// used in Overlay Cover Mode

			if($params->get('overlay_transition','none') !== 'none'){
				$overlay_transition = $params->get('overlay_transition');
				$transition_toggle = 'uk-transition-toggle " tabindex="0">';
			}else{
				$overlay_transition = '';
				$transition_toggle = '">';
			}
			
			

			if($overlay_type !== 'none') $html .= '<div class="uk-inline-clip uk-width-1-1 '.$transition_toggle;

			$html .= '<img src="'.$img->url.'" width="100%" alt="">';
			if($overlay_type !== 'none'){
				if($overlay_type == 'cover') {
					$html .= 	'<div class="'.$overlay_transition.' uk-overlay-'.$overlay_style.' uk-padding-small uk-position-cover"></div>';
					$html .= 	'<div class="uk-overlay uk-position-'.$overlay_content_position.' uk-'.$overlay_content_style.'">';
					$html .= 	textCardRender($item, $params, $errors);
					if($params->get('linktype') == 'button'){
						// Renders the Link Button based on setup:
						$html .= linkRender($item, $params);
					};
					$html .=	'</div>';
				}else{
					$html .= 	'<div class="'.$overlay_transition.' uk-overlay uk-overlay-'.$overlay_style.' uk-padding-small uk-position-'.$overlay_type.'">';
					$html .= 	textCardRender($item, $params, $errors);
					if($params->get('linktype') == 'button'){
						// Renders the Link Button based on setup:
						$html .= linkRender($item, $params);
					};
					$html .=	'</div>';
				};

			};
            
			if($params->get('linktype') == 'full'){
				// if the whole card should be clickable
				$html .= linkRender($item, $params);
			};

			if($overlay_type !== 'none') $html .= '</div>';

			break;
		case 'default_card':
		default:
			if(in_array($img->pos, array('top','bottom')) && $params->get('element_layout') === 'default_card' ){
				//Switch for Cover image Container on Top or bottom
				if($cover){
					$html .= '<div class="uk-card-media-'.$img->pos.' uk-cover-container '.$height.'">';
						$html .= '<img src="'.$img->url.'" width="100%" uk-cover alt="">';
						$html .= '<canvas width="" height=""></canvas>';
					$html .= '</div>';
		
				}else{
					$html .= '<div class="uk-card-media-'.$img->pos.'">';
						$html .= '<img src="'.$img->url.'" width="100%" alt="">';
					$html .= '</div>';
				};			
			}
			elseif(in_array($img->pos, array('left','right'))){
				$classes = '';
				if($img->pos == 'right') $classes .= 'uk-flex-last@s ';
		
				$html .= '<div class="'.$classes.'uk-card-media-'.$img->pos.' uk-cover-container '.$height.' uk-width-'.$params->get('image_container_width','1-3').'">';
					$html .= '<img src="'.$img->url.'" width="100%" alt="" uk-cover>';
					$html .= '<canvas width="600" height="400"></canvas>';
				$html .= '</div>';
			};
	}

	
	return $html;
};

function linkRender($item, $params){
	$html = '';
	$linktext = '';
	$attr = '';
	$classes = '';
	$target= $params->get('linktarget','_blank');
	$outer_start = '';
	$outer_end = '';
	
	
	// switch between Modes Link to display a modal or link to item 
	switch($params->get('link_mode')){
	case 'modal':
		$link = '#nx-modal-'.$item->content_item_id;
		$attr = 'uk-toggle';
		break;
	case 'article':
	default:
		$link = JRoute::_(TagsHelperRoute::getItemRoute($item->content_item_id, $item->core_alias, $item->core_catid, $item->core_language, $item->type_alias, $item->router)); 	// Default we use the item link for the article
	}

	// switch between button or full element link rendering
	switch($params->get('linktype')){
		case 'full':
			$classes = 'uk-position-cover';
			break;
		case 'button':
		default:
			$outer_start = '<div class="uk-width-1-1 '.$params->get('button_margin').' uk-flex uk-flex-'.$params->get('button_alignement','center').'">';
			$classes = 'uk-button uk-button-'.$params->get('buttonstyle','primary').' '.$params->get('button_size','primary') . ' ' . $params->get('button_width','uk-width-1-2');
			$linktext = $params->get('linktext','Read more');
			$outer_end = '</div>';

	};
	$html = $outer_start.'<a class="'.$classes.'" href="'.$link.'" target="'.$target.'" '.$attr.'>'.$linktext.'</a>'.$outer_end;
	return $html;
};


function buildElement($item, $params, &$errors){
	// Builds the HTML Element for display
	// get the image url based on setup
	$img = imagebysetup($item, 'card', $params, $errors);

	$ukgrid = '';

	$containerClasses = 'uk-card uk-card-' . $params->get('card_style','default') . ' uk-card-' . $params->get('card_size','small');
	
	if(intval($params->get('card_hover'))){
		$containerClasses .= ' uk-card-hover';
	};

	if(in_array($img->pos, array('left','right')) && $params->get('element_layout') !== 'image_card'){
		$containerClasses .= ' uk-grid-collapse uk-child-width-1-2@s uk-margin';
		$ukgrid = 'uk-grid';
	}
	

	// Build the html container
	$html = 	'<div class="uk-position-relative ">';
	$html.= 		'<div class="'.$containerClasses.'" '.$ukgrid.'>';
	

	// Append Image in position top to container if not disabled in backend
	if($img->src !== 'none' && in_array($img->pos, array('top','left','right'))){
		$html .= imageCardRender($item, $img, $params, $errors);
	};

	// Content if mode is set to default card
	$html .= '<div class="uk-position-relative uk-width-expand">';
		$html .= cardFieldsRender($item, $params, $errors);

		// Content if mode is set to default card
		if($params->get('element_layout') === 'default_card') $html .= textCardRender($item, $params, $errors);

		if($params->get('linktype') == 'button' && $params->get('element_layout') !== 'image_card') {
			if(intval($params->get('button_always_bottom'))){
				$html .= '<div class="uk-padding"></div>'; // Padding for Absolute Positioned Button
				$html .= '<div class="uk-position-bottom">' . linkRender($item, $params) . '</div>';
			}else{
				$html .= linkRender($item, $params);
			};
		};
	$html .= '</div>';

	// End of Content

	// Append Image in position bottom to container if not disabled in backend
	if($img->src !== 'none' && $img->pos == 'bottom'){
		$html .= imageCardRender($item, $img, $params, $errors);
	};

	
	// Full Card Link if Card is not image card and img position is top or bottom
	if($params->get('linktype') == 'full' && $params->get('element_layout') !== 'image_card'){
		// if the whole card should be clickable
		if(in_array($img->pos, array('top','bottom')))
		{
			$html .= linkRender($item, $params);
		}
	};
	// Debug Section inside container:
	if(intval($params->get('nxdebug', 0))){
		$html .=		'<div class="nx-debug uk-margin">';
		$html .=			'<pre>' . var_export($item, true) . '</pre>';
		$html .= 		'</div>';
	};
	// End of Debug Section

	$html.= 		'</div>';

	// Append Full Card Link if the card has grid layout with image left or right and its not an image Card type
	if($params->get('linktype') == 'full' && $params->get('element_layout') !== 'image_card'){
		if(in_array($img->pos, array('left','right'))) {
			$html .= linkRender($item, $params);
		};
	};
	$html.= 	'</div>';
	return $html;
};

function buildModal($item, $params, $errors){
	// First we create an array that holds all fields we want to display in the modal (Backend Setting)
	$nxdebug = $params->get('nxdebug',0);
	// Customfield rules
	$rules = $params->get('customfield_rules',[]);
	$construct = '';
	// get the image url based on setup
	$img = imagebysetup($item, 'modal', $params, $errors);

	if($img->use){

		$img_in_construct = '<div class="uk-grid-collapse uk-child-width-1-1@s uk-child-width-expand@m" uk-grid>'.				
								'<div class="uk-card-media-left uk-cover-container uk-width-'.$params->get('modal_image_container_width','1-2').'@m">'.
									'<img src="'.$img->url.'" alt="Spielerfoto" uk-cover>'.
									'<canvas width="600" height="400"></canvas>'.
								'</div>';
	}else{
		$img_in_construct = '<div class="nx-no-image-modal">';
	};

	// Title in Modal
	if(!empty($params->get('title_to_render_modal', ''))){
		$titlesString = trim($params->get('title_to_render_modal', ''));
		$array_of_titles = multiexplode(array(" ","\r\n"), $titlesString);
		$construct .= '<div class="uk-padding-small"><'.$params->get('modal_title_tag','h3').' class="'.$params->get('modal_title_cls','').'">';
		$i = 0;
		foreach($array_of_titles as $fieldname){
			if(array_key_exists($fieldname, $item->fields)){
				$value = $item->fields[$fieldname]->value;
				if($i>0) $construct .= ' ';
				$construct .= $value;
				$i++;
			}else{
				if($nxdebug) $construct.= '<span class="uk-alert uk-alert-warning">'.$fieldname.' not exists</span>';
			};
		};
		$construct .= '</'.$params->get('modal_title_tag','h3').'></div>';
	};

	if(!empty($params->get('fields_to_render_modal')))
	{
		$fieldsString = trim($params->get('fields_to_render_modal'));
		$array_of_fields = multiexplode(array(" ","\r\n"), $fieldsString);
		$construct .= '<table class="uk-table uk-table-divider"><tbody>';
		foreach($array_of_fields as $fieldname){
			if(array_key_exists($fieldname, $item->fields)){
				
				$label = $item->fields[$fieldname]->label;

				$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => 'modal']; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
				$value = customRules($cf_object, $rules);  //$value = $item->fields[$fieldname]->value;

				$construct .= '<tr>';
				if(intval($params->get('fields_modal_display_label',1))){
					$construct .= '<td>'.$label.'</td>';
				};

				$construct .= '<td>'.$value.'</td>';
				if($nxdebug) $construct.= '<td class=" uk-table-shrink uk-alert uk-alert-warning">'.$fieldname.'</td>';
				$construct .= '</tr>';
			}else{
				if($nxdebug) $construct.= '<td class=" uk-table-shrink uk-alert uk-alert-warning">'.$fieldname.' not exists</td>';
			};
		};
		$construct .= '</tbody></table>';
	}else{
		$error = '<b>No Customfields configured for display</b> - checkout manual to properly configure the module';
	};

	// Classes for Modal Container
	$modal_cls = 'uk-flex-top ';

	if($params->get('modal_container_type', 'container') === 'container'){
		$modal_cls .= 'uk-modal-container ';
	};

	$modal_cls .= $params->get('modal_container_cls');

	//if(strlen($error)>1) $construct .= $error;
	
	$html = 	'<div id="nx-modal-'.$item->content_item_id.'" class="'.$modal_cls.'" uk-modal>';
	$html .= 		'<div class="uk-modal-dialog uk-margin-auto-vertical">';
	$html .= 			'<button class="uk-modal-close-default" type="button" uk-close></button>';
	$html .= 				$img_in_construct;
	$html .= 				'<div>';
	$html .= 					$construct;
	$html .= 				'</div>';
	$html .= 			'</div>';
	$html .= 		'</div>';
	$html .= 	'</div>';

	return $html;
};

if($nxdebug){/*
?>
	<div class="uk-alert uk-alert-warning uk-width-1-1 uk-padding-small">
		<?php var_dump($errors); foreach($errors as $error) echo $error->msg.'<br/>';?>
	</div>
<?php */
};

?>
<div class="nx-tagsselectedadvanced nx-tags-grid-member uk-position-relative">
	<div class="uk-child-width-1-1@s uk-child-width-1-<?php echo $grid_columns . '@m ' . $grid_cutter . $grid_divider . $grid_match; ?>" uk-grid>
	<?php
		foreach($items as $element){
			// Elements' Link
			$itemlink = JRoute::_(TagsHelperRoute::getItemRoute($element->content_item_id, $element->core_alias, $element->core_catid, $element->core_language, $element->type_alias, $element->router));
			
			// render the Element
			echo buildElement($element, $params, $errors);
			if($params->get('link_mode') == 'modal') echo buildModal($element, $params, $errors);

		};
	?>

	</div>
</div>


<?php
if($nxdebug){echo "<h2>nx-debug</h2><hr><h4>Parameters</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($params, true) . ";\n?>");};
if($nxdebug){echo "<hr><h4>Article Setup</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($items, true) . ";\n?>");};
?>
