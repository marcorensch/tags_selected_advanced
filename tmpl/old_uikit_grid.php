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





<div class="nx-tagsselectedadvanced nx-tags-grid uk-position-relative">
	<div class="uk-child-width-1-1@s uk-child-width-1-<?php echo $grid_columns . '@m ' . $grid_cutter . $grid_divider . $grid_match; ?>" uk-grid>
	<?php foreach($list as $element){
		$itemlink = JRoute::_(TagsHelperRoute::getItemRoute($element->content_item_id, $element->core_alias, $element->core_catid, $element->core_language, $element->type_alias, $element->router));

		echo '<div class="uk-position-relative uk-transition-toggle" tabindex="0">';
			switch($element_layout){
				case 'image_card':
					echo '<img src="'.$element->imageUrl.'">';
					echo '<div class="'.$overlay_transition.' uk-position-cover uk-overlay uk-overlay-'.$overlay_style.' uk-flex uk-flex-center uk-flex-middle">';
						echo '<div><span class="uk-'.$header_tag.'">'.$element->core_title.'</span></div>';
					echo '</div>';
				break;
				case 'default_card':
					echo '<div class="uk-position-relative uk-card uk-card-'.$card_style.' uk-text-'.$alignement;
						if($displayImg){

							if($mediapos == 'left' || $mediapos == 'right'){
								echo ' uk-grid-collapse uk-child-width-1-2@s uk-margin" uk-grid>';
							}else{
								echo '">';
							};

							if($mediapos == 'left'){echo '<div class="uk-card-media-'.$mediapos.' uk-cover-container"> <img title="'.$element->core_title.'" alt="'.$element->core_title.'" src="'.$element->imageUrl.'" uk-cover ><canvas width="600" height="400"></canvas></div>';};
							if($mediapos == 'right'){echo '<div class="uk-flex-last@s uk-card-media-'.$mediapos.' uk-cover-container"> <img title="'.$element->core_title.'" alt="'.$element->core_title.'" src="'.$element->imageUrl.'" uk-cover ><canvas width="600" height="400"></canvas></div>';};

						}else{
							echo '">';
						};
						
						
						if($displayImg && $mediapos == 'top'){echo '<div class="uk-card-media-'.$mediapos.'"> <img title="'.$element->core_title.'" alt="'.$element->core_title.'" src="'.$element->imageUrl.'" ></div>';};
						echo '<div class="uk-card-body uk-position-relative">';
							echo '<'.$header_tag.' class="uk-card-title">'.$element->core_title.'</'.$header_tag.'>';
							if($element->fieldData['date']){
								$date = firstWord($element->fieldData['date']);
								echo '<span class="uk-text-small">'.date('d. M Y', strtotime($date)).'</span>';

								
							};
							if($mediapos == 'top' || $mediapos == 'bottom'){
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
							}else{
								if($element->fieldData['team_a_points'] || $element->fieldData['team_b_points']){
									echo '<div class="uk-flex uk-flex-center">';
										echo '<div class="uk-text-uppercase uk-text-bold" style="margin:4px;">'.secondWord($element->fieldData['team_a_name']).'</div>';
										echo '<div class="uk-text-uppercase uk-text-bold" style="margin:4px;">'.$element->fieldData['team_a_points'].'</div>';
										echo '<div class="uk-text-uppercase uk-text-bold" style="margin:4px;">:</div>';
										echo '<div class="uk-text-uppercase uk-text-bold" style="margin:4px;">'.$element->fieldData['team_b_points'].'</div>';
										echo '<div class="uk-text-uppercase uk-text-bold" style="margin:4px;">'.secondWord($element->fieldData['team_b_name']).'</div>';
										
									echo '</div>';
								};
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

							if(($linktype == 'button' && $mediapos == 'left') || ($linktype == 'button' && $mediapos == 'right')){
								echo '<div class="uk-padding-small"></div><div class="uk-position-bottom"><div class="'.$buttonmargin.'"><a title="'.$element->core_title.'" target="'.$linktarget.'" class="uk-button uk-button-'.$buttonstyle.' uk-width-1-1" href="'.$itemlink.'">'.$buttontext.'</a></div></div>';
							};
						echo '</div>';
						if(($linktype == 'button' && $mediapos == 'top') || ($linktype == 'button' && $mediapos == 'bottom')){
							echo '<div class="uk-padding-small"></div><div class="uk-position-bottom"><div class="'.$buttonmargin.'"><a title="'.$element->core_title.'" target="'.$linktarget.'" class="uk-button uk-button-'.$buttonstyle.' uk-width-1-1" href="'.$itemlink.'">'.$buttontext.'</a></div></div>';
						};
						if($displayImg && $mediapos == 'bottom'){echo '<div class="uk-card-media-bottom"> <img src="'.$element->imageUrl.'" title="'.$element->core_title.'" alt="'.$element->core_title.'"></div>';};
					echo '</div>';
				break;
			};
		if($linktype == 'full'){
			echo '<a title="'.$element->core_title.'" target="'.$linktarget.'" class="uk-position-cover" href="'.$itemlink.'"></a>';
		};
		echo '</div>';
	}; ?>
	</div>
</div>




<?php
if($nxdebug){echo "<h2>nx-debug</h2><hr><h4>Parameters</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($params, true) . ";\n?>");};
if($nxdebug){echo "<hr><h4>Article Setup</h4>\n"; highlight_string("<?php\n\$data =\n" . var_export($list, true) . ";\n?>");};
?>
