<?php
error_reporting(E_ALL);

$is_cli = empty($_POST);
$cwd = getcwd();
if ($cwd === FALSE) die('failed to getcwd(), aborting' . PHP_EOL);

$location = '';
$task = '';
$async = TRUE;
$refresh_only = FALSE;
if ($is_cli)
{
 if (($argc < 2) || ($argc > 5)) die('usage: ' .
                                      basename($argv[0]) .
                                      ' <location> [<task[all|containers|images|sites|toursets]>] [<async[0|1]>] [<refresh_only[0|1]>]');
 $location = $argv[1];
 if (isset($argv[2]))
 {
  if (!ctype_digit($argv[2])) $task = $argv[2];
  else $async = (intval($argv[2]) === 1);
 }
 if (isset($argv[3])) $async = (intval($argv[2]) === 1);
 if (isset($argv[4])) $refresh_only = (intval($argv[2]) === 1);
}
else
{
// require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die('failed to ob_start(), aborting' . PHP_EOL);

// $firephp = FirePHP::getInstance(TRUE);
// if (is_null($firephp)) die('failed to FirePHP::getInstance(), aborting' . PHP_EOL);
// $firephp->setEnabled(FALSE);
// $firephp->log('started script...');

 // set default header 
 header('', TRUE, 500); // == 'Internal Server Error'

 if (isset($_POST['location'])) $location = $_POST['location'];
 if (isset($_POST['task'])) $task = $_POST['task'];
 if (isset($_POST['async'])) $async = (strcmp(strtoupper($_POST['async']), 'TRUE') === 0);
 if (isset($_POST['refresh_only'])) $refresh_only = (strcmp(strtoupper($_POST['refresh_only']), 'TRUE') === 0);
}

$system_is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die('invalid file (was: "' .
                                  $ini_file .
                                  '"), aborting' . PHP_EOL);
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
                  'data' .
                  DIRECTORY_SEPARATOR .
                  $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die('failed to parse init file (was: "' .
                            $ini_file .
                            '"), aborting' . PHP_EOL);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die('failed to parse init file (was: "' .
                                                            $ini_file .
                                                            '"), aborting' . PHP_EOL);
$script_file = '';
$command_line = '';
$command_prefix = ($system_is_windows ? ($async ? 'start /b ' : '')
                                      : 'nohup ');
if (!$async) $command_prefix = '';
//$command_postfix = ($system_is_windows ? ' >NUL 2>&1 <NUL && exit'
//$command_postfix = ($system_is_windows ? ' >NUL 2>&1'
$command_postfix = ($system_is_windows ? ' >NUL 2>&1'
                                       : ' >/dev/null 2>&1 </dev/null & echo ${!};');
if (!$async) $command_postfix = ' 2>&1';
switch ($task)
{
 case 'all':
  $script_file = $cwd .
                                  DIRECTORY_SEPARATOR .
                 $options['geo']['tools_dir'] .
                 DIRECTORY_SEPARATOR .
                                  $options['geo']['process_script'] .
                                  $options[$os_section]['script_ext'];
  $command_line = $command_prefix . $script_file . $command_postfix;
  if (!empty($location)) $command_line .= (' ' . $location);
  if ($refresh_only) $command_line .= (' ' . strval(1));
  $command_line .= $command_postfix;
  break;
 case 'containers':
  $script_file = $cwd .
                                  DIRECTORY_SEPARATOR .
                 $options['geo']['tools_dir'] .
                 DIRECTORY_SEPARATOR .
                                  $options['geo']['process_containers_script'] .
                                  $options[$os_section]['script_ext'];
  $command_line = $command_prefix . $script_file;
  if (!empty($location)) $command_line .= " $location";
  if ($refresh_only) $command_line .= " 1";
  $command_line .= $command_postfix;
  break;
 case 'counters':
  $script_file = $cwd .
                                  DIRECTORY_SEPARATOR .
                 $options['geo']['tools_dir'] .
                 DIRECTORY_SEPARATOR .
                                  'reset_counters.cmd';
  $command_line = $command_prefix . $script_file . $command_postfix;
  break;
 case 'images':
  $script_file = $cwd .
                                  DIRECTORY_SEPARATOR .
                 $options['geo']['tools_dir'] .
                 DIRECTORY_SEPARATOR .
                                  $options['geo']['process_images_script'] .
                                  $options[$os_section]['script_ext'];
  $command_line = $command_prefix . $script_file;
  if (!empty($location)) $command_line .= (' ' . $location);
  if ($refresh_only) $command_line .= (' ' . strval(1));
  $command_line .= $command_postfix;
  break;
 case 'sites':
  $script_file = $cwd .
                                  DIRECTORY_SEPARATOR .
                 $options['geo']['tools_dir'] .
                 DIRECTORY_SEPARATOR .
                                  $options['geo']['process_sites_script'] .
                                  $options[$os_section]['script_ext'];
  $command_line = $command_prefix . $script_file;
  if (!empty($location)) $command_line .= (' ' . $location);
  if ($refresh_only) $command_line .= (' ' . strval(1));
  $command_line .= $command_postfix;
  break;
 case 'toursets':
  $script_file = $cwd .
                                  DIRECTORY_SEPARATOR .
                 $options['geo']['tools_dir'] .
                 DIRECTORY_SEPARATOR .
                                  $options['geo']['process_toursets_script'] .
                                  $options[$os_section]['script_ext'];
  $command_line = $command_prefix . $script_file;
  if (!empty($location)) $command_line .= (' ' . $location);
  if ($refresh_only) $command_line .= (' ' . strval(1));
  $command_line .= $command_postfix;
  break;
 default:
  die('invalid task (was: "' .
            $task .
            '"), aborting');
}

$output = array();
$pid = -1;
$return_value = -1;
if ($command_line)
{
 // *WARNING* is_executable() fails on batchfiles (windows)
 if ($system_is_windows)
 {
  if (!file_exists($script_file))
   die('invalid file (was: "' .
              $script_file .
              '", aborting' . PHP_EOL);
 }
 elseif (!is_executable($script_file))
 {
  die('file not executable (was: "' .
            $script_file .
            '", aborting' . PHP_EOL);
 }

 if (!$is_cli)
 //$firephp->log($script_file, 'script')
 ;
 if (!$is_cli)
 //$firephp->log($command_line, 'command line')
 ;
  
 // run command
 set_time_limit(0);
 if ($system_is_windows)
 {
  if (!$async) exec($command_line, $output, $return_value);
  else
  {
   $fd = popen($command_line, 'r');
   if ($fd === FALSE) die('failed to popen("' .
                                                    $command_line .
                                                    '"), aborting' . PHP_EOL);
   $pid = strval($fd);
   $return_value = pclose($fd);
  }
 }
 else
 {
  exec($command_line, $output, $return_value);
  if ($async) $pid = intval($output[0]);
 }
}
else
{
 $return_value = 0;
}

if ($async)
{
 if (!$is_cli) $_POST['PID'] = $pid;
 if (!$is_cli)
 //$firephp->log($pid, 'PID')
 ;
}
else
{
 if (!$is_cli) $_POST['output'] = $output;
 else echo print_r($output, TRUE);
 if (!$is_cli) $_POST['status'] = $return_value;
 if (!$is_cli)
 //$firephp->log($output, 'output')
 ;
 if (!$is_cli)
 //$firephp->log($return_value, 'return value')
 ;
}

if (!$is_cli)
{
 $json_content = json_encode($_POST);
 if ($json_content === FALSE)
  die('failed to json_encode("' . $_POST . '"): ' . json_last_error() . ', aborting' . PHP_EOL);
 // var_dump($json_content);
 //$firephp->log($json_content, 'response');

 // set header status
 header('', TRUE, ($async ? (($return_value === 0) ? 200 : 500) // == 'OK' | 'internal server error'
                          : 200));                              // == 'OK'
 // send the content back
 echo("$json_content");

 //$firephp->log('ending script...');

 // fini output buffering
 if (!ob_end_flush()) die('failed to ob_end_flush(), aborting' . PHP_EOL);
}
?>
