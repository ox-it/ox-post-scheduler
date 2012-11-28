<?php
/* Todo:
  Make sure that the expiry date is greater than the enable date.
  Fork the plugin: write new header, cite original authors.
*/

/*
Plugin Name: Oxford post scheduler
Plugin URI: http://www.wordpress.org
Description: Add expire date for posts.
Author: Guido Klingbeil, Marko Jung
License: GPL v3 or higher
Version: 0.1
Author URI: http://www.it.ox.ac.uk
*/

/* This is a streamlined fork of the Simple expires plugin bei Andrea Bersi */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/*
This is the copyright notice of the forked simple expiry plugin:
Copyright (c) 2010 Andrea Bersi.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/


define('SIMPLE_EXPIRES_PLUGIN_URL', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
define('SIMPLE_EXPIRES_DOMAIN', 'simple-expires');

register_deactivation_hook( __FILE__, 'ox_expiry_deactivation');
function ox_expiry_deactivation() {
  //  remove rows from wp_postmeta tables
  global $wpdb;

  $query_ret = $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key='ox-has-enable-date' OR meta_key='ox-has-disable-date' OR meta_key='ox-enable-date' OR meta_key='ox-disable-date' " ));

  if($query_ret === FALSE) {
    error_log("ox_expiry_deactivation: error removing meta data from posts in database.");
  } else {
    error_log("ox_expiry_deactivation: plugin deactivated.");
  }
}


add_action('admin_menu', 'ox_expiry_load_admin');
function ox_expiry_load_admin() {
  load_plugin_textdomain(
    SIMPLE_EXPIRES_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/lang', dirname(plugin_basename(__FILE__)).'/lang'
    );

    wp_enqueue_script('my_validate', SIMPLE_EXPIRES_PLUGIN_URL.'/js/jquery.validate.pack.js', array('jquery'));
}


add_action("admin_head","ox_expiry_validate_data");
function ox_expiry_validate_data(){
		?>
			<script type="text/javascript">
			jQuery.extend(jQuery.validator.messages, {
			       required: "<?php _e('Field required', SIMPLE_EXPIRES_DOMAIN)?>",number: "<?php _e('Invalid number', SIMPLE_EXPIRES_DOMAIN)?>",min: jQuery.validator.format("<?php _e('Please enter a value greater than or equal to {0}', SIMPLE_EXPIRES_DOMAIN)?>")
			});
			jQuery().ready(function() {
			    jQuery("#post").validate( {
					rules:{year:{number:true,min:2011},hour:{number:true,max:24},min:{number:true,max:60}}
				}
				);
			});
			</script>
		<?php
	}


add_action("admin_head","ox_expiry_validate_css");
function ox_expiry_validate_css(){
  ?>
  <style>
  .error{color:red;font-weight:bold;}
  </style>
  <?php
}


// auxiliary function wrapping the database query
// parameter: $kind either ox-has-disable-date or ox-has-enable-date
function ox_expiry_get_posts($kind) {
    global $wpdb;

    // check input
    if( (strcmp("ox-has-enable-date", $kind) !=0 & strcmp("ox-has-disable-date", $kind) !=0)) {
        error_log("ox_expiry_get_posts: unexpected input string: ".$kind."\n");
        return;
    }

    $sub_string = ((strcmp("ox-has-enable-date", $kind)) ? "ox-disable-date" : "ox-enable-date");
    $sub_string2 = ((strcmp("ox-has-enable-date", $kind)) ? "publish" : "draft");

    $querystring = 'SELECT postmetadate.post_id 
        FROM 
        ' .$wpdb->postmeta. ' AS postmetadate, 
        ' .$wpdb->postmeta. ' AS postmetadoit, 
        ' .$wpdb->posts. ' AS posts 
        WHERE postmetadoit.meta_key = "'.$kind.'" 
        AND postmetadoit.meta_value = 1 
        AND postmetadate.meta_key = "'.$sub_string.'" 
        AND postmetadate.meta_value <= "' . current_time("mysql") . '" 
        AND postmetadate.post_id = postmetadoit.post_id 
        AND postmetadate.post_id = posts.ID 
        AND posts.post_status = "'.$sub_string2.' "';

    $query_results = $wpdb->get_results($querystring);
    wp_reset_postdata();
    return($query_results);
}


function ox_expires_update($array_of_posts, $string_post_status) {
    if ( ! empty( $array_of_posts ) ) {
        foreach ( $array_of_posts as $cur_post ) {
            //print("update post ".$cur_post->post_id." status: ".get_post_status($cur_post->post_id).", new status: ".$string_post_status.".\n");
            $update_post = array('ID' => $cur_post->post_id);
            // Get the Post's ID into the update array
            $update_post['post_status'] = $string_post_status;
            wp_update_post( $update_post ) ;
        }
    }
}


// the main function of the plugin
function simple_expires() {
    // enable posts
    $result = ox_expiry_get_posts("ox-has-enable-date");
    //error_log("posts to enable: ".$result."\n");
    //error_log(print_r($result));
    ox_expires_update($result, 'publish');

    // disable posts
    $result = ox_expiry_get_posts("ox-has-disable-date");
    //error_log("posts to disable: ".$result."\n");
    //error_log(print_r($result));
    ox_expires_update($result, 'draft');
}


add_action( 'init', 'simple_expires' );

/* Define the custom box */
add_action('add_meta_boxes', 'ox_expiry_add_config_box');

/* Do something with the data entered */
add_action('save_post', 'ox_expiry_save_postdata');


/* Adds a box to the main column on the Post and Page edit screens */
// in our context, a page can not expire
function ox_expiry_add_config_box() {
    add_meta_box( 'ox_expiry_plugin', __('Expire', SIMPLE_EXPIRES_DOMAIN), 'ox_expiry_', 'post','side' ,'high');
}


/* Display the config box */
function ox_expiry_($post) {
    global $wp_locale;

    // Use nonce for verification
    wp_nonce_field( 'my-id', 'simple-expires-nonce' );

    $has_enable = get_post_meta($post->ID, 'ox-has-enable-date');
    $has_disable = get_post_meta($post->ID, 'ox-has-disable-date');

    //print("has_enable: ".(empty($has_enable) ? " empty ": " notempty ").", has_disable: ".(empty($has_disable) ? " empty ":" not empty ")."\n");

    // retrieve the expiry date from the post meta data
    $time_enable = get_post_meta($post->ID,'ox-enable-date',true);
    $time_disable = get_post_meta($post->ID,'ox-disable-date',true);

    //print("metabox time_enable: ".(empty($time_enable)?" empty ":" not empty ").$time_enable.", time_disable: ".(empty($time_disable)?" empty ":" not empty ").$time_disable."\n");
    // set the default expiry date to one year from now on
    $time_adj = time() + (365 * 24 * 60 * 60);

    // parse the expiry date into easy to read variables
    $disable_day =   (! empty($time_disable)) ? mysql2date( 'd', $time_disable, false ) : gmdate( 'd', $time_adj );
    $disable_month = (! empty($time_disable)) ? mysql2date( 'm', $time_disable, false ) : gmdate( 'm', $time_adj );
    $disable_year =  (! empty($time_disable)) ? mysql2date( 'Y', $time_disable, false ) : gmdate( 'Y', $time_adj );
    $disable_hour =  (! empty($time_disable)) ? mysql2date( 'H', $time_disable, false ) : gmdate( 'H', $time_adj );
    $disable_min =   (! empty($time_disable)) ? mysql2date( 'i', $time_disable, false ) : gmdate( 'i', $time_adj );
  
    // parse the enable date into easy to read variables
    $enable_day =   (! empty($time_enable)) ? mysql2date( 'd', $time_enable, false ) : gmdate( 'd', current_time('timestamp') );
    $enable_month = (! empty($time_enable)) ? mysql2date( 'm', $time_enable, false ) : gmdate( 'm', current_time('timestamp') );
    $enable_year =  (! empty($time_enable)) ? mysql2date( 'Y', $time_enable, false ) : gmdate( 'Y', current_time('timestamp') );
    $enable_hour =  (! empty($time_enable)) ? mysql2date( 'H', $time_enable, false ) : gmdate( 'H', current_time('timestamp') );
    $enable_min =   (! empty($time_enable)) ? mysql2date( 'i', $time_enable, false ) : gmdate( 'i', current_time('timestamp') );

    $enable_month_string = "<select  id=\"enable_month\" name=\"enable_month\">\n";
    for ( $i = 1; $i <= 12; $i ++ ) {
      $enable_month_string .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
      if ( $i == $enable_month ) {
        $enable_month_string .= ' selected="selected"';
      }
      $enable_month_string .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
    }
    $enable_month_string .= '</select>';

    $disable_month_string = "<select  id=\"disable_month\" name=\"disable_month\">\n";
    for ( $i = 1; $i <= 12; $i ++ ) {
      $disable_month_string .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
      if ( $i == $disable_month ) {
        $disable_month_string .= ' selected="selected"';
      }
      $disable_month_string .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
    }
    $disable_month_string .= '</select>';

    echo '<div class="">
    <input type="checkbox" name="has_enable" value="has_enable"'.(($has_enable) ? "checked":"").'>Enable at (month, day @ hour : min):<br>'
    .$enable_month_string.
    '<input type="text" class="number" id="day" name="enable_day" value="'.$enable_day.'" size="2" maxlength="2" tabindex="4" autocomplete="off" />,
    <input type="text"  id="year" name="enable_year" value="'.$enable_year.'" size="4" maxlength="4" tabindex="4" autocomplete="off" /> @
    <input type="text"  id="hour" name="enable_hour" value="'.$enable_hour.'" size="2" maxlength="2" tabindex="4" autocomplete="off" /> :
    <input type="text"  id="min" name="enable_min" value="'.$enable_min.'" size="2" maxlength="2" tabindex="4" autocomplete="off" />
    <br>
    <input type="checkbox" name="has_disable" value="has_disable"'.(($has_disable) ? "checked":"").'>Disable at (month, day @ hour : min):<br>
    '.$disable_month_string.'
    <input type="text" class="number" id="day" name="disable_day" value="'.$disable_day.'" size="2" maxlength="2" tabindex="4" autocomplete="off" />,
    <input type="text"  id="year" name="disable_year" value="'.$disable_year.'" size="4" maxlength="4" tabindex="4" autocomplete="off" /> @
    <input type="text"  id="hour" name="disable_hour" value="'.$disable_hour.'" size="2" maxlength="2" tabindex="4" autocomplete="off" /> :
    <input type="text"  id="min" name="disable_min" value="'.$disable_min.'" size="2" maxlength="2" tabindex="4" autocomplete="off" />
    </div>
    </div>';
}


// why is this called so often?
// it should only be called when we save a post

/* When the post is saved, saves our custom data */
function ox_expiry_save_postdata( $post_id ) {
  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if ( !wp_verify_nonce( $_POST['simple-expires-nonce'], 'my-id' )) {
    error_log("ox_expiry_save_postdata: nonce did not verify.\n");
    return $post_id;
  }

  // verify if this is an auto save routine. If it is our form has not been submitted, 
  // so we dont want to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
    return $post_id;

  // Check permissions
  if ( 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $post_id ) )
      return $post_id;
  } else {
    if ( !current_user_can( 'edit_post', $post_id ) )
      return $post_id;
  }

  error_log("ox_expiry_save_postdata: save data.\n");

  // handle enable data
  $mydata = $_POST['enable_year']."-".$_POST['enable_month']."-".zeroise($_POST['enable_day'],2)." ".zeroise($_POST['enable_hour'],2).":".$_POST['enable_min'].":00";
  $mydata = date('Y-m-d H:i:s',strtotime($mydata));

  if(isset($_POST['has_enable'])) {
    update_post_meta($post_id,'ox-enable-date', date($mydata));
    update_post_meta( $post_id, 'ox-has-enable-date', "1" );
  } else {
    delete_post_meta($post_id,'ox-enable-date');
    delete_post_meta( $post_id, 'ox-has-enable-date');
  }
  // if the enable date is in the future, set post status to draft
  if($mydata > current_time("mysql")) {  
    $update_post['post_status'] = 'draft';
    wp_update_post( $update_post );
  }

  // handle disable data
  $mydata = $_POST['disable_year']."-".$_POST['disable_month']."-".zeroise($_POST['disable_day'],2)." ".zeroise($_POST['disable_hour'],2).":".$_POST['disable_min'].":00";
  $mydata = date('Y-m-d H:i:s',strtotime($mydata));

  if(isset($_POST['has_disable'])) {
    update_post_meta($post_id,'ox-disable-date', date($mydata));
    update_post_meta( $post_id, 'ox-has-disable-date', "1" );
  } else {
    delete_post_meta($post_id,'ox-disable-date');
    delete_post_meta( $post_id, 'ox-has-disable-date');
  }
}


