<?php
/**
 * Plugin Name: Feline COI
 * Description: Private cat pedigrees and pedigree-based inbreeding calculations for cats and proposed matings.
 * Version: 0.1.0
 * Author: Will Radford
 * License: GPL-2.0-or-later
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: feline-coi
 */
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/includes/class-fcoi-engine.php';
require_once __DIR__ . '/includes/documents.php';

function fcoi_table() { global $wpdb; return $wpdb->prefix . 'fcoi_pedigrees'; }
function fcoi_activate() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = fcoi_table(); $collate = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE $table (
        user_id bigint(20) unsigned NOT NULL,
        revision bigint(20) unsigned NOT NULL DEFAULT 0,
        pedigree longtext NOT NULL,
        PRIMARY KEY  (user_id)
    ) $collate;");
}
register_activation_hook(__FILE__, 'fcoi_activate');
register_activation_hook(__FILE__, 'fcoi_docs_activate');
function fcoi_forget_user($user_id) { global $wpdb; $wpdb->delete(fcoi_table(), array('user_id' => $user_id), array('%d')); }
add_action('deleted_user', 'fcoi_forget_user');
add_action('wpmu_delete_user', 'fcoi_forget_user');

function fcoi_permission() { return is_user_logged_in() && current_user_can('read'); }
function fcoi_read() {
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare('SELECT revision, pedigree FROM ' . fcoi_table() . ' WHERE user_id = %d', get_current_user_id()), ARRAY_A);
    if (!$row) { return array('revision' => 0, 'cats' => array()); }
    return array('revision' => (int)$row['revision'], 'cats' => json_decode($row['pedigree'], true));
}
function fcoi_save($request) {
    global $wpdb;
    if (strlen($request->get_body()) > 524288) { return new WP_Error('fcoi_size', 'Pedigree exceeds 512 KB.', array('status'=>413)); }
    $data = $request->get_json_params();
    if (!is_array($data) || !isset($data['revision'], $data['cats']) || !is_int($data['revision']) || $data['revision'] < 0) {
        return new WP_Error('fcoi_data', 'Provide cats and a non-negative integer revision.', array('status'=>400));
    }
    try { $engine = new FCOI_Engine($data['cats']); }
    catch (InvalidArgumentException $e) { return new WP_Error('fcoi_pedigree', $e->getMessage(), array('status'=>400)); }
    $cats = $engine->rows();
    foreach ($cats as &$cat) { $cat['name'] = sanitize_text_field($cat['name']); $cat['registration'] = sanitize_text_field($cat['registration']); if ($cat['name'] === '') { return new WP_Error('fcoi_name', 'A cat name cannot be empty.', array('status'=>400)); } } unset($cat);
    try { new FCOI_Engine($cats); } catch (InvalidArgumentException $e) { return new WP_Error('fcoi_pedigree', $e->getMessage(), array('status'=>400)); }
    $table = fcoi_table(); $uid = get_current_user_id();
    $created = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (user_id, revision, pedigree) VALUES (%d, 0, %s)", $uid, '[]'));
    if ($created === false) { return new WP_Error('fcoi_db', 'Could not save. Please contact the site administrator.', array('status'=>500)); }
    $updated = $wpdb->query($wpdb->prepare("UPDATE $table SET pedigree = %s, revision = revision + 1 WHERE user_id = %d AND revision = %d", wp_json_encode($cats), $uid, $data['revision']));
    if ($updated === false) { return new WP_Error('fcoi_db', 'Could not save pedigree.', array('status'=>500)); }
    if ($updated !== 1) { return new WP_Error('fcoi_conflict', 'Your pedigree changed in another tab. Export your current work, then reload before saving.', array('status'=>409)); }
    return array('revision'=>$data['revision'] + 1, 'cats'=>$cats);
}
function fcoi_calculate($request) {
    $data = $request->get_json_params();
    if (!is_array($data) || !isset($data['id']) || !is_string($data['id']) || (isset($data['dam']) && !is_string($data['dam']))) {
        return new WP_Error('fcoi_input', 'Select a cat or mating pair.', array('status'=>400));
    }
    try {
        $stored = fcoi_read(); $engine = new FCOI_Engine($stored['cats']);
        $result = $engine->calculate($data['id'], isset($data['dam']) ? $data['dam'] : null);
        $result['revision'] = $stored['revision']; return $result;
    } catch (InvalidArgumentException $e) { return new WP_Error('fcoi_calc', $e->getMessage(), array('status'=>400)); }
}
add_action('rest_api_init', function() {
    register_rest_route('feline-coi/v1', '/pedigree', array(
        array('methods'=>'GET','callback'=>'fcoi_read','permission_callback'=>'fcoi_permission'),
        array('methods'=>'PUT','callback'=>'fcoi_save','permission_callback'=>'fcoi_permission')
    ));
    register_rest_route('feline-coi/v1', '/calculate', array('methods'=>'POST','callback'=>'fcoi_calculate','permission_callback'=>'fcoi_permission'));
});
function fcoi_render() {
    if (!is_user_logged_in()) { return '<div class="fcoi-login"><p>Sign in to save your private cat pedigree and calculate COI.</p><a href="' . esc_url(wp_login_url(get_permalink())) . '">Sign in</a></div>'; }
    wp_enqueue_style('feline-coi', plugins_url('assets/app.css', __FILE__), array(), '0.1.0');
    wp_enqueue_script('feline-coi', plugins_url('assets/app.js', __FILE__), array(), '0.1.0', true);
    wp_localize_script('feline-coi', 'FCOI_CONFIG', array('api'=>esc_url_raw(rest_url('feline-coi/v1/')), 'nonce'=>wp_create_nonce('wp_rest')));
    return '<div class="fcoi-app"><p>Loading your pedigree…</p></div>';
}
add_shortcode('feline_coi', 'fcoi_render');
add_action('admin_menu', function() {
    add_management_page('Feline COI', 'Feline COI', 'read', 'feline-coi', function() {
        echo '<div class="wrap"><h1>Feline COI</h1><p>Add <code>[feline_coi]</code> to a WordPress page to offer this calculator to signed-in users. Exclude that page from full-page caching.</p>';
        echo fcoi_render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generated with escaped configuration.
        echo '</div>';
    });
});
// Include stored pedigrees in WordPress personal-data export and erasure tools.
add_filter('wp_privacy_personal_data_exporters', function($exporters) {
    $exporters['feline-coi'] = array('exporter_friendly_name'=>'Feline COI', 'callback'=>function($email, $page=1) {
        global $wpdb; $user = get_user_by('email', $email); $data = array();
        if ($user) {
            $json = $wpdb->get_var($wpdb->prepare('SELECT pedigree FROM ' . fcoi_table() . ' WHERE user_id = %d', $user->ID));
            if ($json) { $data[] = array('group_id'=>'feline-coi','group_label'=>'Feline COI pedigree','item_id'=>'feline-coi-' . $user->ID,'data'=>array(array('name'=>'Pedigree JSON','value'=>$json))); }
        }
        return array('data'=>$data,'done'=>true);
    }); return $exporters;
});
add_filter('wp_privacy_personal_data_erasers', function($erasers) {
    $erasers['feline-coi'] = array('eraser_friendly_name'=>'Feline COI', 'callback'=>function($email, $page=1) {
        global $wpdb; $user = get_user_by('email', $email); $count = 0;
        if ($user) { $count = $wpdb->delete(fcoi_table(), array('user_id'=>$user->ID), array('%d')); }
        return array('items_removed'=>$count > 0,'items_retained'=>$count === false,'messages'=>$count === false ? array('Feline COI erasure failed; contact the administrator.') : array(),'done'=>true);
    }); return $erasers;
});
