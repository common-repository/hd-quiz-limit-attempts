<?php
/*
 * Plugin Name: HD Quiz - Limit Attempts
 * Description: Addon for HD Quiz to limit how many times a user can take quizzes
 * Plugin URI: https://harmonicdesign.ca/addons/limit-attempts/
 * Author: Harmonic Design
 * Author URI: https://harmonicdesign.ca
 * Version: 0.4
*/

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

if (!defined('HDQ_A_LIMIT_ATTEMPTS')) {
    define('HDQ_A_LIMIT_ATTEMPTS', '0.5');
}

/* Automatically deactivate if HD Quiz is not active
------------------------------------------------------- */
function hdq_a_limit_attempts()
{
    if (function_exists('is_plugin_active')) {
        if (!is_plugin_active("hd-quiz/index.php")) {
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }
}
add_action('init', 'hdq_a_limit_attempts');

/* Include main files
------------------------------------------------------- */
require dirname(__FILE__) . '/classes/sanitize.php';
require dirname(__FILE__) . '/classes/fields.php';
require dirname(__FILE__) . '/includes/functions.php';


/* Create HD Quiz Results light Settings page
------------------------------------------------------- */
function hdq_limit_attempts_submenu()
{
    add_submenu_page('hdq_quizzes', 'Limit Attempts', 'Limit Attempts', 'edit_others_pages', 'hdq_limit', 'hdq_a_limit_attempts_page_callback');
}
add_action('admin_menu', 'hdq_limit_attempts_submenu', 11);


function hdq_a_limit_attempts_page_callback()
{
    require dirname(__FILE__) . '/includes/limit.php';
}
