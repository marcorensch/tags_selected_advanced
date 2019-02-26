<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_tags_popular
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
<?php JLoader::register('TagsHelperRoute', JPATH_BASE . '/components/com_tags/helpers/route.php'); ?>
<?php include_once('helpers/substring_sentence.php'); ?>
<?php 
	function firstWord($string){
		$arr = explode(' ',trim($string));
    	return $arr[0];
	}; 
	function secondWord($string){
		$arr = explode(' ',trim($string));
    	return $arr[1];
	};

	function buildElement($item, $params){
		// Builds the HTML Element for display

		//First we check if there is an image field given if not we take our fallback image person.png
		$img_url = '/modules/mod_tags_selected_advanced/tmpl/assets/img/person.png';
		$img_err = '';

		if(!empty($params->get('customfield_for_image'))){
			$image_field_name = $params->get('customfield_for_image');
			if (array_key_exists($image_field_name, $item->fields)) {
				$img_url = $item->fields[$image_field_name]->rawvalue;
			}else{
				$img_err .= '<div class="uk-alert uk-alert-error"><strong>Warning</strong> Defined Image Field (<i>'.$image_field_name.'</i>) not found</div>';
			}
		}else{
			$img_err .= '<div class="uk-alert uk-alert-warning"><strong>Warning</strong> Define Image Field for elements in Module Backend</div>';
		}

		// Build now the html container
		$html = 	'<div>';
		$html.= 		'<div class="uk-card uk-card-default">';

		if(!empty($img_err)){
			$html .=		'<div class="uk-position-top">'.$img_err.'</div>';
		};

		// Append Image to container
		$html .=			'<img src="'.$img_url.'" width="100%">';

		// Append Modal Link
		$html .= '<a class="uk-position-cover" href="#'.$item->core_content_item_id.'" uk-toggle></a>';


		// Debug Section inside container:
		if(intval($params->get('nxdebug', 0))){
			$html .=		'<div class="nx-debug uk-margin">';
			$html .=			'<pre>' . var_export($item, true) . '</pre>';
			$html .= 		'</div>';
		};
		// End of Debug Section

		$html.= 		'</div>';
		$html.= 	'</div>';
		return $html;
	};

	function buildModal($item, $params){
		$html = 	'<div id="'.$item->core_content_item_id.'" class="uk-flex-top" uk-modal>';
		$html .= 		'<div class="uk-modal-dialog uk-margin-auto-vertical">Wir haben hier ein PopUp mit dem Alias: '.$item->core_content_item_id.'</div>';
		$html .= 	'</div>';

		return $html;
	};
?>

<div class="nx-tagsselectedadvanced nx-tags-grid-member uk-position-relative">
	<div class="uk-child-width-1-1@s uk-child-width-1-<?php echo $grid_columns . '@m ' . $grid_cutter . $grid_divider . $grid_match; ?>" uk-grid>
	<?php 
		foreach($items as $element){
			// Elements' Link
			$itemlink = JRoute::_(TagsHelperRoute::getItemRoute($element->content_item_id, $element->core_alias, $element->core_catid, $element->core_language, $element->type_alias, $element->router));
			
			// render the Element
			echo buildElement($element, $params);
			echo buildModal($element, $params);

		};
	?>

	</div>
</div>


<?php
if($nxdebug){echo "<h2>nx-debug</h2><hr><h4>Parameters</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($params, true) . ";\n?>");};
if($nxdebug){echo "<hr><h4>Article Setup</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($items, true) . ";\n?>");};
?>
