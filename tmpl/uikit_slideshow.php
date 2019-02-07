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

?>

<div class="nx-tagsselectedadvanced nx-tags-slideshow uk-position-relative">
	<div uk-slideshow="
				animation: <?php echo $slideshow_animation; ?>; 
				autoplay: <?php echo $slideshow_autoplay; ?>; 
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

						echo '<div class="uk-width-'.$slideshow_overlay_width.' '.$slideshow_overlay_margin.' uk-overlay uk-overlay-primary uk-position-'.$slideshow_overlay_position.' '.$slideshow_c_alignement.' '.$slideshow_overlay_transition.'">';
						echo 	'<'.$header_tag.' class="uk-margin-remove">'.$item->core_title.'</'.$header_tag.'>';
								if($params->get('slideshow_meta_section') !== 'none'){
									echo  ModTagsselectedHelper::getMeta($item, $params);
								};
								/*
								if($item->fieldData['team_a_points'] || $item->fieldData['team_b_points']){
									echo 		'<div class="uk-width-1-1 uk-visible@l">'
													.'<table class="uk-table uk-table-middle uk-table-justify"><tbody><tr><td class="uk-text-left uk-text-meta">'.$item->fieldData['team_a_name'].'</td><td class="uk-text-center uk-text-meta">'.$item->fieldData['team_a_points'].':'.$item->fieldData['team_b_points'].'</td><td class=" uk-text-right uk-text-meta">'.$item->fieldData['team_b_name'].'</td></tr></tbody></table>'
												.'</div>';
									echo '<div class="uk-flex uk-flex-center uk-flex-wrap uk-hidden@l">';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.ModTagsselectedHelper::secondWord($item->fieldData['team_a_name']).'</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.$item->fieldData['team_a_points'].'</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">:</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.$item->fieldData['team_b_points'].'</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.ModTagsselectedHelper::secondWord($item->fieldData['team_b_name']).'</div>';
										
									echo '</div>';


								};

								if($item->fieldData['shorttext_html']){
									echo '<div class="uk-visible@m">';
									if($content_text_truncate > 1){
										echo substr_sentence($item->fieldData['shorttext_html'], 0, $content_sentence_truncate, $content_text_truncate);
									}else{
										echo $item->fieldData['shorttext_html'];
									};
									echo '</div>';
								};
							*/	
						echo '</div>';

					if($linktype == 'full'){
						echo '<a target="'.$linktarget.'" class="uk-position-cover" href="'.JRoute::_(TagsHelperRoute::getItemRoute($item->content_item_id, $item->core_alias, $item->core_catid, $item->core_language, $item->type_alias, $item->router)).'"></a>';
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
if($nxdebug){echo "<hr><h4>Article Setup</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($list, true) . ";\n?>");};
?>
