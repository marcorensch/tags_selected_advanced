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


if($nxdebug){/*
?>
	<div class="uk-alert uk-alert-warning uk-width-1-1 uk-padding-small">
		<?php var_dump($errors); foreach($errors as $error) echo $error->msg.'<br/>';?>
	</div>
<?php */
};

if($params->get('card_onload_animation') !== 'none'){
	$scrollspy = 'uk-scrollspy="target: > div > div.item; cls:' . $params->get('card_onload_animation','uk-animation-fade') . '; delay:' . $params->get('card_onload_animation_delay','500') . '"';
}else{
	$scrollspy = '';
};

// Add Special Styling Attr. for Deck
if($params->get('modal_render_mode') === 'deck'){
	$document = JFactory::getDocument();
	$deck = '.deck_info_top {
					height:' . $params->get('deck_top_height','150px') . ';
				}
				@supports(-webkit-clip-path: ' . $params->get('deck_top_clippath','polygon(78% 63%, 100% 78%, 100% 100%, 0 100%, 0 85%)') . ') or (clip-path: ' . $params->get('deck_top_clippath','polygon(78% 63%, 100% 78%, 100% 100%, 0 100%, 0 85%)') . ') {

					.deck_info_top {
						-webkit-clip-path: ' . $params->get('deck_top_clippath','polygon(78% 63%, 100% 78%, 100% 100%, 0 100%, 0 85%)') . ';
						clip-path: ' . $params->get('deck_top_clippath','polygon(78% 63%, 100% 78%, 100% 100%, 0 100%, 0 85%)') . ';
						
					}
					
				  }';
	$document->addStyleDeclaration( $deck );

};

?>
<link href="https://fonts.googleapis.com/css?family=Teko:400,700" rel="stylesheet"> 
<div class="nx-tagsselectedadvanced nx-tags-grid-member uk-position-relative">
	
	<div class="<?php echo $grid_columns . $grid_cutter . $grid_divider . $grid_match; ?>" uk-grid <?= $scrollspy ?>>
	<?php
		foreach($items as $element){
			// Elements' Link
			$itemlink = JRoute::_(TagsHelperRoute::getItemRoute($element->content_item_id, $element->core_alias, $element->core_catid, $element->core_language, $element->type_alias, $element->router));
			
			// render the Element
			echo ModTagsselectedHelper::buildGridElement($element, $params, $errors);
			if($params->get('link_mode') == 'modal' && $params->get('modal_render_mode','default') == 'default') echo ModTagsselectedHelper::buildModal($element, $params, $errors);
			if($params->get('link_mode') == 'modal' && $params->get('modal_render_mode') == 'alternative') echo ModTagsselectedHelper::buildAlternativeModal($element, $params, $errors);
			if($params->get('link_mode') == 'modal' && $params->get('modal_render_mode') == 'deck') echo ModTagsselectedHelper::buildDeckModal($element, $params, $errors);

		};
	?>

	</div>
	
</div>


<?php
if($nxdebug){echo "<h2>nx-debug</h2><hr><h4>Parameters</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($params, true) . ";\n?>");};
if($nxdebug){echo "<hr><h4>Article Setup</h4>\n <pre>" . var_export($items, true) . "</pre>";};
?>
