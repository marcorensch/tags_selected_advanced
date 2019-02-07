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
	public static function firstWord($string){
		$arr = explode(' ',trim($string));
    	return $arr[0];
	}
	public static function secondWord($string){
		$arr = explode(' ',trim($string));
    	return $arr[1];
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

	public static function getMeta($item, $params){
		$html = '';
		switch($params->get('slideshow_meta_section')){
			case 'customfield':
			break;
			case 'article_title':
			break;
			case 'author':
			break;
			case 'created':
				$date = ModTagsselectedHelper::firstWord($item->core_created_time);
				$html .= '<span class="uk-text-small">'.date('d. M Y', strtotime($date)).'</span>';
			break;
			default:
				$html .= '<span>something is wrong</span>';
		};
		return $html;
	}

	public static function getContentList($params)
	{
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
/*
		private function getFieldValues($array, $v){
			foreach($array as $struct) {
				if ($v == $struct->name) {
					$item = $struct;
					return $item->value;
					break;
				}
			}
		}

		
	*/	

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

			
		$query=$tagsHelper->getTagItemsQuery($tagsToMatch, $typesr = null, $includeChildren = false, $orderByOption = 'c.core_title', $orderDir = 'ASC',$anyOrAll, $languageFilter = 'all', $stateFilter = '0,1');
		$db->setQuery($query, 0, $maximum);


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
		return $articles_by_tags;
	}
}