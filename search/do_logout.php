<?php
error_reporting(E_ALL);

$is_cli = FALSE;
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting");

$location = '';
if (!$is_cli)
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die("failed to ob_start(), aborting");

 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
 $firephp->setEnabled(TRUE);
 $firephp->log('started script...');

 // set default header 
 header(':', TRUE, 500); // == 'Internal Server Error'

 if (isset($_POST['location'])) $location = $_POST['location'];
}

//$system = php_uname('s');
// $system_is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
// $ini_file = $cwd . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'geo_php.ini';
// define('DATA_DIR', $cwd . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
// $options = parse_ini_file($ini_file, TRUE);
// if ($options === FALSE) die("failed to parse_ini_file(\"$ini_file\"), aborting");
// $os_section = ($system_is_windows ? 'geo_windows' : 'geo_unix');
// $loc_section = 'geo_db_' . $location;

// sanity check(s)
// if (count($options) == 0) die("failed to parse init file (was: \"$ini_file\"), aborting");

// // sanity check(s)
// if (!isset($_SERVER['PHP_AUTH_USER']) ||
    // (strcmp($_SERVER['PHP_AUTH_USER'], $options['geo']['logout_user_name']) !== 0) ||
				// (strcmp($_SERVER['PHP_AUTH_PW'], $options['geo']['logout_user_pw']) !== 0))
// {
 // header('WWW-Authenticate: Basic realm="' . $options['geo']['http_auth_realm'] . '"');
 // header(':', TRUE, 401); // Unauthorized
 // die('invalid user/pw (was: ' . $_SERVER['PHP_AUTH_USER'] . '/' . $_SERVER['PHP_AUTH_PW'] . ')');
// }

if (!$is_cli)
{
 $json_content = json_encode($_POST);
 if ($json_content === FALSE)
  die("failed to json_encode(\"$_POST\"): " . json_last_error() . ", aborting\n");
 // var_dump($json_content);
 $firephp->log($json_content, 'response');

 // set header status
 header(':', TRUE, 200); // OK
 // send the content back
 echo("$json_content");

 $firephp->log('ending script...');

 // fini output buffering
 if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
}
?>
