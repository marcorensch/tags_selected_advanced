<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_tags_popular
 *
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 * @since       3.1
 */
class ModTagsselectedHelper
{
	public static function getContentList($params){
		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php'); //load fields helper
		JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');

		$db         	= JFactory::getDbo();
		$app        	= JFactory::getApplication();
		$user       	= JFactory::getUser();
		$groups     	= implode(',', $user->getAuthorisedViewLevels());

		$maximum    	= $params->get('maximum', 5);
		$tagsHelper 	= new JHelperTags;
		$option     	= $app->input->get('option');
		$view       	= $app->input->get('view');
		$prefix     	= $option . '.' . $view;
		$id         	= (array) $app->input->getObject('id');
		$selectedTags 	= $params->get('tags');
		$nxdebug 		= $params->get('nxdebug');

		// Custom Fields
		$customFieldsSetup = $params->get('customfields');

		// Custom Field Values


		// Strip off any slug data.
		foreach ($id as $id)
		{
			if (substr_count($id, ':') > 0)
			{
				$idexplode = explode(':', $id);
				$id        = $idexplode[0];
			}
		};

		$tagsToMatch = $selectedTags;
		if (!$tagsToMatch || is_null($tagsToMatch))
		{
			return $results = false;
		}


		$anyOrAll = boolval(intval($params->get('tag_matches', 0)));

			
		$query=$tagsHelper->getTagItemsQuery($tagsToMatch, $typesr = null, $includeChildren = false, $orderByOption = 'c.core_title', $orderDir = 'ASC',$anyOrAll, $languageFilter = 'all', $stateFilter = '1');
		$db->setQuery($query, 0, 0); // was $maximum


		$articles_by_tags = $db->loadObjectList();		// Contains all matched Articles sorted by the selcted tags

		// Define Model for CustomFields
		$cfmodel =JModelLegacy::getInstance('Article', 'ContentModel', array('ignore_request'=>true));
		$appParams = JFactory::getApplication()->getParams();
		$cfmodel->setState('params', $appParams);

		

		foreach ($articles_by_tags as &$result)
		{
			// we take the id of the actual elemet
			$id = $result->content_item_id;
			$item = $cfmodel->getItem($id);

			// we get URL for this element
			$url =  JRoute::_(ContentHelperRoute::getArticleRoute($id, $item->catid, $item->language));
			
			$sql = "SELECT urls FROM #__content WHERE id = ".intval($id);
			$db->setQuery($sql);
			$articleUrls = $db->loadResult();
					  
			$urls = json_decode($articleUrls);
			// we get the CustomFields for this element
			$jcFields = FieldsHelper::getFields('com_content.article',  $item, True);

			// we create an empty array whicht holds the customfields
			$fieldsarray = [];

			foreach($jcFields as $jcField)
			{
				// First we check if we have already this CF in our array ( this happens if a field is connected to multiple categories )
				if (isset($fieldsarray[$jcField->name])) {
					continue;
				} else {
					// if its not already exists --> Push it into the array 
					$fieldsarray[$jcField->name] = $jcField;
				}
			}
			// we give back our fieldsarray to the item
			$result->url = $url;
			$result->urls = $urls;
			$result->fields = $fieldsarray;
		}

		// Sort Array
		// Sort the multidimensional array if a field is setted up
		$orderByField = $params->get('order_by_customfield');
		usort($articles_by_tags, function($a, $b) use ($orderByField, $params){
			if(empty($orderByField)){
				switch($params->get('order_direction','asc')){
					case 'desc':
						return $a->content_item_id > $b->content_item_id;
					break;
					case 'asc':
					default:
						return $a->content_item_id < $b->content_item_id;
				};
				
			}else{
				if (array_key_exists($orderByField, $a->fields)) {
					switch($params->get('order_direction','asc')){
						case 'desc':
							return $a->fields[$orderByField]->rawvalue < $b->fields[$orderByField]->rawvalue;
						break;
						case 'asc':
						default:
							return $a->fields[$orderByField]->rawvalue > $b->fields[$orderByField]->rawvalue;
					}
				}else
				{
					echo '<script type="text/javascript">console.log("Field '.$orderByField.' not found");</script>';
					switch($params->get('order_direction','asc')){
						case 'desc':
							return $a->content_item_id > $b->content_item_id;
						break;
						case 'asc':
						default:
							return $a->content_item_id < $b->content_item_id;
					};
				}
			}
		});
		if(intval($maximum) > 0){
			$articles_by_tags_sliced = array_slice($articles_by_tags, 0, $maximum);
			return $articles_by_tags_sliced;
		}else{
			return $articles_by_tags;
		};
	}

	public static function firstWord($string){
		$arr = explode(' ',trim($string));
    	return $arr[0];
	}

	public static function secondWord($string){
		$arr = explode(' ',trim($string));
    	return $arr[1];
	}

	public static function displaywordn($string, $n){
		$arr = explode(' ',trim($string));
		if(count($arr) > 1){
			$new_string = '<span class="uk-hidden@m">'.$arr[$n].'</span>';
			$new_string .= '<span class="uk-visible@m">'.$string.'</span>';
		}else{
			$new_string = '<span class="">'.$string.'</span>';
		}
		
		return $new_string;
	}

	public static function multiexplode ($delimiters,$string) {
		//  php at metehanarslan dot com ¶
		//  http://php.net/manual/de/function.explode.php
		$ready = str_replace($delimiters, $delimiters[0], $string);
		$launch = explode($delimiters[0], $ready);
		return  $launch;
	}

	public static function customRules($input, $rules){
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

				$setup = (object) ['mode' => $rule->rule_type, 'value' => $rule->rule_string_to_add, 'replacemode' => $rule->rule_string_replace_with, 'find' => $rule->rule_string_to_find, 'replace' => $rule->rule_string_to_replace ];
				// As function because we could make it bigger if necessary
				$html = self::applyBasicRule($input, $setup);
			
				break;
			};
		};
		
		if(empty($html)) $html = trim($input->fieldvalue);
	
		return $html;
	
	}

	public static function applyBasicRule($input, $setup){
		switch($setup->mode){
			case 'before':
				$string = $setup->value . trim($input->fieldvalue);
				break;
			case 'after':
				$string = trim($input->fieldvalue) . $setup->value;
				break;
			case 'replace':
			if($setup->replacemode === 'string'){
				$string = str_replace($setup->find , $setup->replace, trim($input->fieldvalue));
			}else{
				// Linebreak is only other option atm
				$string = str_replace($setup->find , '<br />', trim($input->fieldvalue));
			}
				
				break;
			default:
				$string = trim($input->fieldvalue);
		};
		return $string;
	}

	public static function getImageUrl($item, $src_setup, $fieldname = null){
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
	}

	public static function imagebysetup($item, $context, $params, &$errors){
		// returns the correct image URL & Error based on article / customfield & backend setup
		// get debug state
		$nxdebug = $params->get('nxdebug', 0);
		// Create the img object
		$img = new stdClass();
	
	
		//First we check if there is an image field given if not we check for a value inside Fallback Image then we take our fallback image person.png
		if( null !== $params->get('fallback_image')){
			$backup_img = $params->get('fallback_image');
		}else{
			$backup_img = 'modules/mod_tags_selected_advanced/tmpl/assets/img/person.png';
		};
		
		$img_err = '';
	
		// Call the getURL Function based on context (for card or modal)
		switch ($context){
			case 'modal':
				$src = $params->get('image_in_modal');
				$image_in_modal = ($params->get('image_in_modal') == 'same') ? $params->get('image_source') : $params->get('image_in_modal'); // if its same use image_source as source
				$url = self::getImageUrl($item, $image_in_modal, $params->get('customfield_for_modal_image', null) );
				$img_src_setup = $params->get('customfield_for_modal_image');
				break;
			case 'card':
			default:
				$src = $params->get('image_source');
				$url = self::getImageUrl($item, $params->get('image_source','image_intro'), $params->get('customfield_for_image', null) );
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
	}

	public static function fieldsRender($item, $params, &$errors = ''){
		// Setup
		$html = ''; // Container for Output
		$nxdebug = $params->get('nxdebug', 0);
		
		if(!empty($params->get('fields_to_render_front')))
		{
			$fieldsString = trim($params->get('fields_to_render_front'));
	
			// Grouped Fields
			// Check if there are grouped Fields to render (by comma)
			if(strpos($fieldsString, ',') !== false){
				// create resultgrid
				$result = CardField::buildResultGrid($fieldsString, $item, $params);
				if($result !== false){
					$html = CardField::buildResultGrid($fieldsString, $item, $params);
				}else{
					$html = '';
				};
			}else{
				// No grouped fields just space separated fieldnames
				$array_of_fields = self::multiexplode(array(" ","\r\n"), $fieldsString);
				$html = CardField::buildSimpleTable($item, $array_of_fields, $params);
			};
			
		}else{
			// No fields to render defined - so no rendering happens
		}
	
		return $html;
	}

	public static function getMeta($item, $params){
		$html = '';
		$error = '';
		$rules = $params->get('customfield_rules',[]);
		$meta_trunc = intval($params->get('meta_text_truncate',0));
		switch($params->get('meta_section')){
			case 'customfield':
				if(!empty($params->get('meta_cf_name',''))){
					$fieldnames = explode(" ", $params->get('meta_cf_name',''));
					foreach($fieldnames as $fieldname){
						if(strlen($html) > 1) $html .= ' ';
						if(array_key_exists($fieldname, $item->fields))
						{
							// Object for customfield rules
							$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => $params->get('layoutChoice')]; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
							$html .= self::customRules($cf_object, $rules); 																			//$item->fields[$fieldname]->value;
						}else{	
							$error .= 'Given Customfield ' . $fieldname . ' for Meta not found!';
							if(strlen($html) == 0) {
								$error .= ' - Article Title used instead!';
								//$html .= '';
							};
						};
					};
				};
				break;
			case 'article_title':
				$html = $item->core_title;
				break;
			case 'article_text':
				$html = $item->core_body;
				break;
			case 'author':
				$html = $item->author;
				break;
			case 'created':
				$date = self::firstWord($item->core_created_time);
				$html .= '<span class="uk-text-small">'.date('d. M Y', strtotime($date)).'</span>';
			break;
			default:
				$html .= '<span>something is wrong</span>';
		};

		// Truncate if
		if( in_array($params->get('meta_section'), ['customfield', 'article_title', 'article_text']) && empty($error) && $meta_trunc > 0){
			$html = sentenceTrim($html, $meta_trunc);
		};

		// Additional CSS Classes:
		$html = '<div class="'.$params->get('meta_text_cls','').'">'.$html.'</div>';

		return $html;
	}

	public static function textCardRender($item, $params, &$errors){
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
							$title .= self::customRules($cf_object, $rules); 																			//$item->fields[$fieldname]->value;
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
							$text .= self::customRules($cf_object, $rules);  //$item->fields[$fieldname]->value;
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
	}

	public static function imageCardRender($item, $img, $params, &$errors){
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
				$overlay_classes = $params->get('overlay_classes','');
				$overlay_content_style = $params->get('overlay_content_style','light');
				$overlay_content_position = $params->get('overlay_content_position','center');		// used in Overlay Cover Mode
	
				if($params->get('overlay_transition','none') !== 'none'){
					$overlay_transition = $params->get('overlay_transition');
					$transition_toggle = 'uk-transition-toggle " tabindex="0">';
				}else{
					$overlay_transition = '';
					$transition_toggle = '">';
				};

				$additional_ContClasses = ' ' . $params->get('grid_add_cont_classes');
				
				if($overlay_type !== 'none') $html .= '<div class="uk-inline-clip uk-width-1-1 '.$additional_ContClasses. ' ' .$transition_toggle;
	
				$html .= '<img src="'.$img->url.'" width="100%" alt="">';
				if($overlay_type !== 'none'){
					if($overlay_type == 'cover') {
						$html .= 	'<div class="'.$overlay_transition.' uk-overlay-'.$overlay_style.' uk-padding-small uk-position-cover"></div>';
						$html .= 	'<div class="uk-overlay uk-position-'.$overlay_content_position.' uk-'.$overlay_content_style. ' ' . $overlay_classes . '">';
						$html .= 	self::textCardRender($item, $params, $errors);
						if($params->get('linktype') == 'button'){
							// Renders the Link Button based on setup:
							$html .= self::linkRender($item, $params, null);
						};
						$html .=	'</div>';
					}else{
						$html .= 	'<div class="'.$overlay_transition.' uk-overlay uk-overlay-'.$overlay_style.' uk-'.$overlay_content_style.' uk-padding-small uk-position-'.$overlay_type.'">';
						$html .= 	self::textCardRender($item, $params, $errors);
						if($params->get('linktype') == 'button'){
							// Renders the Link Button based on setup:
							$html .= self::linkRender($item, $params, null);
						};
						$html .=	'</div>';
					};
	
				};
				
				if($params->get('linktype') == 'full'){
					// if the whole card should be clickable
					$html .= self::linkRender($item, $params, null);
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
	}

	public static function linkRender($item, $params, $urlx){
		$html = '';
		$linktext = '';
		$attr = '';
		$classes = '';
		$target= $params->get('linktarget','_blank');
		$outer_start = '';
		$outer_end = '';


		
		
		// switch between Modes Link to display a modal or link to item 
		if($urlx === null){
			switch($params->get('link_mode')){
				case 'modal':
					$link = '#nx-modal-'.$item->content_item_id;
					$attr = 'uk-toggle';
					break;
				case 'article':
				default:
					$link = JRoute::_(TagsHelperRoute::getItemRoute($item->content_item_id, $item->core_alias, $item->core_catid, $item->core_language, $item->type_alias, $item->router)); 	// Default we use the item link for the article
			};
		}else{
			// Overwrite Link URL if $urlx is not false
			if($params->get('nxdebug',0)) echo '<div style="position:absolute; top:0;left:0;">'. var_dump($urlx) . '</div>';
			if($urlx !== null) {
				$link = $urlx;
				$target = '_blank';
			};
		};
		

		
	
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
	}

	public static function buildModal($item, $params, $errors){
		// First we create an array that holds all fields we want to display in the modal (Backend Setting)
		$nxdebug = $params->get('nxdebug',0);
		// Customfield rules
		$rules = $params->get('customfield_rules',[]);
		$construct = '';
		// get the image url based on setup
		$img = self::imagebysetup($item, 'modal', $params, $errors);
	
		if($img->use){
	
			$img_in_construct = '<div class="uk-grid-collapse uk-child-width-1-1@s uk-child-width-expand@m" uk-grid>'.				
									'<div class="uk-card-media-left uk-cover-container uk-width-'.$params->get('modal_image_container_width','1-2').'@m">'.
										'<img src="'.$img->url.'" alt="Image" uk-cover>'.
										'<canvas width="600" height="400"></canvas>'.
									'</div>';
		}else{
			$img_in_construct = '<div class="nx-no-image-modal">';
		};
	
		// Title in Modal
		if(!empty($params->get('title_to_render_modal', ''))){
			$titlesString = trim($params->get('title_to_render_modal', ''));
			$array_of_titles = self::multiexplode(array(" ","\r\n"), $titlesString);
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

		// Fields in Modal
		if(!empty($params->get('fields_to_render_modal')))
		{
			$fieldsString = trim($params->get('fields_to_render_modal'));
			$array_of_fields = self::multiexplode(array(" ","\r\n"), $fieldsString);
			$construct .= '<div class="'. $params->get('fields_1_classes') .'">';
			$construct .= '<table class="uk-table uk-table-small uk-table-divider"><tbody>';
			foreach($array_of_fields as $fieldname){

				// Check if we have added Special Class with Fieldname
				if(strpos($fieldname, '[') !== false){
					var_dump($fieldname);
					preg_match('/\[(.*?)\]/' , $fieldname, $fieldclass);
					$arr = explode("[", $fieldname, 2);
					$fieldname = $arr[0];
				}else{
					$fieldclass = '';
				};

				if(array_key_exists($fieldname, $item->fields)){
					
					$label = $item->fields[$fieldname]->label;
	
					$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => 'modal']; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
					$value = self::customRules($cf_object, $rules);  //$value = $item->fields[$fieldname]->value;
	
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
			$construct .= '</div>';
		}else{
			$error = '<b>No Customfields configured for display</b> - checkout manual to properly configure the module';
		};

		// Fields 2 in Modal
		if(!empty($params->get('fields_2_to_render_modal'))){
			$fieldsString = trim($params->get('fields_2_to_render_modal'));
			$array_of_fields = self::multiexplode(array(" ","\r\n"), $fieldsString);

			$has_value = false;

			$cf2 = '<div class="uk-width-1-1 '.$params->get('fields_2_modal_outer_container_class').'">';
			$cf2 .= '<div class="uk-card '.$params->get('fields_2_modal_container','').' '.$params->get('fields_2_modal_container_class').'">';
			$cf2 .= '<'.$params->get('fields_2_modal_title_tag', 'h4').' class="'.$params->get('fields_2_modal_title_class', 'h4').'">'.$params->get('fields_2_modal_title').'</'.$params->get('fields_2_modal_title_tag', 'h4').'>';

			foreach($array_of_fields as $fieldname){

				// Check if we have added Special Class with Fieldname
				if(strpos($fieldname, '[') !== false){
					var_dump($fieldname);
					preg_match('/\[(.*?)\]/' , $fieldname, $fieldclass);
					$arr = explode("[", $fieldname, 2);
					$fieldname = $arr[0];
				}else{
					$fieldclass = '';
				};

				if(array_key_exists($fieldname, $item->fields)){
					
					$label = $item->fields[$fieldname]->label;
	
					$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => 'modal']; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
					$value = self::customRules($cf_object, $rules);  //$value = $item->fields[$fieldname]->value;
					if($value){
						
						$has_value = true;

						$cf2 .= '<div class="uk-child-width-auto uk-margin-remove uk-grid-small" uk-grid>';

							if(intval($params->get('fields_2_modal_display_label',0))){
								$cf2 .= '<div class="uk-width-1-3"><div>'.$label.'</div></div>';
							};
			
							$cf2 .= '<div class=""><div>'.$value.'</div></div>';
							if($nxdebug) $cf2.= '<div><div class=" uk-alert uk-alert-warning">'.$fieldname.'</div></div>';

						$cf2 .= '</div>';
					};
					
				}else{
					if($nxdebug) $cf2.= '<div class=" uk-alert uk-alert-warning">'.$fieldname.' not exists</div>';
				};
			};

			$cf2 .= '</div>';
			$cf2 .= '</div>';

			if($has_value) $construct .= $cf2;
		};
	
		// Classes for Modal Container
		$modal_cls = 'uk-flex-top ';
	
		if($params->get('modal_container_type', 'container') === 'container'){
			$modal_cls .= 'uk-modal-container ';
		};
	
		$modal_cls .= $params->get('modal_container_cls');
	
		//if(strlen($error)>1) $construct .= $error;
		
		// Build the Modal Container
		$html = 	'<div id="nx-modal-'.$item->content_item_id.'" class="'.$modal_cls.'" uk-modal>';
		$html .= 		'<div class="uk-modal-dialog uk-margin-auto-vertical">';
		$html .= 			'<button class="uk-modal-close-default" type="button" uk-close></button>';
		$html .= 				$img_in_construct;
		$html .= 				'<div class="'.$params->get('fields_container_classes').' ">';
		$html .= 					$construct;
		$html .= 				'</div>';
		$html .= 			'</div>';
		$html .= 		'</div>';
		$html .= 	'</div>';
	
		return $html;
	}

	public static function buildAlternativeModal($item, $params, $errors){
		// First we create an array that holds all fields we want to display in the modal (Backend Setting)
		$nxdebug = $params->get('nxdebug',0);

		// Customfield rules
		$rules = $params->get('customfield_rules',[]);

		// Create empty Construct VAR
		$construct = '';

		// Classes for Modal Container
		$modal_cls = 'uk-flex-top ';
		if($params->get('modal_container_type', 'container') === 'container'){
			$modal_cls .= 'uk-modal-container ';
		};
		$modal_cls .= $params->get('modal_container_cls');

		// get the image url based on setup
		$img = self::imagebysetup($item, 'modal', $params, $errors);

		if($img->use){
			if($params->get('modal_grid_mode','default') === 'default'){
				$img_in_construct = '<div class="uk-grid-collapse uk-child-width-1-1@s uk-child-width-expand@m" uk-grid>'.			
										'<div class="uk-card-media-left uk-cover-container uk-width-'.$params->get('modal_image_container_width','1-2').'@m">'.
											'<img src="'.$img->url.'" alt="Image" uk-cover>'.
											'<canvas width="600" height="400"></canvas>'.
										'</div>';
			}else{
				$img_in_construct = '<div class="uk-child-width-1-1">'.			
										'<div class="uk-cover-container" uk-height-viewport="offset-top: true; offset-bottom: true">'.
											'<img src="'.$img->url.'" alt="Image" uk-cover>'.
											'<canvas width="600" height="400"></canvas>'.
										'</div>';
			};
			
		}else{
			$img_in_construct = '<div class="nx-no-image-modal">';
		};

		// Title in Modal
		if(!empty($params->get('title_to_render_modal', ''))){
			$titlesString = trim($params->get('title_to_render_modal', ''));
			$array_of_titles = self::multiexplode(array('\ (?![^[]*])',"\r\n"), $titlesString);
			$construct .= '<div class="uk-padding-small"><'.$params->get('modal_title_tag','h3').' class="'.$params->get('modal_title_cls','').'">';
			$i = 0;
			foreach($array_of_titles as $fieldname){
				if(strpos($fieldname, '[') !== false){
					
					preg_match('/\[(.*?)\]/' , $fieldname, $fieldclass);
					$arr = explode("[", $fieldname, 2);
					$fieldname = $arr[0];

					var_dump($fieldclass);
				}else{
					$fieldclass = false;
				};

				if(array_key_exists($fieldname, $item->fields)){
					$value = $item->fields[$fieldname]->value;
					if($i>0) $construct .= ' ';

					if($fieldclass){
						$construct .= '<span class="' .$fieldclass[1].'">';
						$construct .= $value;
						$construct .= '</span>';

					}else{
						$construct .= $value;
					};
					$i++;
				}else{
					if($nxdebug) $construct.= '<span class="uk-alert uk-alert-warning">'.$fieldname.' not exists</span>';
				};
			};
			$construct .= '</'.$params->get('modal_title_tag','h3').'></div>';
		};

		// Fields in Modal
		if(!empty($params->get('fields_to_render_modal')))
		{
			$fieldsString = trim($params->get('fields_to_render_modal'));
			$array_of_fields = self::multiexplode(array('\ (?![^[]*])',"\r\n"), $fieldsString);
			$construct .= '<div class="'. $params->get('fields_1_classes') .'">';
			$construct .= '<div><div class="uk-child-width-1-3 uk-grid-collapse uk-flex uk-flex-middle" uk-grid>';
			foreach($array_of_fields as $fieldname){

				// Check if we have added Special Class with Fieldname
				if(strpos($fieldname, '[') !== false){
					
					preg_match('/\[(.*?)\]/' , $fieldname, $fieldclass);
					$arr = explode("[", $fieldname, 2);
					$fieldname = $arr[0];

					var_dump($fieldclass);
				}else{
					$fieldclass = false;
				};

				if(array_key_exists($fieldname, $item->fields)){
					
					$label = $item->fields[$fieldname]->label;
	
					$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => 'modal']; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
					$value = self::customRules($cf_object, $rules);  //$value = $item->fields[$fieldname]->value;
					
					if($fieldclass){
						$construct .= '<div class="' .$fieldclass[1].'">';
					}else{
						$construct .= '<div>';
					};
					
					if(intval($params->get('fields_modal_display_label',1))){
						$construct .= '<'.$params->get('modal_label_tag','h3').' class="'.$params->get('modal_label_tag_class','').'">'.$label.'</'.$params->get('modal_label_tag','h3').'>';
					};
	
					$construct .= '<div class="'.$params->get('modal_value_class','').'">'.$value.'</div>';
					if($nxdebug) $construct.= '<div class=" uk-alert uk-alert-warning">'.$fieldname.'</div>';
					$construct .= '</div>';
				}else{
					if($nxdebug) $construct.= '<div class=" uk-alert uk-alert-warning">'.$fieldname.' not exists</div>';
				};
			};
			$construct .= '</div></div>';
			$construct .= '</div>';
		}else{
			$error = '<b>No Customfields configured for display</b> - checkout manual to properly configure the module';
		};

		// Build the Modal Container
		$html = 	'<div id="nx-modal-'.$item->content_item_id.'" class="'.$modal_cls.'" uk-modal>';
		$html .= 		'<div class="uk-modal-dialog uk-margin-auto-vertical">';
		$html .= 			'<button class="uk-modal-close-default" type="button" uk-close></button>';
		$html .= 				$img_in_construct;
		$html .= 				'<div class="'.$params->get('fields_container_classes').'">';
		$html .= 					$construct;
		$html .= 				'</div>';
		$html .= 			'</div>';
		$html .= 		'</div>';
		$html .= 	'</div>';

		return $html;
	}

	public static function buildDeckModal($item, $params, $errors){
		// First we create an array that holds all fields we want to display in the modal (Backend Setting)
		$nxdebug = $params->get('nxdebug',0);

		// Customfield rules
		$rules = $params->get('customfield_rules',[]);

		// Create empty Construct VAR
		$construct = '';

		// Classes for Modal Container
		$modal_cls = 'uk-flex-top ';
		if($params->get('modal_container_type', 'container') === 'container'){
			//$modal_cls .= 'uk-modal-container ';
		};

		$modal_cls .= $params->get('modal_container_cls');

		// get the image url based on setup
		$img = self::imagebysetup($item, 'modal', $params, $errors);

		if($img->use){
			$img_in_construct = '<div class="">'.
									'<div class="uk-cover-container" uk-height-viewport="offset-top: true; offset-bottom:true">'.			
										'<img src="'.$img->url.'" alt="Image" style="width:100%; margin-bottom:150px;" >'.
									'</div>';
			
		}else{
			$img_in_construct = '<div class="nx-no-image-modal">';
		};

		// Title in Modal
		if(!empty($params->get('title_to_render_modal', ''))){
			$titlesString = trim($params->get('title_to_render_modal', ''));
			$array_of_titles = self::multiexplode(array('\ (?![^[]*])',"\r\n"), $titlesString);
			$title .= '<div class="uk-padding-small"><'.$params->get('modal_title_tag','h3').' class="'.$params->get('modal_title_cls','').'">';
			$i = 0;
			foreach($array_of_titles as $fieldname){
				var_dump($fieldname);
				if(strpos($fieldname, '[') !== false){
					preg_match('/\[(.*?)\]/' , $fieldname, $fieldclass);
					$arr = explode("[", $fieldname, 2);
					$fieldname = $arr[0];
				}else{
					$fieldclass = false;
				};

				if(array_key_exists($fieldname, $item->fields)){
					$value = $item->fields[$fieldname]->value;

					if($i>0) $title .= ' ';

					if($fieldclass){
						$title .= '<div class="' .$fieldclass[1].'">';
						$title .= $value;
						$title .= '</div>';

					}else{
						$title .= $value;
					};
					$i++;
				}else{
					if($nxdebug) $title.= '<span class="uk-alert uk-alert-warning">'.$fieldname.' not exists</span>';
				};
			};
			$title .= '</'.$params->get('modal_title_tag','h3').'></div>';
		};

		// Fields in Modal
		if(!empty($params->get('fields_to_render_modal')))
		{
			$fieldsString = trim($params->get('fields_to_render_modal'));
			$array_of_fields = self::multiexplode(array('\ (?![^[]*])',"\r\n"), $fieldsString);
			$construct .= '<div class="'. $params->get('fields_1_classes') .'">';
			$construct .= '<div><div class="uk-child-width-1-3 uk-grid-collapse uk-flex uk-flex-middle" uk-grid>';
			foreach($array_of_fields as $fieldname){

				// Check if we have added Special Class with Fieldname
				if(strpos($fieldname, '[') !== false){
					
					preg_match('/\[(.*?)\]/' , $fieldname, $fieldclass);
					$arr = explode("[", $fieldname, 2);
					$fieldname = $arr[0];

				}else{
					$fieldclass = false;
				};

				if(array_key_exists($fieldname, $item->fields)){
					
					$label = $item->fields[$fieldname]->label;
	
					$cf_object = (object) ['fieldname' => $fieldname, 'fieldvalue' => $item->fields[$fieldname]->value, 'context' => 'modal']; 		//{'fieldname': $fieldname, 'fieldvalue': $item->fields[$fieldname]->value};
					$value = self::customRules($cf_object, $rules);  //$value = $item->fields[$fieldname]->value;
					
					if($fieldclass){
						$construct .= '<div class="' .$fieldclass[1].'">';
					}else{
						$construct .= '<div>';
					};
					
					if(intval($params->get('fields_modal_display_label',1))){
						$construct .= '<'.$params->get('modal_label_tag','h3').' class="'.$params->get('modal_label_tag_class','').'">'.$label.'</'.$params->get('modal_label_tag','h3').'>';
					};
	
					$construct .= '<div class="'.$params->get('modal_value_class','').'">'.$value.'</div>';
					if($nxdebug) $construct.= '<div class=" uk-alert uk-alert-warning">'.$fieldname.'</div>';
					$construct .= '</div>';
				}else{
					if($nxdebug) $construct.= '<div class=" uk-alert uk-alert-warning">'.$fieldname.' not exists</div>';
				};
			};
			$construct .= '</div></div>';
			$construct .= '</div>';
		}else{
			$error = '<b>No Customfields configured for display</b> - checkout manual to properly configure the module';
		};

		// Build the Modal Container
		$html = 	'<div id="nx-modal-'.$item->content_item_id.'" class="'.$modal_cls.'" uk-modal>';
		$html .= 		'<div class="uk-modal-dialog uk-margin-auto-vertical">';
		$html .= 			'<button class="uk-modal-close-default" type="button" uk-close></button>';
		$html .= 				$img_in_construct;
		$html .= 				'<div class="uk-overlay uk-position-bottom uk-padding-remove '.$params->get('fields_container_classes').'">';
		$html .= 					'<div class="deck_info_top '.$params->get('deck_top_classes').'">';
		$html .= 						$title;
		$html .= 					'</div>';
		$html .= 						$construct;
		$html .= 				'</div>';
		$html .= 			'</div>';
		$html .= 		'</div>';
		$html .= 	'</div>';

		return $html;
	}

	public static function urlxsetup($item, $params){
		// URL A / B / C specific cases for badge
		$badge='';
		$urlids = ['urla','urlb','urlc'];
		$urls = new StdClass();
		$specUrl = null; 											// Last Special URL (if url A & URL B are setted up as external link urlb will be used!)

		foreach($urlids as $id){
			$object = new stdClass();
			$object->name = $id;									// urlx
			$textx = $id.'text';
			$object->text = $item->urls->$textx;					// urlxtext
			$object->link = $item->urls->$id;						// http:// OR false
			$targetx = 'target'.substr($id, -1);
			$object->target = $item->urls->$targetx; 				// targetx

			$object->config = $params->get($id.'_config');
			$badgetext = $id.'_badge_txt';
			$object->txt_src = $params->get($id.'_text_src');
			$object->badge_txt = $params->get($id.'_badge_txt');
			$object->display = $params->get($id.'_external_dp');

			$urls -> $id = $object;

			// Special URL Setup:
			if($params->get($id.'_config') === 'external' && $object->link !== false){
				$specUrl = $object->link;
			};
		};
		$badges = '';
		foreach($urls as $url){
			if($url->link === false || $url->config === '0' || $url->display === '0'){
				continue;
			}else{
				switch($url->txt_src){
					case 'article':
						$badge_txt = $url->text;
						break;
					case 'module':
					default:
						$badge_txt = $url->badge_txt;
				};
				$badges .= '<div class="uk-text-right">'.$badge_txt.'</div>';
			};
		};
		if(!empty($badges)){
			$badge .= '<div class="uk-card-badge uk-label">'.$badges.'</div>';
		};
		//echo '<pre>' . var_export($item->urls, true) . '</pre>';
		$urlx = new stdClass();
		$urlx->url = $specUrl;
		$urlx->badge = $badge;
		$urlx->badge_inner = $badges;

		return $urlx;
	}

	public static function buildGridElement($item, $params, &$errors){
		// Builds the HTML Element for display
		// get the image url based on setup
		$img = self::imagebysetup($item, 'card', $params, $errors);

		$urlxsetup = self::urlxsetup($item, $params);
		$badge = $urlxsetup->badge;
		$specUrl = $urlxsetup->url;
		
		//if($params->get('nxdebug',0)){ echo '<pre>' . var_export($urls, true) . '</pre>';};

		// Hover Box Shadow
		if($params->get('card_hover_box_shadow') !== 'none'){
			$hover_box_shadow = $params->get('card_hover_box_shadow');
		}else{
			$hover_box_shadow = '';
		};
	
		$ukgrid = '';
		if($params->get('element_layout','default') === 'default_card'){
			$containerClasses = 'uk-card uk-card-' . $params->get('card_style','default') . ' uk-card-' . $params->get('card_size','small') . ' ' . $hover_box_shadow;
		}else{
			$containerClasses = 'uk-card ' . $hover_box_shadow;
		};

		
		
		if(intval($params->get('card_hover'))){
			$containerClasses .= ' uk-card-hover';
		};
	
		if(in_array($img->pos, array('left','right')) && $params->get('element_layout') !== 'image_card'){
			$containerClasses .= ' uk-grid-collapse uk-child-width-1-2@s uk-margin';
			$ukgrid = 'uk-grid';
		}
		
	
		// Build the html container 
		if(intval($params->get('simpleMobile',0))){
			$cls_large = ' uk-visible@m';
		}else{
			$cls_large = ' ';
		}
		

		$html = 	'<div class="uk-position-relative">';
		
		// for mobile if simpleMobile
		if($params->get('simpleMobile',0)){
			$html .= 		'<div class="item '.$containerClasses.' uk-card-body uk-margin-top-small uk-hidden@m">';
			if(strlen($urlxsetup->badge_inner) > 3 ) $html .= 			'<div class="uk-card-badge uk-label nx-card-badge-mobile">'.$urlxsetup->badge_inner.'</div>';
			$html .= 			'<h3 class="uk-h5 uk-margin-remove">'.$item->core_title.'</h3>';
			$html .=			self::linkRender($item, $params, $specUrl);
			$html .= 		'</div>';
		};

		// for Desktop
		$html.= 		'<div class="item '.$containerClasses . $cls_large.' " '.$ukgrid.'>';
		
	
		// Append Image in position top to container if not disabled in backend
		if($img->src !== 'none' && in_array($img->pos, array('top','left','right'))){
			$html .= self::imageCardRender($item, $img, $params, $errors);
		};
	
		// Content if mode is set to default card
		$html .= '<div class="uk-position-relative uk-width-expand">';
			$html .= self::fieldsRender($item, $params, $errors);
			if($params->get('meta_section') !== 'none'){
				$html .= self::getMeta($item, $params);
			};
	
			// Content if mode is set to default card
			if($params->get('element_layout') === 'default_card') $html .= self::textCardRender($item, $params, $errors);
	
			if($params->get('linktype') == 'button' && $params->get('element_layout') !== 'image_card') {
				if(intval($params->get('button_always_bottom'))){
					$html .= '<div class="uk-padding"></div>'; // Padding for Absolute Positioned Button
					$html .= '<div class="uk-position-bottom">' . self::linkRender($item, $params, $specUrl) . '</div>';
				}else{
					$html .= self::linkRender($item, $params, $specUrl);
				};
			};
		$html .= '</div>';
	
		// End of content
	
		// Append Image in position bottom to container if not disabled in backend
		if($img->src !== 'none' && $img->pos == 'bottom'){
			$html .= self::imageCardRender($item, $img, $params, $errors);
		};
	
		
		// Full Card Link if Card is not image card and img position is top or bottom
		if($params->get('linktype') == 'full' && $params->get('element_layout') !== 'image_card'){
			// if the whole card should be clickable
			if(in_array($img->pos, array('top','bottom')))
			{
				$html .= self::linkRender($item, $params, $specUrl);
			}
		};

		// Add Badge
		if(!empty($badge)) $html .= $badge;

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
				$html .= self::linkRender($item, $params, $specUrl);
			};
		};

		$html.= 	'</div>';
		return $html;
	}

}
class CardField{

	public static function buildSimpleTable($item, $array_of_fields, $params){
		$nxdebug = $params->get('nxdebug', 0);
		$display_label = intval($params->get('fields_front_display_label',0));
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
		$outer = '<div class="uk-child-width-1-2 uk-grid-collapse uk-text-small" uk-grid>';
		//echo 'fieldsString hat groups';
		$array_of_groups = $array_of_groups = explode(',',$fieldsString);
		//print_r($array_of_groups);
		foreach($array_of_groups as $group){
			$array_of_fields = ModTagsselectedHelper::multiexplode(array(" ","\r\n"), trim($group));
			$g_container = '<div class="nx-group"> <div class="uk-child-width-auto uk-grid-collapse uk-flex uk-flex-center" uk-grid>';
	
			foreach($array_of_fields as $fieldname){
				if(array_key_exists($fieldname, $item->fields)){
					$value = $item->fields[$fieldname]->value;
					if($value === ''){
						return false;
					}
					if(intval($params->get('fields_front_shortener', 0))){
						$value = ModTagsselectedHelper::displaywordn($value, intval($params->get('fields_front_shortener',0)));
					}
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

}

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