<?php
error_reporting(E_ALL);
//require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

$cwd = getcwd();
if ($cwd === FALSE) die('failed to getcwd(), aborting' . PHP_EOL);

// init output buffering
if (!ob_start()) die('failed to ob_start(), aborting' . PHP_EOL);

//$firephp = FirePHP::getInstance(TRUE);
//if (is_null($firephp)) die('failed to FirePHP::getInstance(), aborting' . PHP_EOL);
//$firephp->setEnabled(FALSE);
//$firephp->log('started script...');

// set default header
header('', TRUE, 500); // == 'Internal Server Error'

if (empty($_POST)) die('invalid invocation ($_POST was empty), aborting' . PHP_EOL);
$location = 'nrw';
if (isset($_POST['location'])) $location = $_POST['location'];
$mode = 'c';
if (isset($_POST['mode'])) $mode = $_POST['mode'];
switch ($mode)
{
 case 'c':
 case 'd':
 case 'u':
  break;
 default:
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
$container = NULL;
if (isset($_POST['container'])) $container = json_decode($_POST['container'], TRUE);

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die('invalid file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
                                      'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die('failed to parse init file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if ($container === NULL) die('invalid container parameter (was: "' . $_POST['container'] . '"), aborting');
if (count($options) == 0) die('failed to parse init file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
$db_containers_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                                                                                                        : $options[$os_section]['db_base_dir']) .
                      DIRECTORY_SEPARATOR .
                                            (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                   : '') .
                                            (isset($options[$loc_section]['db_contacts_dbf']) ? $options[$loc_section]['db_containers_dbf']
                                                                                                                                                : $options['geo_db']['db_containers_dbf']);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_containers_file)) die("\"$db_containers_file\" not readable, aborting");
if (!file_exists($db_containers_file)) die('db file does not exist (was: "' . $db_containers_file . '"), aborting' . PHP_EOL);
//$firephp->log($db_containers_file, 'containers database');

// init dBase
// *NOTE*: open DB read-write
$db_containers = dbase_open($db_containers_file, 2);
if ($db_containers === FALSE) die('failed to dbase_open("' . $db_containers_file . '", 2), aborting' . PHP_EOL);
//$firephp->log('opened containers db...');
$num_containers_records = dbase_numrecords($db_containers);
if ($num_containers_records === FALSE)
{
 dbase_close($db_containers);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
//$firephp->log($num_containers_records, '#records (containers)');

$success = FALSE;
$conflict = FALSE;
$cont_id_db = mb_convert_encoding($container['CONTID'],
                                                                    $options['geo_db']['db_containers_cp'],
                                                                    'UTF-8');
switch ($mode)
{
 case 'c':
 {
  // sanity check: validate that this record ID does not exist yet...
  for ($i = 1; $i <= $num_containers_records; $i++)
  {
   $db_record = dbase_get_record($db_containers, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db_containers);
    die('failed to dbase_get_record(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   if ($db_record['deleted'] == 1) continue;
   if (strcmp($cont_id_db, trim($db_record[0])) == 0)
   {
//    $firephp->log($container['CONTID'], 'conflict, continuing');
    $conflict = TRUE;
    break 2;
   }
  }

  $field_info = dbase_get_header_info($db_containers);
  if ($field_info === FALSE)
  {
   dbase_close($db_containers);
   die('failed to dbase_get_header_info(), aborting' . PHP_EOL);
  }
  //$firephp->log($field_info, 'field info');
  //print_r($field_info);

  $db_record = dbase_get_record($db_containers, 1);
  if ($db_record === FALSE)
  {
   dbase_close($db_containers);
   die('failed to dbase_get_record(1), aborting' . PHP_EOL);
  }
  unset($db_record['deleted']);

//   $firephp->log($db_record, 'record');
  foreach ($db_record as $key => $value)
   switch ($field_info[$key]['type'])
   {
        case 'boolean':
          $db_record[$key] = false;
          break;
        case 'character':
          $db_record[$key] = '';
          break;
        case 'date':
          $db_record[$key] = date('Ymd', 0);
          break;
              case 'memo':
          $db_record[$key] = '';
          break;
              case 'number':
          $db_record[$key] = 0;
          break;
        default:
          dbase_close($db_contacts);
          die('invalid field type (index: ' .
                  $key .
                  ', was: ' .
                  $field_info[$key]['type'] .
                  "), aborting");
          break;
   }

  // CONTID
  $db_record[0] = $cont_id_db;
  // CONTTYPE
  $db_record[1] = mb_convert_encoding($container['CONTTYPE'],
                                                                            $options['geo_db']['db_containers_cp'],
                                                                            'UTF-8');
  // ACQUIRED
  $db_record[2] = '';
  // STATUS
  $db_record[3] = mb_convert_encoding($container['STATUS'],
                                                                            $options['geo_db']['db_containers_cp'],
                                                                            'UTF-8');
  // LASTREPAIR
  $db_record[4] = mb_convert_encoding($container['LASTREPAIR'],
                                                                            $options['geo_db']['db_containers_cp'],
                                                                            'UTF-8');
  // LOCKTYPE
  // SERIALNR
  $db_record[6] = mb_convert_encoding($container['SERIALNR'],
                                                                            $options['geo_db']['db_containers_cp'],
                                                                            'UTF-8');
  // _MODIFIED
  $db_record[7] = date('Ymd', time());
  // COMMENT
  $db_record[8] = mb_convert_encoding($container['COMMENT'],
                                                                            $options['geo_db']['db_containers_cp'],
                                                                            'UTF-8');
  // PRODUCED
  $db_record[9] = '';
  // COPY_CONTI
  // COPY2_CONT
  // COPY3_CONT

  if (!dbase_add_record($db_containers, $db_record))
  {
   // var_dump($db_record);
   dbase_close($db_containers);
   die('failed to dbase_add_record(), aborting' . PHP_EOL);
  }
  $success = TRUE;
  break;
 }
 case 'd':
 {
  for ($i = 1; $i <= $num_containers_records; $i++)
  {
   $db_record = dbase_get_record_with_names($db_containers, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db_containers);
    die('failed to dbase_get_record_with_names(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   if (($db_record['deleted'] == 1) ||
       (strcmp(trim($db_record['CONTID']) != $cont_id_db) != 0)) continue;

   if (!dbase_delete_record($db_containers, $i))
   {
    // var_dump($db_record);
        dbase_close($db_containers);
    die('failed to dbase_delete_record(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   $success = TRUE;
   break;
  }
  break;
 }
 case 'u':
 {
  for ($i = 1; $i <= $num_containers_records; $i++)
  {
   $db_record = dbase_get_record($db_containers, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db_containers);
    die('failed to dbase_get_record(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   if (($db_record['deleted'] == 1) ||
       (strcmp(trim($db_record[0]), $cont_id_db) != 0)) continue;

   // CONTID
   // $db_record[0] = $cont_id_db;
   // CONTTYPE
   $db_record[1] = mb_convert_encoding($container['CONTTYPE'],
                                                                              $options['geo_db']['db_containers_cp'],
                                                                              'UTF-8');
   // ACQUIRED
   // STATUS
   $db_record[3] = mb_convert_encoding($container['STATUS'],
                                                                              $options['geo_db']['db_containers_cp'],
                                                                              'UTF-8');
   // LASTREPAIR
   $db_record[4] = mb_convert_encoding($container['LASTREPAIR'],
                                                                              $options['geo_db']['db_containers_cp'],
                                                                              'UTF-8');
   // LOCKTYPE
   // SERIALNR
   $db_record[6] = mb_convert_encoding($container['SERIALNR'],
                                                                              $options['geo_db']['db_containers_cp'],
                                                                              'UTF-8');
   // _MODIFIED
   $db_record[7] = date('Ymd', time());
   // COMMENT
   $db_record[8] = mb_convert_encoding($container['COMMENT'],
                                                                              $options['geo_db']['db_containers_cp'],
                                                                              'UTF-8');
   // PRODUCED
   // COPY_CONTI
   // COPY2_CONT
   // COPY3_CONT

   unset($db_record['deleted']);
   if (!dbase_replace_record($db_containers, $db_record, $i))
   {
    // var_dump($db_record);
        dbase_close($db_containers);
    die("failed to dbase_replace_record($i), aborting\n");
   }
   $success = TRUE;
   break;
  }
  break;
 }
 default:
  dbase_close($db_containers);
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
if (dbase_close($db_containers) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
//$firephp->log('closed containers db...');

if ($success)
{
 switch ($mode)
 {
  case 'c':
      // http_response_code(201); // == 'Created'
   header('', TRUE, 201); // == 'Created'
   break;
  case 'd':
  case 'u':
      // http_response_code(200); // == 'OK'
   header('', TRUE, 200); // == 'OK'
   break;
  default:
   die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
 }
}
else
{
 switch ($mode)
 {
  case 'c':
      // http_response_code(($conflict ? 409 : 500)); // == 'Conflict' : 'Internal Server Error'
   if ($conflict) header('', TRUE, 409); // == 'Conflict'
   break;
  case 'd':
  case 'u':
//   http_response_code(404); // == 'Not Found'
   header('', TRUE, 404); // == 'Not Found'
   break;
  default:
   die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
 }
 die('failed to edit container (CID was: ' . $container['CONTID'] . '), aborting' . PHP_EOL);
}
$json_content = json_encode($_POST);
if ($json_content === FALSE)
{
 header('', TRUE, 500); // == 'Internal Server Error'
 die('failed to json_encode("' . $_POST . '"): "' . json_last_error() . '", aborting' . PHP_EOL);
}
// $json_content['contact'] = $_POST['contact'];
// var_dump($json_content);
// $firephp->log($json_content, 'response');

// send the content back
echo("$json_content");

//$firephp->log('ending script...');

// fini output buffering
if (!ob_end_flush()) die('failed to ob_end_flush(), aborting' . PHP_EOL);
?>
