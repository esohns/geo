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
$sub_mode = '';
if (isset($_POST['sub_mode'])) $sub_mode = $_POST['sub_mode'];
switch ($mode)
{
 case 'c':
  switch ($sub_mode)
  {
   case '':     // create && do NOT link
   case 'link': // create && link
    break;
   default:
    die('invalid mode/submode (was: "' . $mode . '"/"' . $sub_mode . '"), aborting' . PHP_EOL);
  }
  break;
 case 'd':
  switch ($sub_mode)
  {
   case '':       // delete && unlink
   case 'unlink': // unlink ONLY
    break;
   default:
    die('invalid mode/submode (was: "' . $mode . '"/"' . $sub_mode . '"), aborting' . PHP_EOL);
  }
  break;
 case 'u':
  switch ($sub_mode)
  {
   case '':     // update ONLY
   case 'link': // link ONLY
    break;
   default:
    die('invalid mode/submode (was: "' . $mode . '"/"' . $sub_mode . '"), aborting' . PHP_EOL);
  }
  break;
 default:
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
$contact = NULL;
if (isset($_POST['contact'])) $contact = json_decode($_POST['contact'], TRUE);
$site = -1;
if (isset($_POST['site'])) $site = intval($_POST['site']);

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
if ($contact === NULL) die('invalid contact parameter (was: "' . $_POST['contact'] . '"), aborting' . PHP_EOL);
if (count($options) == 0) die('failed to parse init file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
$db_contacts_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                                   : $options[$os_section]['db_base_dir']) .
                                        DIRECTORY_SEPARATOR .
                                       (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                                                                                  : '') .
                                        (isset($options[$loc_section]['db_contacts_dbf']) ? $options[$loc_section]['db_contacts_dbf']
                                                                                                                                          : $options['geo_db']['db_contacts_dbf']);
$db_relation_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                                   : $options[$os_section]['db_base_dir']) .
                    DIRECTORY_SEPARATOR .
                                        (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                 : '') .
                                        (isset($options[$loc_section]['db_relation_dbf']) ? $options[$loc_section]['db_relation_dbf']
                                                                                                                                            : $options['geo_db']['db_relation_dbf']);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_contacts_file)) die('"' . $db_contacts_file . '" not readable, aborting' . PHP_EOL);
if (!file_exists($db_contacts_file)) die('db file does not exist (was: "' . $db_contacts_file . '"), aborting' . PHP_EOL);
//if (!is_readable($db_contacts_file)) die('"' . $db_contacts_file . '" not readable, aborting' . PHP_EOL);
if (!file_exists($db_relation_file)) die('db file does not exist (was: "' . $db_relation_file . '"), aborting' . PHP_EOL);
//$firephp->log($db_contacts_file, 'contacts database');
//$firephp->log($db_relation_file, 'relation database');

// init dBase
// *NOTE*: open DB read-write
$db_contacts = dbase_open($db_contacts_file, 2);
if ($db_contacts === FALSE) die('failed to dbase_open("' . $db_contacts_file . '2), aborting' . PHP_EOL);
//$firephp->log('opened contacts db...');
$num_contacts_records = dbase_numrecords($db_contacts);
if ($num_contacts_records === FALSE)
{
 dbase_close($db_contacts);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
//$firephp->log($num_contacts_records, '#records (contacts)');

$success = FALSE;
$conflict = FALSE;
switch ($mode)
{
 case 'c':
 {
  // sanity check: validate that this record ID does not exist yet...
  for ($i = 1; $i <= $num_contacts_records; $i++)
  {
   $db_record = dbase_get_record($db_contacts, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db_contacts);
    die('failed to dbase_get_record(' . 
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   if ($db_record['deleted'] == 1) continue;
   if ($db_record[20] == $contact['CONTACTID'])
   {
//    $firephp->log($contact['CONTACTID'], 'conflict, continuing');
    $conflict = TRUE;
    break 2;
   }
  }

  $field_info = dbase_get_header_info($db_contacts);
  if ($field_info === FALSE)
  {
   dbase_close($db_contacts);
   die('failed to dbase_get_header_info(), aborting' . PHP_EOL);
  }
  //$firephp->log($field_info, 'field info');
  //print_r($field_info);

  $db_record = dbase_get_record($db_contacts, 1);
  if ($db_record === FALSE)
  {
   dbase_close($db_contacts);
   die('failed to dbase_get_record(1), aborting' . PHP_EOL);
  }
  unset($db_record['deleted']);

//   $firephp->log($db_record, 'record');
  foreach ($db_record as $key => $value)
   switch ($field_info[$key]['type'])
   {
        case 'boolean':
          $db_record[$key] = FALSE;
          break;
        case 'character':
          $db_record[$key] = '';
          break;
        case 'date':
          $db_record[$key] = date('Ymd', 0);
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
                  '), aborting' . PHP_EOL);
          break;
   }
  // GROUP
  $db_record[0] = mb_convert_encoding($contact['GROUP'],
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
  // COMPANY
  $db_record[1] = mb_convert_encoding($contact['COMPANY'],
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
  // STREET
  $db_record[2] = mb_convert_encoding($contact['STREET'],
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
  // CITY
  $db_record[3] = mb_convert_encoding($contact['CITY'],
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
  // TEL
  $db_record[4] = mb_convert_encoding($contact['TEL'],
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
  // FAX
  $db_record[5] = mb_convert_encoding($contact['FAX'],
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
  // REGISTERED
  $db_record[6] = (empty($contact['REGISTERED']) ? date('Ymd', time())
                                                 : $contact['REGISTERED']);
  // N_MODIFIED/_MODIFIED
  $db_record[7] = date('Ymd', time());
  // STATUS
  $db_record[8] = mb_convert_encoding($options['geo_data_contacts']['data_contacts_status_desc'],
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            mb_internal_encoding());
  // ZIP
  $db_record[9] = mb_convert_encoding(strval($contact['ZIP']),
                                                                            $options['geo_db']['db_contacts_cp'],
                                                                            mb_internal_encoding());
  // COUNTRY
  $db_record[10] = mb_convert_encoding($contact['COUNTRY'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // FIRSTNAME
  $db_record[11] = mb_convert_encoding($contact['FIRSTNAME'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // LASTNAME
  $db_record[12] = mb_convert_encoding($contact['LASTNAME'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // MORENAMES
  // DEPARTMENT
  $db_record[14] = mb_convert_encoding($contact['DEPARTMENT'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // SEX
  // STYLE
  // JOBTITLE
  $db_record[17] = mb_convert_encoding($contact['JOBTITLE'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // COMMENT
  $db_record[18] = mb_convert_encoding($contact['COMMENT'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                              'UTF-8');
  // COLLECTION
  $db_record[19] = mb_convert_encoding($options[$loc_section]['collection'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                              mb_internal_encoding());
  // CONTACTID
  $db_record[20] = $contact['CONTACTID'];
  // MOBILE
  $db_record[21] = mb_convert_encoding($contact['MOBILE'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // E_MAIL
  $db_record[22] = mb_convert_encoding($contact['E_MAIL'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // TITLE
  // FINDERID
  $db_record[24] = mb_convert_encoding($contact['FINDERID'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                             'UTF-8');
  // POST

  if (!dbase_add_record($db_contacts, $db_record))
  {
   // var_dump($db_record);
   dbase_close($db_contacts);
   die('failed to dbase_add_record(), aborting' . PHP_EOL);
  }
  $success = TRUE;
  break;
 }
 case 'd':
 {
  if ($sub_mode === 'unlink')
  {
   $success = TRUE;
   break;
  }

  for ($i = 1; $i <= $num_contacts_records; $i++)
  {
   $db_record = dbase_get_record_with_names($db_contacts, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db_contacts);
    die('failed to dbase_get_record_with_names(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   if (($db_record['deleted'] == 1) ||
       ($db_record['CONTACTID'] != $contact['CONTACTID'])) continue;

   if (!dbase_delete_record($db_contacts, $i))
   {
    // var_dump($db_record);
        dbase_close($db_contacts);
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
  if ($sub_mode === 'link')
  {
   $success = TRUE;
   break;
  }

  for ($i = 1; $i <= $num_contacts_records; $i++)
  {
   $db_record = dbase_get_record($db_contacts, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db_contacts);
    die('failed to dbase_get_record(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   if ($db_record['deleted'] == 1) continue;
   if ($db_record[20] != $contact['CONTACTID']) continue;

   // GROUP
   $db_record[0] = mb_convert_encoding($contact['GROUP'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
   // COMPANY
   $db_record[1] = mb_convert_encoding($contact['COMPANY'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
   // STREET
   $db_record[2] = mb_convert_encoding($contact['STREET'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                              'UTF-8');
   // CITY
   $db_record[3] = mb_convert_encoding($contact['CITY'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                            'UTF-8');
   // TEL
   $db_record[4] = mb_convert_encoding($contact['TEL'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                              'UTF-8');
   // FAX
   $db_record[5] = mb_convert_encoding($contact['FAX'],
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                              'UTF-8');
   // REGISTERED
   // $db_record[6] = (empty($contact['REGISTERED']) ? date('Ymd', time())
                                                  // : $contact['REGISTERED']);
   // N_MODIFIED/_MODIFIED
   $db_record[7] = date('Ymd', time());
   // STATUS
   // ZIP
   $db_record[9] = mb_convert_encoding(strval($contact['ZIP']),
                                                                              $options['geo_db']['db_contacts_cp'],
                                                                              mb_internal_encoding());
   // COUNTRY
   $db_record[10] = mb_convert_encoding($contact['COUNTRY'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                              'UTF-8');
   // FIRSTNAME
   $db_record[11] = mb_convert_encoding($contact['FIRSTNAME'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                                'UTF-8');
   // LASTNAME
   $db_record[12] = mb_convert_encoding($contact['LASTNAME'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                              'UTF-8');
   // MORENAMES
   // DEPARTMENT
   $db_record[14] = mb_convert_encoding($contact['DEPARTMENT'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                               'UTF-8');
   // SEX
   // STYLE
   // JOBTITLE
   $db_record[17] = mb_convert_encoding($contact['JOBTITLE'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                                'UTF-8');
   // COMMENT
   $db_record[18] = mb_convert_encoding($contact['COMMENT'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                                'UTF-8');
   // COLLECTION
   // CONTACTID
   // $db_record[20] = $contact['CONTACTID'];
   // MOBILE
   $db_record[21] = mb_convert_encoding($contact['MOBILE'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                                'UTF-8');
   // E_MAIL
   $db_record[22] = mb_convert_encoding($contact['E_MAIL'],
                                                                                $options['geo_db']['db_contacts_cp'],
                                                                                'UTF-8');
   // TITLE
   // FINDERID
   // $db_record[24] = mb_convert_encoding($contact['FINDERID'],
                      // $options['geo_db']['db_contacts_cp'],
                      // 'UTF-8');
   // POST

   unset($db_record['deleted']);
   if (!dbase_replace_record($db_contacts, $db_record, $i))
   {
    // var_dump($db_record);
        dbase_close($db_contacts);
    die('failed to dbase_replace_record(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   $success = TRUE;
   break;
  }
  break;
 }
 default:
  dbase_close($db_contacts);
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
if (dbase_close($db_contacts) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
//$firephp->log('closed contacts db...');

if (($success === TRUE) &&
    ((($mode === 'c') && ($sub_mode === 'link')) ||
     ($mode === 'd') ||
   (($mode === 'u') && ($sub_mode === 'link'))))
{
 $success = FALSE;
 $db_relation = dbase_open($db_relation_file, 2);
 if ($db_relation === FALSE) die('failed to dbase_open("' .
                                                                  $db_relation_file .
                                                                  '", 2), aborting' . PHP_EOL);
 //$firephp->log('opened relation db...');
 $num_relation_records = dbase_numrecords($db_relation);
 if ($num_relation_records === FALSE)
 {
  dbase_close($db_relation);
  die('failed to dbase_numrecords(), aborting' . PHP_EOL);
 }
// $firephp->log($num_relation_records, '#records (relation)');

 switch ($mode)
 {
  case 'c':
  case 'u':
   // sanity check: validate that this record does not exist yet...
   for ($i = 1; $i <= $num_relation_records; $i++)
   {
    $db_record = dbase_get_record($db_relation, $i);
    if ($db_record === FALSE)
    {
     dbase_close($db_relation);
     die('failed to dbase_get_record(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
    if ($db_record['deleted'] == 1) continue;
    if (((intval(trim($db_record['CONTACTID'])) == $contact['CONTACTID']) &&
                  (intval(trim($db_record['SITEID']))    == $site)))
    {
//     $firephp->log('record exists (contact: ' .
  //                                    $contact['CONTACTID'] .
    //                                  ' , site: ' .
      //                                $site .
        //                              '), continuing');
     // $conflict = TRUE;
     $success = TRUE;
     break 2;
    }
   }

   $field_info = dbase_get_header_info($db_relation);
   if ($field_info === FALSE)
   {
    dbase_close($db_relation);
    die('failed to dbase_get_header_info(), aborting' . PHP_EOL);
   }
   //$firephp->log($field_info, 'field info');
   //print_r($field_info);

   $db_record = dbase_get_record($db_relation, 1);
   if ($db_record === FALSE)
   {
    dbase_close($db_relation);
    die('failed to dbase_get_record(1), aborting' . PHP_EOL);
   }
   unset($db_record['deleted']);

   // $firephp->log($db_record, 'record');
   foreach ($db_record as $key => $value)
    switch ($field_info[$key]['type'])
    {
          case 'boolean':
          $db_record[$key] = FALSE;
          break;
        case 'character':
            $db_record[$key] = '';
          break;
         case 'date':
            $db_record[$key] = date('Ymd', 0);
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
                    '), aborting' . PHP_EOL);
           break;
    }

   // CONTACTID
   $db_record[0] = strval($contact['CONTACTID']);
   // SITEID
   $db_record[1] = strval($site);
   // RELATION
   $db_record[2] = '';
   // _MODIFIED
   $db_record[3] = date('Ymd', time());
   // _UNIQUE
   $db_record[4] = $db_record[0] . ',' . $db_record[1];

   if (!dbase_add_record($db_relation, $db_record))
   {
    // var_dump($db_record);
    dbase_close($db_relation);
    die('failed to dbase_add_record(), aborting' . PHP_EOL);
   }
   $success = TRUE;
   break;
  case 'd':
   for ($i = 1; $i <= $num_relation_records; $i++)
   {
    $db_record = dbase_get_record_with_names($db_relation, $i);
    if ($db_record === FALSE)
    {
     dbase_close($db_relation);
     die('failed to dbase_get_record_with_names(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
    if (($db_record['deleted'] == 1) ||
        ((intval(trim($db_record['CONTACTID'])) != $contact['CONTACTID']) &&
                 (intval(trim($db_record['SITEID']))    != $site))) continue;

    if (!dbase_delete_record($db_relation, $i))
    {
     // var_dump($db_record);
          dbase_close($db_relation);
     die('failed to dbase_delete_record(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
   }
   $success = TRUE;
   break;
  default:
   die('invalid mode/submode (was: "' . $mode . '"/"' . $sub_mode . '"), aborting' . PHP_EOL);
 }

 if (dbase_close($db_relation) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
 //$firephp->log('closed relation db...');
}

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
//   http_response_code(($conflict ? 409 : 500)); // == 'Conflict' : 'Internal Server Error'
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
 die('failed to edit contact (CTID was: ' . $contact['CONTACTID'] . '), aborting' . PHP_EOL);
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
