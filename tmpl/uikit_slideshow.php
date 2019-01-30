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
?>

<div class="nx-tagsselectedadvanced nx-tags-slideshow uk-position-relative">
	<div uk-slideshow="
				animation: <?php echo $slideshow_animation; ?>; 
				autoplay: <?php echo $slideshow_autoplay; ?>; 
				autoplay-interval: <?php echo $slideshow_interval; ?>; 
				pause-on-hover:  <?php echo $slideshow_pause_on_hover; ?>" 
				class=" <?php echo $moduleclass_sfx; ?>">
		<ul class="uk-slideshow-items" <?php echo $viewportsetup; ?>>
		<?php
			foreach($list as $element){
				echo '<li>';
					/*highlight_string("<?php\n\$data =\n" . var_export($element, true) . ";\n?>");*/
					echo '<img title="'.$element->core_title.'" alt="'.$element->core_title.'" src="'.$element->imageUrl.'" uk-cover>';

						echo '<div class="uk-overlay uk-overlay-'.$overlay_style.' uk-position-absolute uk-position-bottom-right uk-text-'.$alignement.' '.$overlay_transition.' uk-width-'.$overlay_width.'" style="'.$customcss.'">'
								.'<'.$header_tag.' class="uk-margin-remove">'.$element->core_title.'</'.$header_tag.'>';
								if($element->fieldData['date']){
									$date = firstWord($element->fieldData['date']);
									echo '<span class="uk-text-small">'.date('d. M Y', strtotime($date)).'</span>';
								};

								if($element->fieldData['team_a_points'] || $element->fieldData['team_b_points']){
									echo 		'<div class="uk-width-1-1 uk-visible@l">'
													.'<table class="uk-table uk-table-middle uk-table-justify"><tbody><tr><td class="uk-text-left uk-text-meta">'.$element->fieldData['team_a_name'].'</td><td class="uk-text-center uk-text-meta">'.$element->fieldData['team_a_points'].':'.$element->fieldData['team_b_points'].'</td><td class=" uk-text-right uk-text-meta">'.$element->fieldData['team_b_name'].'</td></tr></tbody></table>'
												.'</div>';
									echo '<div class="uk-flex uk-flex-center uk-flex-wrap uk-hidden@l">';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.secondWord($element->fieldData['team_a_name']).'</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.$element->fieldData['team_a_points'].'</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">:</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.$element->fieldData['team_b_points'].'</div>';
										echo '<div class="uk-text-uppercase uk-text-small uk-text-bold" style="margin:2px;">'.secondWord($element->fieldData['team_b_name']).'</div>';
										
									echo '</div>';


								};

								if($element->fieldData['shorttext_html']){
									echo '<div class="uk-visible@m">';
									if($content_text_truncate > 1){
										echo substr_sentence($element->fieldData['shorttext_html'], 0, $content_sentence_truncate, $content_text_truncate);
									}else{
										echo $element->fieldData['shorttext_html'];
									};
									echo '</div>';
								};
								
						echo '</div>';
					if($linktype == 'full'){
						echo '<a target="'.$linktarget.'" class="uk-position-cover" href="'.JRoute::_(TagsHelperRoute::getItemRoute($element->content_item_id, $element->core_alias, $element->core_catid, $element->core_language, $element->type_alias, $element->router)).'"></a>';
					};

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
