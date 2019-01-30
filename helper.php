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
abstract class ModTagsselectedHelper
{
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

		// Custom Fields
		$customFieldsSetup = $params->get('customfields');

		// Custom Field Values

		function getFieldValues($array, $v){
			foreach($array as $struct) {
				if ($v == $struct->name) {
					$item = $struct;
					return $item->value;
					break;
				}
			}
		}

		
		

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
		// Sort the multidimensional array
		$orderByField = $params->get('order_by_customfield', 0);

		
		usort($articles_by_tags, function($a, $b) use ($orderByField){
			if(empty($orderByField)){
				return $a->content_item_id > $b->content_item_id;
			}else{
				return $a->fields[$orderByField]->rawvalue > $b->fields[$orderByField]->rawvalue;
			}
		});

		return $articles_by_tags;
		
	}
}