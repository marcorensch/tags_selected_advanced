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

// Stylesheet for responsive margin & padding
$document = JFactory::getDocument();
$document->addStyleSheet('modules/mod_tags_selected_advanced/tmpl/assets/css/responsive-margin-padding.css');
$document->addStyleSheet('modules/mod_tags_selected_advanced/tmpl/assets/css/slideshow.css');

$err = 0;

?>

<div class="nx-tagsselectedadvanced nx-tags-slideshow uk-position-relative">
	<div uk-slideshow="
				animation: <?php echo $slideshow_animation; ?>; 
				autoplay: <?php echo $slideshow_autoplay; ?>;
				<?php if(!$slideshow_viewportheight) echo 'max-height:'.$slideshowmaxheight; ?>; 
				autoplay-interval: <?php echo $slideshow_interval; ?>; 
				pause-on-hover:  <?php echo $slideshow_pause_on_hover; ?>" 
				class="uk-position-relative uk-visible-toggle <?php echo $moduleclass_sfx; ?> "
				tabindex="-1">
		<ul class="uk-slideshow-items" <?php echo $viewportsetup; ?>>
		<?php
			foreach($items as $item){
				$img = ModTagsselectedHelper::getImageUrl($item, $params->get('image_source','none'), $params->get('customfield_for_modal_image', null) );

				echo '<li>';
						if($params->get('image_source','none') !== 'none') echo '<img title="'.$item->core_title.'" alt="'.$item->core_title.'" src="'.$img[0].'" uk-cover>';

						echo '<div class="uk-width-1-1@s uk-width-'.$slideshow_overlay_width.'@m uk-overlay uk-overlay-'.$slideshow_overlay_style.' uk-position-'.$slideshow_overlay_position.$overlay_margin.' uk-margin-remove@s ">';
							echo '<div class="'.$slideshow_overlay_transition.'">';
								echo '<div class="uk-visible@m">';
									if($params->get('meta_section') !== 'none'){
										echo ModTagsselectedHelper::getMeta($item, $params);
									};
									if(!empty($params->get('fields_to_render_front',''))){
										echo ModTagsselectedHelper::fieldsRender($item, $params);
									};
									
								echo '</div>';

								echo ModTagsselectedHelper::textCardRender($item, $params, $err);
							echo '</div>';
						echo '</div>';

					if($linktype == 'full'){
						if(!empty(ModTagsselectedHelper::urlxsetup($item, $params)->url)){
							echo '<a target="_blank" class="uk-position-cover" href="'.ModTagsselectedHelper::urlxsetup($item, $params)->url.'"></a>';
						}else{
							echo '<a target="'.$linktarget.'" class="uk-position-cover" href="'.JRoute::_(TagsHelperRoute::getItemRoute($item->content_item_id, $item->core_alias, $item->core_catid, $item->core_language, $item->type_alias, $item->router)).'"></a>';
						};
						
					};
					if(!empty(ModTagsselectedHelper::urlxsetup($item, $params)->badge_inner)){
						echo '<div class="uk-card-badge uk-label uk-position-top-right nx-slideshow-badge">'.ModTagsselectedHelper::urlxsetup($item, $params)->badge_inner. '</div>';
					};
					if($nxdebug) echo '<div class="uk-position-absolute uk-position-z-index">' . highlight_string("<?php\n\$data =\n" . var_export($item, true) . ";\n?>") . '</div>';
				echo '</li>';
			}
		?>
		</ul>
		<a class="uk-position-center-left uk-position-small uk-hidden-hover" href="#" uk-slidenav-previous uk-slideshow-item="previous"></a>
		<a class="uk-position-center-right uk-position-small uk-hidden-hover" href="#" uk-slidenav-next uk-slideshow-item="next"></a>
	</div>
</div>


<?php
if($nxdebug){echo "<h2>nx-debug</h2><hr><h4>Parameters</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($params, true) . ";\n?>");};
if($nxdebug){echo "<hr><h4>Article Setup</h4>\n <pre>" . var_export($list, true) . "</pre>";};
?>
