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

	function multiexplode ($delimiters,$string) {
		//  php at metehanarslan dot com ¶
		//  http://php.net/manual/de/function.explode.php
		$ready = str_replace($delimiters, $delimiters[0], $string);
		$launch = explode($delimiters[0], $ready);
		return  $launch;
	};

	function imagebysetup($item, $params){
		// returns the correct image URL & Error based on article / customfield & backend setup
		// get debug state
		$nxdebug = $params->get('nxdebug', 0);
		// Create the img object
		$img = new stdClass();


		//First we check if there is an image field given if not we take our fallback image person.png
		$backup_img = 'modules/mod_tags_selected_advanced/tmpl/assets/img/person.png';
		$img_err = '';
		$img_src_setup = $params->get('image_source');

		switch($img_src_setup){
			case 'customfield':
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
			break;
			case 'image_intro':
			case 'image_fulltext':
				$core_images = json_decode($item->core_images);
				$img_url = $core_images->$img_src_setup;
			break;
			case 'none':
			default:
		};

		// Last check if image exists, if not we print out an error
		if (!file_exists($img_url) && !empty($img_url)) {
			$img_err .= '<div class="uk-alert uk-alert-danger uk-padding-small">Die Datei "'.$img_url.'" existiert nicht Datei "'.$backup_img.'" wird geladen!</div>';
			$img_url = $backup_img;
		}
		elseif(empty($img_url))
		{
			if($nxdebug)
			{
				$img_err .= '<div class="uk-alert uk-alert-warning uk-padding-small">Keine Bildinformationen hinterlegt im Feld '.$img_src_setup.' Datei "'.$backup_img.'" wird geladen!</div>';
			}
			$img_url = $backup_img;
		};


		// insert the data into the object
		$img->url = $img_url;
		$img->src = $img_src_setup;
		$img->pos = $params->get('image_pos', 'top');
		$img->error = $img_err;

		return $img;
	};

	function imageCardRender($img, $params){
		$html = '';
		// Display message if something isn't right about the image configuration
		if(!empty($img->error)){
			$html .=		'<div class="uk-position-top uk-padding-small">'.$img->error.'</div>';
		};
		$cover = intval($params->get('image_cover',0));
		$height = '';
		if($params->get('image_container_height') !== 'none'){
			$height = 'uk-height-'.$params->get('image_container_height');
		}
	
		if(in_array($img->pos, array('top','bottom')) || $params->get('element_layout') === 'image_card' ){
			
			
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

			$html .= '<div class="'.$classes.'uk-card-media-'.$img->pos.' uk-cover-container">';
				$html .= '<img src="'.$img->url.'" width="100%" alt="" uk-cover>';
				$html .= '<canvas width="600" height="400"></canvas>';
			$html .= '</div>';
		};
		return $html;
	};

	function textCardRender($item, $params){
		// Prepare Content based on setup
		$nxdebug = $params->get('nxdebug', 0);
		$alignement = $params->get('content_alignement', 'left');
		$title_tag = $params->get('title_tag','h3');
		$title_cls= $params->get('title_cls','');
		$html = '';
		$error = '';
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
							$title .= $item->fields[$fieldname]->value;
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

		// build the HTML
		switch($params->get('element_layout')){
			case 'image_card':
				break;
			case 'default_card':
			default:
				$html .=	'<div class="uk-card-body uk-text-'.$alignement.'">';
				if($nxdebug && !empty($error))
				{
					$html .= '<div class="uk-position-top"><div class="uk-alert uk-alert-danger">'.$error.'</div></div>';
				};
				$html .= 	'<'.$title_tag.' class="'.$title_cls.'">'.$title.'</'.$title_tag.'>'.
								'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</p>'.
							'</div>';
		};
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

	function buildElement($item, $params){
		// Builds the HTML Element for display
		
		// get the image url based on setup
		$img = imagebysetup($item, $params);

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
			$html .= imageCardRender($img, $params);
		};

		// Content if mode is set to default card
		$html .= textCardRender($item, $params);
		if($params->get('linktype') == 'button') $html .= linkRender($item, $params);

		// End of Content

		// Append Image in position bottom to container if not disabled in backend
		if($img->src !== 'none' && $img->pos == 'bottom'){
			$html .= imageCardRender($img);
		};

		
		// Link
		if($params->get('linktype') == 'full'){
			// if the whole card should be clickable
			if(in_array($img->pos, array('top','bottom')) || $params->get('element_layout') == 'image_card')
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
		if($params->get('linktype') == 'full'){
			// Append Modal Link if the card has grid layout
			if(in_array($img->pos, array('left','right')) && $params->get('element_layout') !== 'image_card') {
				$html .= linkRender($item, $params);
			};
		};
		$html.= 	'</div>';
		return $html;
	};

	function buildModal($item, $params){
		// First we create an array that holds all fields we want to display in the modal (Backend Setting)
		$nxdebug = $params->get('nxdebug',0);
		$construct = '';
		if(!empty($params->get('fields_to_render_modal')))
		{
			$fieldsString = trim($params->get('fields_to_render_modal'));
			$array_of_fields = multiexplode(array(" ","\r\n"), $fieldsString);
			$construct = '<table class="uk-table uk-table-divider"><tbody>';
			foreach($array_of_fields as $fieldname){
				$label = $item->fields[$fieldname]->label;
				$value = $item->fields[$fieldname]->value;
				$construct .= '<tr><td>'.$label.'</td><td>'.$value.'</td>';
				if($nxdebug) $construct.= '<td class=" uk-table-shrink uk-alert uk-alert-warning">'.$fieldname.'</td>';
				$construct .= '</tr>';
			};
			$construct .= '</tbody></table>';
		}else{
			$error = '<b>No Customfields configured for display</b> - checkout manual to properly configure the module';
		}

		if(strlen($error)>1) $construct .= $error;
		
		$html = 	'<div id="nx-modal-'.$item->content_item_id.'" class="uk-flex-top" uk-modal>';
		$html .= 		'<div class="uk-modal-dialog uk-margin-auto-vertical">';
		$html .= 			$construct;
		$html .= 		'</div>';
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
			if($params->get('link_mode') == 'modal') echo buildModal($element, $params);

		};
	?>

	</div>
</div>


<?php
if($nxdebug){echo "<h2>nx-debug</h2><hr><h4>Parameters</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($params, true) . ";\n?>");};
if($nxdebug){echo "<hr><h4>Article Setup</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($items, true) . ";\n?>");};
?>
