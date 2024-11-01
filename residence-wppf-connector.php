<?php
/*
Plugin Name: WP Property Feed to Residence Theme Connector
 Plugin URI: http://www.wppropertyfeed.co.uk/
 Description: This plugin will take your property feed using WP Property Feed Plugin and map the feed to the WP Residence theme properties.
 Version: 1.30
 Author: Ultimateweb Ltd
 Author URI: http://www.ultimateweb.co.uk
 Text Domain: wp-property-feed-to-residence-theme-connector
 License: GPL2
*/

/*  Copyright 2018  Ian Scotland, Ultimateweb Ltd  (email : info@ultimateweb.co.uk)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

error_reporting(E_ALL);
ini_set('display_errors', '1');
 */

defined('ABSPATH') or die("No script kiddies please!");
$wppf_reside_version = '1.30';

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
register_activation_hook( __FILE__, 'wppf_reside_install');
register_deactivation_hook( __FILE__, 'wppf_reside_uninstall' );

add_action('init', 'wppf_reside_init',1);
add_action('admin_init', 'wppf_reside_admin_init');
add_action('wppf_after_admin_init', 'wppf_reside_admin_init');
add_action('wppf_settings_tabs','wppf_reside_tab');
add_action('wppf_settings_sections','wppf_reside_section');
add_action('admin_notices', 'wppf_reside_notice_residence');
add_action('wppf_after_settings_updated', 'wppf_reside_settings_updated', 101, 1);
add_action('wppf_reside_incremental','wppf_reside_populate');
add_action('wppf_register_scripts', 'wppf_reside_register_scripts');

function wppf_reside_install() {
    wp_clear_scheduled_hook('wppf_reside_incremental');
    wp_schedule_event(time() + 60, 'quarterhourly', 'wppf_reside_incremental');
}

function wppf_reside_uninstall() {
    delete_option("wppf_theme");
}

function wppf_reside_init() {
    //are the theme and plugin active
    if (get_template() == 'wpresidence' && is_plugin_active('wp-property-feed/wppf_properties.php')) {
        add_action('admin_menu', 'wppf_reside_admin_menu');
        add_filter('wppf_filter_slug', function($title) { return "wppf-property";});
        update_option("wppf_theme", "residence");
        if (!wp_get_schedule('wppf_reside_incremental')) wp_schedule_event(time() + 60, 'quarterhourly', 'wppf_reside_incremental');  //re-instate the wp residende schedule in case it drops away!
    } else {
        delete_option("wppf_theme");
    }
    wp_enqueue_style('wppf_properties_resi', plugins_url('wppf_resi.css',__FILE__));

    //Legacy way
    $mcf[] = array("property available","Available From","date","1");
    $mcf[] = array("property furnished","Furnished","short text","2");
    $mcf[] = array("property let_type","Let Type","short text","3");
    $mcf[] = array("property deposit","Deposit","short text","4");
    $mcf[] = array("advertised","Advertised","short text","5");
    $mcf[] = array("council tax band","Council Tax Band","short text","6");
    update_option("wp_estate_custom_fields",$mcf);

    $admin = get_option("wpresidence_admin");
    $web_status = array("For Sale","Under Offer","Sold","SSTC","For Sale By Auction","Reserved","New Instruction","Just on the Market","Price Reduction","Keen to Sell",
                            "No Chain","Vendor will pay stamp duty","Offers in the region of","Guide Price","To Let","Let","Let Agreed", "To Let");
    update_option("wp_estate_status_list", implode(",\r\n",$web_status));

    $fields = (isset($admin["wpestate_custom_fields_list"])) ? $admin["wpestate_custom_fields_list"] : array();
    $fields = $admin["wpestate_custom_fields_list"];
    $fields = wppf_reside_custom_field($fields, "property available","Available From","date","1","");
    $fields = wppf_reside_custom_field($fields, "property furnished","Furnished","short text","2","");
    $fields = wppf_reside_custom_field($fields, "property let_type","Let Type","short text","3","");
    $fields = wppf_reside_custom_field($fields, "property deposit","Deposit","short text","4","");
    $fields = wppf_reside_custom_field($fields, "advertised","Advertised","dropdown","5","Available,SSTC/Let Agreed,Sold/Let");
    $fields = wppf_reside_custom_field($fields, "council tax band","Council Tax Band","short text","6","");
    $admin["wpestate_custom_fields_list"] = $fields;
    $admin["wp_estate_status_list"] = implode(",\r\n",$web_status);
    $admin["wp_estate_show_no_features"] = "no";
    update_option("wpresidence_admin", $admin);
}

function wppf_reside_custom_field($current, $name, $label, $type, $order, $dropdown=null) {
    $current = (empty($current)) ? array() : $current;
    if (isset($current['add_field_name'])) {
        $idx = array_search($name,$current['add_field_name']);      
        if ($idx) {
            $current['add_field_name'][$idx] = $name;
            $current['add_field_label'][$idx] = $label;
            $current['add_field_order'][$idx] = $order;
            $current['add_field_type'][$idx] = $type;
            $current['add_dropdown_order'][$idx] = $dropdown;
        } else {
            $current['add_field_name'][] = $name;
            $current['add_field_label'][] = $label;
            $current['add_field_order'][] = $order;
            $current['add_field_type'][] = $type;
            $current['add_dropdown_order'][] = $dropdown;
        }
    }
    return $current;
}

function wppf_reside_notice_residence() {
    if (get_template() != 'wpresidence' || !is_plugin_active('wp-property-feed/wppf_properties.php')) {
        echo "<div class='notice notice-error'><p><img align='left' src='".plugins_url('wppf.png', __FILE__)."' alt='WP Property Feed' style='margin: -10px 10px -1px -10px' /> <strong>WPPF WP Residence</strong><br>The WP Residence WPPF Property Theme plugin is currently inactive as it requires both the WP Residence theme to be the current theme and also for the WP Property Feed plugin to be active.</p><div style='clear:both'></div></div>";
    }
}

function wppf_reside_register_scripts() {
    wp_dequeue_script('google_maps');
}

function wppf_reside_admin_menu() {
    //check for theme and plugin and show notice it they do not exist and are not active
    if (get_template() == 'wpresidence' && is_plugin_active('wp-property-feed/wppf_properties.php')) remove_menu_page('edit.php?post_type=wppf_property');
}

function wppf_reside_tab() {
    echo "<a href='#residence' class='nav-tab'>WP Residence</a>";
}

function wppf_reside_section() {
    echo "<div class='wppf_tab_section' id='residence' style='display:none'>";
    do_settings_sections('wppf_reside_feed');
    $wppf_reside_propertycount = get_option("wppf_reside_propertycount");
    if (!empty($wppf_reside_propertycount)) {
        echo 'There are currently ' . $wppf_reside_propertycount.' feed properties listed in the theme.<br /><br />';
        echo '<strong>Last 10 Updates:</strong><br /><table border="0">';
        if ($logs = get_option('wppf_reside_log')) {
            foreach($logs as $log) {?>
                <tr>
                    <td><?php echo $log['logdate']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;updated: <?php echo $log['updated']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;deleted: <?php echo $log['deleted']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;total: <?php echo $log['totalproperties']; ?></td>
                </tr>
            <?php
            }
        }
        echo '</table><br /><br />';
    }
    echo '</div>';
}

function wppf_reside_admin_init() {
    add_settings_section('wppf_main', 'Residence', 'wppf_reside_section_text', 'wppf_reside_feed');
    add_settings_field('wppf_reside_filter', 'Properties Filtering', 'wppf_reside_setting_filter', 'wppf_reside_feed', 'wppf_main');
    add_settings_field('wppf_reside_epc_floorplan', 'Show EPCs and Floorplans in gallery', 'wppf_reside_setting_epc_floorplan', 'wppf_reside_feed', 'wppf_main');
    add_settings_field('wppf_reside_refresh', 'Update Residence properties now', 'wppf_reside_setting_refresh', 'wppf_reside_feed', 'wppf_main');
}

function wppf_reside_section_text() {
    echo '<p>Congratulations you and now set up up and the property feed plugin will automatically feed properties into the WP Residence theme every 15 minutes.</p>';
}

function wppf_reside_setting_refresh() {
    echo "<input type='checkbox' name='wppf_options[reside_refresh]' value='1' /> tick to refresh the property data on save (please be patient it can take a while!)";
}

function wppf_reside_setting_epc_floorplan() {
    $options = get_option('wppf_options');
    if (empty($options['epc_floorplan'])) $options['epc_floorplan']='none';
    echo "<select name='wppf_options[epc_floorplan]'><option value='none'>Do not show EPCs and Floorplans in gallery</option>";
    if ($options['epc_floorplan'] == 'epcs')
        echo "<option value='epcs' selected='selected'>Show EPCs in gallery</option>";
    else
        echo "<option value='epcs'>Show EPCs in gallery</option>";
    if ($options['epc_floorplan'] == 'floorplans')
        echo "<option value='floorplans' selected='selected'>Show Floorplans in gallery</option>";
    else
        echo "<option value='floorplans'>Show Floorplans in gallery</option>";
    if ($options['epc_floorplan'] == 'both')
        echo "<option value='both' selected='selected'>Show EPCs and Floorplans in gallery</option>";
    else
        echo "<option value='both'>Show EPCs and Floorplans in gallery</option>";
    echo "</select>";
}

function wppf_reside_setting_filter() {
    $options = get_option('wppf_options');
    if (empty($options['feed_filter'])) $options['feed_filter']='none';
    echo "<select name='wppf_options[feed_filter]'><option value='none'>No filter include all properties</option>";
    if ($options['feed_filter'] == 'unsold')
        echo "<option value='unsold' selected='selected'>Only advertised and SSTC/Let Agreed properties</option>";
    else
        echo "<option value='unsold'>Only advertised and SSTC/Let Agreed properties</option>";
    if ($options['feed_filter'] == 'advertised')
        echo "<option value='adverised' selected='selected'>Only advertised properties (exclude SSTC/Let Agreed/Sold/Let)</option>";
    else
        echo "<option value='advertised'>Only advertised properties (exclude SSTC/Let Agreed/Sold/Let)</option>";
    echo "</select>";
}

function wppf_reside_settings_updated() {
    $options = get_option('wppf_options');
    if (isset($options['reside_refresh'])) {
        wppf_reside_populate();
    }
}

function wppf_reside_purge() {
    $rhposts = get_posts(array('post_type' => 'estate_property', 'numberposts' => -1, 'meta_key' => 'wppf', 'meta_value' => '1'));
    foreach ($rhposts as $post) {
        wppf_purge_post($post->ID);
    }
}
add_action("wppf_purge","wppf_reside_purge");

function wppf_reside_populate() {
    set_time_limit(300);
    ini_set('max_execution_time', 300);
    $options = get_option('wppf_options');

    //fix for older version of the wppf plugin
    $tax_prefix=(floatval(explode(".",get_option('wppf_version'))[1]) > 50) ? "wppf_": "";

    //clear the image log
    update_option("wppf_reside_imagelog", null);

    //mark all properties to delete (we will un-mark any that are in the feed) - only do this if we have successfully connected to the API, hence it is here
    $rhposts = get_posts(array('post_type' => 'estate_property', 'numberposts' => -1, 'meta_key' => 'wppf', 'meta_value' => '1'));
    foreach ($rhposts as $post) {
        update_post_meta($post->ID,'remove',true);
    }

    //Update agents
    $terms = get_terms(array('taxonomy' => $tax_prefix.'branch'));
    foreach ($terms as $branch) {
        $postid = 0;
        $rhposts = get_posts(array('post_type' => 'estate_agent', 'numberposts' => -1, 'meta_key' => 'wppf_slug', 'meta_value' => $branch->slug));
        if (empty($rhposts)) {
            $agent = array('ID' => $postid,
                'post_title' => $branch->name,
                'post_status' => 'publish',
                'post_type' => 'estate_agent');
            $postid = wp_insert_post($agent);
            $phone = get_term_meta($branch->ID, 'phone');
            $email = get_term_meta($branch->ID, 'email');
            add_post_meta($postid,'first_name',$branch->name, true);
            if (!empty($phone)) add_post_meta($postid,'agent_phone',$phone,true);
            if (!empty($email)) add_post_meta($postid,'agent_email',$email,true);
            add_post_meta($postid,'wppf_slug',$branch->slug,true);
        }
    }

    //Main update loop
    $updated = 0;
    $wposts = get_posts(array('post_type' => 'wppf_property', 'numberposts' => -1));
    foreach ($wposts as $property) {
        $process_property = true;
        $web_status = get_post_meta($property->ID,'web_status',true);
        if ($options['feed_filter']=="unsold" && ($web_status=='Let' || $web_status=='Sold')) $process_property = false;
        if ($options['feed_filter']=="advertised" && ($web_status=='Let' || $web_status=='Sold' || $web_status=='Let Agreed' || $web_status=='SSTC')) $process_property = false;
        if ($process_property) {
            $postid = 0; $isNew = false; $changed=true;
            $wppfid = get_post_meta($property->ID,'wppf_id',true);
            $rhposts = get_posts(array('post_type' => 'estate_property', 'numberposts' => -1, 'meta_key' => 'wppf_id', 'meta_value' => $wppfid));
            if (!empty($rhposts)) {
                $postid = $rhposts[0]->ID;
                $plast = get_post_meta($property->ID,'updated',true);  //date the feed property was last updated
                $tlast = get_post_meta($postid,'updated',true); //date the residence property was last updated
                if (!empty($plast) && !empty($tlast)) {
                    if (strtotime($plast) < strtotime($tlast)) {
                        $changed = false;
                        update_post_meta($postid,'remove',false);
                    }
                }
            }
            if ($postid == 0) $isNew = true;

            if ($changed || $isNew) {
                $postcontent = "";
                $paragraphs = wppf_get_paragraphs($property->ID);
                if (!empty($paragraphs)) {
                    foreach($paragraphs as $paragraph) {
                        if ($paragraph['name']!='') $postcontent .= "<h4>".$paragraph['name']."</h4>";
                        $postcontent.="<p>";
                        if ($paragraph['dimensions']!="") $postcontent .= "<em>".$paragraph['dimensions']."</em><br />";
                        $postcontent.=$paragraph['description']."</p>";
                    }
                }
                if (empty($postcontent)) $postcontent = $property->post_content;

                $docs = wppf_get_documents($property->ID);
                if (count($docs)>0){
                    $postcontent .= '<h4>Printable Documents</h4><ul>';
                    foreach ($docs as $doc) {
                        $postcontent .= '<li><a href="'.$doc['url'].'" target="_blank">';
                        $postcontent .= (empty($doc['name'])) ? "Brochure" : $doc['name'];
                        $postcontent .= '</a></li>';
                    }
                    $postcontent .= '</ul>';
                }

                //$status_display = $options['status_display'];
                //if (empty($status_display)) $status_display = "category";
                //$proptitle = $property->post_title;
                //if ($status_display == "category") $proptitle .= " - ".get_post_meta($property->ID,'web_status',true);
                //set up key datafields
                $prop = array('ID' => $postid,
                    'post_title' => $property->post_title,
                    'post_excerpt' => $property->post_excerpt,
                    'post_content' => $postcontent,
                    'post_status' => 'publish',
                    'post_type' => 'estate_property',
                    'post_date' => get_post_meta($property->ID,'uploaded',true),
                    'post_modified' => get_post_meta($property->ID,'updated',true));
                remove_filter('content_save_pre', 'wp_filter_post_kses');
                remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                $postid = wp_insert_post($prop);
                add_filter('content_save_pre', 'wp_filter_post_kses');
                add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                update_post_meta($postid,'wppf','1');
                update_post_meta($postid,'wppf_id',$wppfid);
                update_post_meta($postid,'xml',get_post_meta($property->ID,'xml',true));
                update_post_meta($postid,'agentref',get_post_meta($property->ID,'agentref',true));

                update_post_meta($postid, 'sidebar_agent_option', 'global');
                update_post_meta($postid, 'local_pgpr_slider_type', 'global');
                update_post_meta($postid, 'local_pgpr_content_type', 'global');

                update_post_meta($postid,'updated',get_post_meta($property->ID,'updated',true));
                if (strtotime(get_post_meta($property->ID,'available',true)) > strtotime('1900-01-01')) update_post_meta($postid,'property-date',get_post_meta($property->ID,'available',true));
                update_post_meta($postid,'remove',false);
                if (get_post_meta($property->ID,'price_display',true)=='0' || get_post_meta($property->ID,'price_qualifier',true)=='POA') {
                    update_post_meta($postid,'property_price','');
                } else {
                    update_post_meta($postid,'property_price',get_post_meta($property->ID,'price',true));
                }

                //handle status
                wp_set_object_terms($postid, get_post_meta($property->ID,'web_status',true), "property_status",false);
                update_post_meta($postid,'property_status',get_post_meta($property->ID,'web_status',true));
                switch(get_post_meta($property->ID,'web_status',true)) {
                    case "SSTC":
                    case "Let Agreed":
                        update_post_meta($postid,'advertised','SSTC/Let Agreed');
                        break;
                    case "Sold":
                    case "Let":
                        update_post_meta($postid,'advertised','Sold/Let');
                        break;
                    default:
                        update_post_meta($postid,'advertised','Available');
                        break;
                }

                update_post_meta($postid,'property_label',get_post_meta($property->ID,'price_postfix',true));
                update_post_meta($postid,'property_label_before',get_post_meta($property->ID,'price_qualifier',true));
                $propertyarea_sqft = get_post_meta($property->ID,'propertyarea_sqft',true);
                if (!empty($propertyarea_sqft)) {
                    update_post_meta($postid,'property_size',$propertyarea_sqft);
                }
                update_post_meta($postid,'property_rooms',get_post_meta($property->ID,'receptions',true));
                update_post_meta($postid,'property_bedrooms',get_post_meta($property->ID,'bedrooms',true));
                update_post_meta($postid,'property_bathrooms',get_post_meta($property->ID,'bathrooms',true));
                update_post_meta($postid,'property-garage',get_post_meta($property->ID,'parking',true));

                update_post_meta($postid,'property_address',get_post_meta($property->ID,'address_name',true));
                update_post_meta($postid,'hidden_address', wppf_get_address($property->ID,", "));
                update_post_meta($postid,'property_latitude',get_post_meta($property->ID,'latitude',true));
                update_post_meta($postid,'property_longitude',get_post_meta($property->ID,'longitude',true));
                update_post_meta($postid,'property_country',get_post_meta($property->ID,'address_country',true));
                update_post_meta($postid,'property_zip',get_post_meta($property->ID,'address_postcode',true));
                update_post_meta($postid,'page_custom_zoom','15');
                update_post_meta($postid,'prop_featured',get_post_meta($property->ID,'featured',true));
                $sap = get_post_meta($property->ID,'eer_current',true);
                if (!empty($sap)) {
                    $sapband = "G";
                    if ((int)$sap >= 21) $sapband = "F";
                    if ((int)$sap >= 39) $sapband = "E";
                    if ((int)$sap >= 55) $sapband = "D";
                    if ((int)$sap >= 69) $sapband = "C";
                    if ((int)$sap >= 81) $sapband = "B";
                    if ((int)$sap >= 92) $sapband = "A";
                    update_post_meta($postid,'energy_class',$sapband);
                }
                update_post_meta($postid,'epc_current_rating',get_post_meta($property->ID,'eer_current',true));
                update_post_meta($postid,'epc_potential_rating',get_post_meta($property->ID,'eer_potential',true));               
                update_post_meta($postid,'property-available',get_post_meta($property->ID,'available',true));
                update_post_meta($postid,'property-furnished',get_post_meta($property->ID,'furnished',true));
                update_post_meta($postid,'property-let_type',get_post_meta($property->ID,'let_type',true));
                update_post_meta($postid,'property-deposit',get_post_meta($property->ID,'let_bond',true));
                update_post_meta($postid,'council-tax-band',get_post_meta($property->ID,'counciltaxband',true));

                delete_post_meta($postid,'property_agent'); //clear current agents
                $branches = wp_get_post_terms($property->ID, $tax_prefix."branch");
                foreach ($branches as $branch) {
                    $tbs = get_posts(array('post_type' => 'estate_agent', 'numberposts' => -1, 'meta_key' => 'wppf_slug', 'meta_value' => $branch->slug));
                    foreach($tbs as $tb) {
                        update_post_meta($postid,'property_agent',$tb->ID);
                    }
                }

                //handle bullets
                $admin = get_option("wpresidence_admin");
                $features = explode(",",$admin["wp_estate_feature_list"]);
                $bulletstr = str_replace("</ul>","",str_replace("<ul>","",get_post_meta($property->ID,'bullets',true)));
                $bullets = explode("</li><li>",$bulletstr);

                wp_delete_object_term_relationships($postid, "property_features");
                foreach($bullets as $bullet) {
                    $bullet = str_replace("<li>","",str_replace("</li>","",$bullet));
                    //new in version 3
                    if (!empty($bullet)) wp_set_object_terms($postid, $bullet, "property_features",true);
                    $bullet = trim(preg_replace("/[^A-Za-z0-9 ]/"," ", $bullet));
                    if (!in_array($bullet,$features)) $features[] = $bullet;
                    update_post_meta($postid,strtolower(str_replace(" ","_",$bullet)),"1");
                }
                $admin["wp_estate_feature_list"] = implode(",",$features);
                update_option("wpresidence_admin", $admin);

                delete_post_meta($postid,'image_to_attach'); //clear property images
                $attachments = array();
                $epcs = array();

                $files = get_post_meta($property->ID,'files');  //get all the files from WPPF
                $outfiles = array();
                $floorplantitle = array();
                $floorplanurl = array();
                $floorplanattach = array();
                $floorplanother = array();

                $hasthumb = false;
                if (has_post_thumbnail($property->ID)) {
                    $sourcethumbid = get_post_thumbnail_id($property->ID);
                    if (is_numeric($sourcethumbid)) {
                        set_post_thumbnail($postid, $sourcethumbid);
                        $hasthumb = true;
                    }
                }

                if (isset($files[0])) {
                    //make sure the files are in the correct sort order
                    $ifiles = $files[0];
                    $sfiles = array(); $tfiles = array();
                    foreach ($ifiles as $key => $row)
                    {
                        $tfiles[$key] = (int)$row['filetype'];
                        $sfiles[$key] = (int)$row['sortorder'];
                    }
                    array_multisort($tfiles, SORT_ASC, $sfiles, SORT_ASC, $ifiles);

                    foreach($ifiles as $file) {  //Loop through WPPF image files
                        switch($file['filetype']) {
                            case "0": //picture
                            case "1": //picture map
                            case "9": //EPCs
                                if ($options['epc_floorplan']=="both" || $options['epc_floorplan']=="epcs" || $file['filetype']!="9") {
                                    if (!wppf_reside_check_attachment($file['attachmentid'],$postid,$file['sortorder'])) {
                                        if ($aid = wppf_reside_upload_file($file['url'],$postid,$file['sortorder'])) { //create an attachment and add it to this post
                                            $file['attachmentid'] = $aid;
                                        }
                                    }
                                    $attachments[] = $file['attachmentid'];
                                }
                                if ($file['filetype']=="9") $epcs[] = $file['attachmentid'];
                                if (!$hasthumb && $file['filetype']=="0") {
                                    set_post_thumbnail($postid, $file['attachmentid']);
                                    $hasthumb = true;
                                }
                                break;
                            case "2": //floorplans
                                if ($options['epc_floorplan']=="both" || $options['epc_floorplan']=="floorplan") {
                                    if (!wppf_reside_check_attachment($file['attachmentid'],$postid,$file['sortorder'])) {
                                        if ($aid = wppf_reside_upload_file($file['url'],0,0)) {  // do not attached to post images
                                            $file['attachmentid'] = $aid;
                                        }
                                    }
                                    $attachments[] = $file['attachmentid'];
                                }
                                $floorplantitle[] = $file['name'];
                                $floorplanattach[] = $file['attachmentid'];
                                $floorplanurl[] = $file['url'];
                                $floorplanother[] = "";
                                break;
                            case "11": //Video tour
                                $parts = parse_url($file['url']);
                                if (strpos($parts['host'],"youtube.com")!==false) {
                                    if (isset($parts['query'])) {
                                        foreach(explode("&",$parts['query']) as $pair) {
                                            $kp = explode("=",$pair);
                                            if ($kp[0]='v') {
                                                update_post_meta($postid,'embed_video_type', 'youtube');
                                                update_post_meta($postid,'embed_video_id', $kp[1]);
                                            }
                                        }
                                    }
                                }
                                elseif (strpos($parts['host'],"youtu.be")!==false) {
                                    if (isset($parts['path'])) {
                                        update_post_meta($postid,'embed_video_type', 'youtube');
                                        update_post_meta($postid,'embed_video_id', $parts['path']);
                                    }
                                } else {
                                    update_post_meta($postid,'embed_virtual_tour', '<iframe src="'.$file['url'].'" style="width: 100%;height:60vh;border:none"></iframe>');
                                }
                                break;
                        }
                        $outfiles[] = $file;
                    }
                    //ditch any attachments that are no longer part of this property
                    $cimages = get_attached_media('image',$postid);
                    foreach($cimages as $currentimage) {
                        if (!in_array($currentimage->ID,$attachments)) wp_delete_attachment($currentimage->ID,true);
                    }

                    update_post_meta($postid,'image_to_attach',implode(",",$attachments));
                    update_post_meta($postid,'epc_to_attach',implode(",",$epcs));
                    update_post_meta($property->ID,'files',$outfiles);
                    update_post_meta($postid,'use_floor_plans','0');
                    if (!empty($floorplantitle)) {
                        update_post_meta($postid,'plan_title', $floorplantitle);
                        update_post_meta($postid,'plan_image', $floorplanurl);
                        update_post_meta($postid,'plan_description', $floorplanother);
                        update_post_meta($postid,'plan_image_attach', $floorplanattach);
                        update_post_meta($postid,'plan_size', $floorplanother);
                        update_post_meta($postid,'plan_rooms', $floorplanother);
                        update_post_meta($postid,'plan_bath', $floorplanother);
                        update_post_meta($postid,'plan_price', $floorplanother);
                        update_post_meta($postid,'use_floor_plans','1');
                    }
                }

                //get property sales areas (For Sale, To Let, etc) and add to property status
                wp_delete_object_term_relationships($postid, "property_action_category");
                $areas = wp_get_post_terms($property->ID, $tax_prefix."property_area");
                foreach ($areas as $area) {
                     wp_set_object_terms($postid,$area->name, "property_action_category");
                }

                //get property type (detached, semi, etc) and add to property types
                wp_delete_object_term_relationships($postid, "property_category");
                $ptypes = wp_get_post_terms($property->ID, $tax_prefix."property_type");
                foreach ($ptypes as $ptype) {
                    wp_set_object_terms($postid,$ptype->name,"property_category");
                }

                //get City/locality locations
                wp_delete_object_term_relationships($postid, "property_city");
                wp_set_object_terms($postid,get_post_meta($property->ID,'address_town'),"property_city");
                wp_delete_object_term_relationships($postid, "property_county_state");
                wp_set_object_terms($postid,get_post_meta($property->ID,'address_county'),"property_county_state");
                wp_delete_object_term_relationships($postid, "property_area");
                wp_set_object_terms($postid,get_post_meta($property->ID,'address_locality'),"property_area");

                do_action('wppf_reside_update_property', $postid, $property);
                $updated++;
            }
        }
    }

    //Now remove any properties that were not in the feed
    $deleted = 0;
    $rhposts = get_posts(array('post_type' => 'estate_property', 'numberposts' => -1, 'meta_query' => array('relation' => 'AND', array('key' => 'wppf', 'value' => '1', 'compare' => '='),array('key' => 'remove', 'value' => true, 'compare' => '='))));
    foreach ($rhposts as $post) {
        wppf_purge_post($post->ID);
        $deleted++;
    }


    $property_count = 0;
    //update total count
    $rhposts = get_posts(array('post_type' => 'estate_property', 'numberposts' => -1, 'meta_key'=>'wppf', 'meta_value'=>'1'));
    if (is_array($rhposts)) {
        $property_count = count($rhposts);
        update_option("wppf_reside_propertycount", $property_count);
    }

    //update logs keeping just the last 10
    $date = new DateTime();
    $log = get_option("wppf_reside_log");
    if (is_array($log)) {
        array_unshift($log,array('logdate' => $date->format('d/m/Y H:i:s'),'updated' => $updated, 'deleted' => $deleted, 'totalproperties' => $property_count));
        $log = array_slice($log,0,10);
    } else {
        $log = array(array('logdate' => $date->format('d/m/Y H:i:s'), 'updated' => $updated, 'deleted' => $deleted, 'totalproperties' => $property_count));
    }
    update_option("wppf_reside_log", $log);
}

function wppf_reside_check_attachment($attachment_id, $post_id, $sortorder) {
    if (!empty($attachment_id)) {
        $image = wp_get_attachment_image_src($attachment_id);
        wp_update_post( array('ID' => $attachment_id, 'post_parent' => $post_id, 'menu_order' => $sortorder));
        if ($image) return true;
    }
    return false;
}

function wppf_reside_upload_file($url, $postid, $sortorder) {
    $mimetype = "image/jpeg";
    $parts = pathinfo($url);
    switch ($parts['extension']) {
        case "gif":
            $mimetype = "image/gif";
            break;
        case "png":
            $mimetype = "image/png";
            break;
    }

    $attachment = array(
            'guid'           => $url,
            'post_mime_type' => $mimetype,
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename($url)),
            'post_content'   => 'cdn',
            'post_status'    => 'inherit',
            'post_parent'    => $postid,
            'menu_order'     => $sortorder
        );
    $attach_id = wp_insert_attachment($attachment, basename($url), $postid);
    //fake the attachment data so it will display in the gallery
    $imagemeta = array("aperture" => "0", "credit" => "", "camera" => "", "caption" => "", "created_timestamp" => "0", "copyright" => "", "focal_length" => "0", "iso" => "0", "shutter_speed" => "0", "title" => "", "orientation" => "1", "keywords" => array());
    $meta = array("width" => "300", "height" => "200", "file" => preg_replace( '/\.[^.]+$/', '', basename($url)), "image_meta" => $imagemeta);
    wp_update_attachment_metadata($attach_id, $meta);
    return $attach_id;
}

if (!function_exists("get_image_sizes")) {
    function get_image_sizes() {
	    global $_wp_additional_image_sizes;
	    $sizes = array();
	    foreach ( get_intermediate_image_sizes() as $_size ) {
		    if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
			    $sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			    $sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			    $sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		    } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			    $sizes[ $_size ] = array(
				    'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				    'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				    'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			    );
		    }
	    }
	    return $sizes;
    }
}

if (!function_exists("wppf_image_downsize")) {
    function wppf_image_downsize($downsize, $id, $size) {
        //if this is a cdn image then server that
        $attachment = get_post($id);
        if ($attachment->post_content == 'cdn') {
            $sizes = get_image_sizes();
            if (is_array($size)) $size='thumbnail';
            if (isset($sizes[$size])) {
                return array($attachment->guid, $sizes[$size]['width'], $sizes[$size]['height'], $sizes[$size]['crop']);
            } else {
                return array($attachment->guid, '100', '75', true);
            }
        }
        return false;
    }
    add_filter('image_downsize', 'wppf_image_downsize', 10, 3);
}

if (!function_exists("wppf_attachment_cdn_url")) {
    function wppf_attachment_cdn_url($url, $id) {
        $attachment = get_post($id);
        if ($attachment->post_content == 'cdn') {
		    $url = $attachment->guid;
	    }
	    return $url;
    }
    add_filter('attachment_link', 'wppf_attachment_cdn_url', 10, 2 );
    add_filter('wp_get_attachment_url', 'wppf_attachment_cdn_url', 10, 2);
    add_filter('wp_get_attachment_thumb_url', 'wppf_attachment_cdn_url', 10, 2);
}

function wppf_reside_published($the_date, $overide, $post) {
    if ($post->post_type == 'estate_property') {
        $wppf_slug = get_post_meta($post->ID,'wppf_slug',true);
        if (!empty($wppf_slug)) {
            $updated = get_post_meta($post->ID,'updated',true);
            $the_date = mysql2date(get_option( 'date_format' ), $updated);
        }
    }
    return $the_date;
}
add_filter('get_the_date', 'wppf_reside_published', 10, 3);