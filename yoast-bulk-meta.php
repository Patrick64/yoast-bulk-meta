<?php
/*
Plugin Name: Yoast bulk meta
Description: 
Author: Patrick Woodcock
Author URI: 
Version: 1.0.2
Requires at least: 5.0
Tested up to: 5.2
Requires PHP: 7.0
*/


function yoast_bulk_meta_register_options_page()
{
	add_options_page('Yoast Bulk Meta', 'Yoast Bulk Meta', 'manage_options', 'yoast_bulk_meta', 'yoast_bulk_meta_options_page');
}
add_action('admin_menu', 'yoast_bulk_meta_register_options_page');

function yoast_bulk_meta_register_settings()
{
	add_option('yoast_bulk_meta_option_name', 'This is my option value.');
	register_setting('yoast_bulk_meta_options_group', 'yoast_bulk_meta_option_name', 'yoast_bulk_meta_callback');
}
add_action('admin_init', 'yoast_bulk_meta_register_settings');

// _yoast_wpseo_title 
// '_yoast_wpseo_metadesc'
function yoast_bulk_meta_update_meta($fieldtitle,$field,$newvalue,$i,$post_id,$doupdate,$url,&$results) {
	$current = get_post_meta($post_id, $field, true);
	if ($current == $newvalue) {
		$results[] = ("<span style='color:blue'>Line " . ($i + 1) . ": post id " . $post_id . " for url " . $url . " " . $fieldtitle . " is the same: " . $newvalue . "</span>");
	} else if ($current) {
		$results[] = ("<span style='color:darkorange'>Line " . ($i + 1) . ": post id " . $post_id . " for url " . $url . " current " . $fieldtitle . ": <del>" . $current . "</del> to be replaced with: " . $newvalue . '</span>');
	} else {
		$results[] = ("<span style='color:green'>Line " . ($i + 1) . ": post id " . $post_id . " for url " . $url . " add " . $fieldtitle . ": " . $newvalue . "</span>");
	}
	if ($doupdate && $current != $newvalue) {
		update_post_meta($post_id, $field, $newvalue);
		$results[] = $fieldtitle . " updated.";
	}
}

function yoast_bulk_meta_options_page()
{
	//content on page goes here
	$doupdate = isset($_POST['doupdate']);
	$results = array();
	if (isset($_POST['csvtext'])) {
		$lines = explode("\n", $_POST['csvtext']);

		foreach ($lines as $i => $line) {

			$csv = str_getcsv($line, "\t");

			if (count($csv) != 3) {
				$results[] = "Line " . ($i + 1) . " error: needs 3 columns exactly!";
			} else {
				$url = $csv[0];
				
				$url = preg_replace('/^https?\:\/\/[^\/]+/', '', $url);
				$title = stripslashes($csv[1]);
				$description = stripslashes($csv[2]);
				$post_id = url_to_postid($url);
				if (!$post_id) {
					$results[] = ("<span style='color:red'>Line " . ($i + 1) . " error: cannot find post for url " . $url . "</span>");
				} else {
					if ($title) {
						yoast_bulk_meta_update_meta('Title','_yoast_wpseo_title',$title,$i,$post_id,$doupdate,$url,$results);
					}
					if ($description) {
						yoast_bulk_meta_update_meta('Description','_yoast_wpseo_metadesc',$description,$i,$post_id,$doupdate,$url,$results);
					}
				}
			}
		}
	}

	?>
	<div>

		<h2>Yoast bulk meta</h2>

		<form method="post" action="">
			<?php settings_fields('yoast_bulk_meta_options_group'); ?>
			<h3>Meta CSV</h3>
			<p>Format: URL [TAB] Title [TAB] Description</p>
			<textarea style="width:100%; height:500px" name="csvtext"><?= isset($_POST['csvtext']) ? stripslashes($_POST['csvtext']) : ""  ?></textarea>
			<input type="submit" name="view_only" id="submit" class="button button-primary" value="Check">
			<ul>
				<?php foreach ($results as $result) : ?>
					<li><?= $result ?></li>
				<?php endforeach; ?>

			</ul>

			<?php if ($results) : ?>
				<br><br><input type="submit" name="doupdate" id="submit" class="button button-danger" value="Write new meta descriptions">
			<?php endif; ?>
		</form>
	</div>
<?php
}
