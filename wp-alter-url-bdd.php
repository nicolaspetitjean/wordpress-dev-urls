#!/usr/bin/env php
<?php
$old_url = ''; // Old url (no http)
$new_url = ''; // New url (no http)
$db_prefix = ''; // Db prefix
$enable_ssl = true;

/**
 * MySQL settings
 */
/** The name of the database */
define('DB_NAME', 'my_db');
/** MySQL database username */
define('DB_USER', 'username');
/** MySQL database password */
define('DB_PASSWORD', 'passwd');
/** MySQL hostname */
define('DB_HOST', '127.0.0.1');

/**
 * Check if table exists
 *
 * @param $tbl
 * @return bool
 */
function table_exists($tbl) {
	global $db, $db_prefix;

	// Check for custom table prefix
	if($db_prefix != 'wp_' && strpos($tbl, 'wp_') === 0) {
		$tbl = preg_replace('/^wp_/', $db_prefix, $tbl);
	}
	$return = true;
	$sql = "SHOW TABLES LIKE '{$tbl}'";
	$result = $db->query($sql);
	if(!$result->fetchColumn()) {
		$return = false;
	}
	return $return;
}

/**
 * Update the table with new data
 *
 * @param $field_id
 * @param $field_value
 * @param $tbl
 * @param null $cond
 * @param null $add_http
 * @return void
 */
function maj_table($field_id, $field_value, $tbl, $cond = null, $add_http = null)
{
    global $db, $old_url, $new_url, $db_prefix, $enable_ssl;

    if($cond) {
        $cond = ' ' . $cond;
    }
    $prefix = '';
    if($add_http) {
        $prefix = 'http://';
    }
    // Check for custom table prefix
    if($db_prefix != 'wp_' && strpos($tbl, 'wp_') === 0) {
        $tbl = preg_replace('/^wp_/', $db_prefix, $tbl);
    }
    // Get data
    $sql = "SELECT {$field_id},{$field_value} FROM {$tbl}" . $cond;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $newmeta = array();
    while($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $newmeta[$post[$field_id]] = str_replace($prefix . $old_url, $prefix . $new_url, $post[$field_value]);
        if($enable_ssl) {
            //$replace = preg_replace('/http(?!s)/', 'https', $newmeta[$post[$field_id]]);
            $replace = str_replace('http://', 'https://', $newmeta[$post[$field_id]]);
        } else {
            $replace = str_replace('https://', 'http://', $newmeta[$post[$field_id]]);
        }
        $newmeta[$post[$field_id]] = $replace;
    }
    if(empty($newmeta)) {
        return false;
    }
    $sql = "UPDATE {$tbl} SET {$field_value}=? WHERE {$field_id}=?";
    $stmt = $db->prepare($sql);
    $a = 0;
    foreach($newmeta as $k => $v) {
        $array[0] = $v;
        $array[1] = $k;
        if($stmt->execute($array)) {
            $a++;
        }
    }
    echo "$a lines updated in $tbl\n";
}

/* Check script is configured */
if(empty($db_prefix) OR empty($old_url) OR empty($new_url)) {
	die("Please configure this script before running it.\n");
}

/* Use PDO */
$host = 'mysql:host='. DB_HOST.';dbname='.DB_NAME.';charset=utf8';
try
{
	$db = new PDO($host, DB_USER, DB_PASSWORD);
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e)
{
    die("Unable to connect to the database.\n");
}
/* Table : wp_posts */
if(table_exists('wp_posts')) {
	maj_table('ID', 'guid', 'wp_posts', null, true);
}
/* Table : wp_posts */
if(table_exists('wp_posts')) {
	maj_table('ID', 'post_content', 'wp_posts', null, true);
}
/* Table : wp_usermeta */
if(table_exists('wp_usermeta')) {
	maj_table('umeta_id', 'meta_value', 'wp_usermeta', 'WHERE meta_key = \'source_domain\'');
}
/* Table : wp_sitemeta */
if(table_exists('wp_sitemeta')) {
	maj_table('meta_id', 'meta_value', 'wp_sitemeta', 'WHERE meta_key = \'siteurl\'');
}
/* Table : wp_site */
if(table_exists('wp_sitemeta')) {
	maj_table('id', 'domain', 'wp_site');
}
/* Table : wp_options */
if(table_exists('wp_options')) {
	maj_table('option_id', 'option_value', 'wp_options', 'WHERE option_name IN (\'siteurl\', \'home\')');
}
/* Table : wp_blogs */
if(table_exists('wp_blogs')) {
	maj_table('blog_id', 'domain', 'wp_blogs');
}
/* Table : wp_revslider_slides */
if(table_exists('wp_revslider_slides')) {
	maj_table('id', 'params', 'wp_revslider_slides');
}
/* Table : wp_revslider_slides */
if(table_exists('wp_revslider_slides')) {
	maj_table('id', 'layers', 'wp_revslider_slides');
}
/* Table : wp_2_revslider_slides */
if(table_exists('wp_2_revslider_slides')) {
	maj_table('id', 'params', 'wp_2_revslider_slides');
}
/* Table : wp_2_revslider_slides */
if(table_exists('wp_2_revslider_slides')) {
	maj_table('id', 'layers', 'wp_2_revslider_slides');
}
/* Table : wp_2_posts */
if(table_exists('wp_2_posts')) {
	maj_table('ID', 'guid', 'wp_2_posts', null, true);
}
/* Table : wp_2_posts */
if(table_exists('wp_2_posts')) {
	maj_table('ID', 'post_content', 'wp_2_posts', null, true);
}
/* Table : wp_2_options */
if(table_exists('wp_2_options')) {
	maj_table('option_id', 'option_value', 'wp_2_options', 'WHERE option_name IN (\'siteurl\', \'home\')');
}