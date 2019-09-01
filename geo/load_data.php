<?php
error_reporting(E_ALL);
//require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting\n");

if (!$is_cli)
{
 // init output buffering
 if (!ob_start()) die("failed to ob_start(), aborting");

// $firephp = FirePHP::getInstance(TRUE);
// if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
// $firephp->setEnabled(FALSE);
// $firephp->log('started script...');

 // set default header
 header('', TRUE, 500); // == 'Internal Server Error'
}

$location = '';
$mode = 'site';
$ids = NULL;
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['mode'])) $mode = $_GET['mode'];
 if (isset($_GET['ids']))
 {
  $ids = json_decode($_GET['ids'], TRUE);
  if ($ids === NULL) die("failed to json_decode() ids, aborting");
 }
}
else
{
 if (($argc < 3) || ($argc > 3)) die('usage: ' . basename($argv[0]) . ' <location> <mode[contact|site]> <ID>');
 $location = $argv[1];
 $mode = $argv[2];
 $ids = array();
 $ids[] = intval($argv[3]);
}

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die("invalid file (was: \"$ini_file\"), aborting\n");
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
                                      'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die("failed to parse init file (was: \"$ini_file\"), aborting\n");
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (empty($ids)) die('invalid id(s) parameter (was: "' . ($is_cli ? $argv[3] : $_GET['ids']) . '"), aborting');
if (count($options) == 0) die("failed to parse init file (was: \"$ini_file\"), aborting");
$site_id_is_string = FALSE;
$db_file = '';
$db_relation_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                                : $options[$os_section]['db_base_dir']) .
                    DIRECTORY_SEPARATOR .
                (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                 : '') .
                (isset($options[$loc_section]['db_relation_dbf']) ? $options[$loc_section]['db_relation_dbf']
                                            : $options['geo_db']['db_relation_dbf']);
switch ($mode)
{
 case 'contact':
  $db_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                         : $options[$os_section]['db_base_dir']) .
             DIRECTORY_SEPARATOR .
         (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                          : '') .
         (isset($options[$loc_section]['db_contacts_dbf']) ? $options[$loc_section]['db_contacts_dbf']
                                         : $options['geo_db']['db_contacts_dbf']);
  break;
 case 'container':
  $db_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                         : $options[$os_section]['db_base_dir']) .
             DIRECTORY_SEPARATOR .
         (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                          : '') .
         (isset($options[$loc_section]['db_containers_dbf']) ? $options[$loc_section]['db_containers_dbf']
                                       : $options['geo_db']['db_containers_dbf']);
  break;
 case 'site':
  $db_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                         : $options[$os_section]['db_base_dir']) .
             DIRECTORY_SEPARATOR .
         (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                          : '') .
         (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
                                    : $options['geo_db']['db_sites_dbf']);
  $site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                        (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
  break;
 default:
  die('invalid mode parameter (was: "' . $mode . '"), aborting');
}
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_file)) die("\"$db_file\" not readable, aborting");
if (!file_exists($db_relation_file)) die("db file does not exist (was: \"$db_relation_file\"), aborting");
if (!file_exists($db_file)) die("db file does not exist (was: \"$db_file\"), aborting");
if (!$is_cli)
// $firephp->log($db_file, 'relation file')
;
else fprintf(STDERR, 'relation file: "' . $db_relation_file . "\"\n");
if (!$is_cli)
// $firephp->log($db_file, 'database file')
;
else fprintf(STDERR, 'database file: "' . $db_file . "\"\n");

$status_active_string_db = mb_convert_encoding($options[$loc_section]['db_sites_status_active_desc'],
                                               $options['geo_db']['db_sites_cp'],
                                               'CP1252');
$status_ex_string_db = mb_convert_encoding($options[$loc_section]['db_sites_status_ex_desc'],
                                           $options['geo_db']['db_sites_cp'],
                                           'CP1252');
$status_active_string_utf8 = mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'],
                                                 'UTF-8',
                                                 'CP1252');
$status_ex_string_utf8 = mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'],
                                             'UTF-8',
                                             'CP1252');

// init dBase
// *NOTE*: open DB read-only
$db = dbase_open($db_file, 0);
if ($db === FALSE) die("failed to dbase_open(), aborting");
// $field_info = dbase_get_header_info($db);
// if ($field_info === FALSE)
// {
 // dbase_close($db);
 // die("failed to dbase_get_header_info(), aborting");
// }
// print_r($field_info);
if (!$is_cli)
// $firephp->log('opened db...')
;
else fprintf(STDERR, "opened db...\n");
$num_records = dbase_numrecords($db);
if ($num_records === FALSE)
{
 dbase_close($db);
 die("failed to dbase_numrecords(), aborting");
}
if (!$is_cli)
// $firephp->log($num_records, '#records')
;
else fprintf(STDERR, '#records: ' . $num_records . "\n");

$db_relation = -1;
if ($mode == 'site')
{
 // *NOTE*: open DB read-only
 $db_relation = dbase_open($db_relation_file, 0);
 if ($db_relation === FALSE) die("failed to dbase_open(), aborting");
 // $field_info = dbase_get_header_info($db);
 // if ($field_info === FALSE)
 // {
 // dbase_close($db_relation);
 // die("failed to dbase_get_header_info(), aborting");
 // }
 // print_r($field_info);
 if (!$is_cli)
// $firephp->log('opened relation db...')
;
 else fprintf(STDERR, "opened relation db...\n");
 $num_relation_records = dbase_numrecords($db_relation);
 if ($num_relation_records === FALSE)
 {
  dbase_close($db_relation);
  dbase_close($db);
  die("failed to dbase_numrecords(), aborting");
 }
 if (!$is_cli)
// $firephp->log($num_records, '# relation records')
;
 else fprintf(STDERR, '# relation records: ' . $num_relation_records . "\n");
}

$not_found = FALSE;
$data_records = array();
for ($i = 0; $i < count($ids); $i++)
{
 $j = 1;
 $db_relation_record = array();
 for (; $j <= $num_records; $j++)
 {
  $db_record = dbase_get_record_with_names($db, $j);
  if ($db_record === FALSE)
  {
   if ($mode == 'site') dbase_close($db_relation);
   dbase_close($db);
   die("failed to dbase_get_record_with_names($j), aborting");
  }
  if ($db_record['deleted'] == 1) continue;

  if ($mode == 'site')
  {
   $site_id = ($site_id_is_string ? intval(mb_convert_encoding(trim($db_record['SITEID']),
                                                               mb_internal_encoding(),
                                                   $options['geo_db']['db_sites_cp']))
                  : $db_record['SITEID']);
   if ($site_id != $ids[$i]) continue;

   $db_record['CONTACTID'] = -1;
   $k = 1;
   for (; $k <= $num_relation_records; $k++)
   {
    $db_relation_record = dbase_get_record_with_names($db_relation, $k);
    if ($db_relation_record === FALSE)
    {
     dbase_close($db_relation);
     dbase_close($db);
     die("failed to dbase_get_record_with_names($k), aborting");
    }
    if ($db_relation_record['deleted'] == 1) continue;
    $site_id_2 = intval(trim($db_relation_record['SITEID']));
    if ($site_id != $site_id_2) continue;

  $db_record['CONTACTID'] = intval(trim($db_relation_record['CONTACTID']));
  break;
   }
  }
  elseif (($mode == 'contact') &&
          ($db_record['CONTACTID'] != $ids[$i])) continue;
  elseif ($mode == 'container')
  {
   $cont_id = mb_convert_encoding(trim($db_record['CONTID']),
                                  'UTF-8',
                      $options['geo_db']['db_containers_cp']);
   if (strcmp($cont_id, $ids[$i]) != 0) continue;
  }

  break;
 }
 if ($j == ($num_records + 1))
 {
  if (!$is_cli)
// $firephp->log('record not found, continuing', $i)
;
  $not_found = TRUE;
  continue;
 }

 unset($db_record['deleted']);
 switch ($mode)
 {
  case 'contact':
   // drop some data
   unset($db_record['N_MODIFIED'], $db_record['_MODIFIED'], $db_record['STATUS'], $db_record['MORENAMES'],
       $db_record['SEX'], $db_record['STYLE'], $db_record['COLLECTION'], $db_record['TITLE'],
     $db_record['POST']);
   // convert string encodings, add record data
   // GROUP
   $db_record['GROUP'] = mb_convert_encoding(trim($db_record['GROUP']),
                                             'UTF-8',
                                 $options['geo_db']['db_contacts_cp']);
   // COMPANY
   $db_record['COMPANY'] = mb_convert_encoding(trim($db_record['COMPANY']),
                                               'UTF-8',
                               $options['geo_db']['db_contacts_cp']);
   // STREET
   $db_record['STREET'] = mb_convert_encoding(trim($db_record['STREET']),
                                              'UTF-8',
                              $options['geo_db']['db_contacts_cp']);
   // CITY
   $db_record['CITY'] = mb_convert_encoding(trim($db_record['CITY']),
                                            'UTF-8',
                            $options['geo_db']['db_contacts_cp']);
   // TEL
   $db_record['TEL'] = mb_convert_encoding(trim($db_record['TEL']),
                                           'UTF-8',
                               $options['geo_db']['db_contacts_cp']);
   // FAX
   $db_record['FAX'] = mb_convert_encoding(trim($db_record['FAX']),
                                           'UTF-8',
                             $options['geo_db']['db_contacts_cp']);
   // REGISTERED
   $db_record['REGISTERED'] = trim($db_record['REGISTERED']);
   // N_MODIFIED/_MODIFIED
   // STATUS
   // ZIP
   $db_record['ZIP'] = intval($db_record['ZIP']);
   // COUNTRY
   $db_record['COUNTRY'] = mb_convert_encoding(trim($db_record['COUNTRY']),
                         'UTF-8',
                         $options['geo_db']['db_contacts_cp']);
   // FIRSTNAME
   $db_record['FIRSTNAME'] = mb_convert_encoding(trim($db_record['FIRSTNAME']),
                         'UTF-8',
                         $options['geo_db']['db_contacts_cp']);
   // LASTNAME
   $db_record['LASTNAME'] = mb_convert_encoding(trim($db_record['LASTNAME']),
                          'UTF-8',
                        $options['geo_db']['db_contacts_cp']);
   // MORENAMES
   // DEPARTMENT
   $db_record['DEPARTMENT'] = mb_convert_encoding(trim($db_record['DEPARTMENT']),
                              'UTF-8',
                          $options['geo_db']['db_contacts_cp']);
   // SEX
   // STYLE
   // JOBTITLE
   $db_record['JOBTITLE'] = mb_convert_encoding(trim($db_record['JOBTITLE']),
                          'UTF-8',
                        $options['geo_db']['db_contacts_cp']);
   // COMMENT
   $db_record['COMMENT'] = mb_convert_encoding(trim($db_record['COMMENT']),
                         'UTF-8',
                         $options['geo_db']['db_contacts_cp']);
   // COLLECTION
   // CONTACTID
   $db_record['CONTACTID'] = intval($db_record['CONTACTID']);
   // MOBILE
   $db_record['MOBILE'] = mb_convert_encoding(trim($db_record['MOBILE']),
                                              'UTF-8',
                                $options['geo_db']['db_contacts_cp']);
   // E_MAIL
   $db_record['E_MAIL'] = mb_convert_encoding(trim($db_record['E_MAIL']),
                            'UTF-8',
                        $options['geo_db']['db_contacts_cp']);
   // TITLE
   // FINDERID
   $db_record['FINDERID'] = mb_convert_encoding(trim($db_record['FINDERID']),
                            'UTF-8',
                          $options['geo_db']['db_contacts_cp']);
   // POST
   break;
  case 'container':
   // drop some data
   unset($db_record['_MODIFIED'], $db_record['COPY_CONTI'], $db_record['COPY2_CONT'],
       $db_record['COPY3_CONT']);

   // convert string encodings, add record data
   // CONTID
   $db_record['CONTID'] = mb_convert_encoding(trim($db_record['CONTID']),
                                              'UTF-8',
                                  $options['geo_db']['db_containers_cp']);
   // CONTTYPE
   $db_record['CONTTYPE'] = mb_convert_encoding(trim($db_record['CONTTYPE']),
                                                'UTF-8',
                                $options['geo_db']['db_containers_cp']);
   // ACQUIRED
   $db_record['ACQUIRED'] = trim($db_record['ACQUIRED']);
   // STATUS
   $db_record['STATUS'] = mb_convert_encoding(trim($db_record['STATUS']),
                                              'UTF-8',
                              $options['geo_db']['db_containers_cp']);
   // LASTREPAIR
   $db_record['LASTREPAIR'] = trim($db_record['LASTREPAIR']);
   // LOCKTYPE
   $db_record['LOCKTYPE'] = mb_convert_encoding(trim($db_record['LOCKTYPE']),
                                                'UTF-8',
                                  $options['geo_db']['db_containers_cp']);
   // SERIALNR
   // $db_record['SERIALNR'] = ((strlen(trim($db_record['SERIALNR'])) == 0) ? -1
                                                               // : intval(trim($db_record['SERIALNR'])));
   $db_record['SERIALNR'] = mb_convert_encoding(trim($db_record['SERIALNR']),
                                                'UTF-8',
                                  $options['geo_db']['db_containers_cp']);
   // _MODIFIED
   // COMMENT
   $db_record['COMMENT'] = mb_convert_encoding(trim($db_record['COMMENT']),
                                               'UTF-8',
                                 $options['geo_db']['db_containers_cp']);
   // PRODUCED
   $db_record['PRODUCED'] = trim($db_record['PRODUCED']);
   // COPY_CONTI
   // COPY2_CONT
   // COPY3_CONT
   break;
  case 'site':
   // drop some data
   unset($db_record['_CURRENTYR'], $db_record['_MODIFIED'], $db_record['_TOURSET'],
         $db_record['_TOURSET2'], $db_record['AREAID'], $db_record['COLLECTION'], $db_record['COMNT_DRV'],
       $db_record['COSTFROM'], $db_record['COSTPERYEA'], $db_record['COSTTO'], $db_record['MAP'],
     $db_record['PERM_REMIN'], $db_record['PERMISSION'], $db_record['SITE'], $db_record['CONT_AUF'],
       $db_record['CONT_AB']);
   // convert string encodings, add record data
   //_CURRENTYR
   //_MODIFIED
   //_TOURSET
   //_TOURSET2
   //AREAID
   $db_record['CITY'] = mb_convert_encoding(trim($db_record['CITY']),
                                            'UTF-8',
                        $options['geo_db']['db_sites_cp']);
   //COLLECTION
   $db_record['COMMUNITY'] = mb_convert_encoding(trim($db_record['COMMUNITY']),
                                                 'UTF-8',
                                 $options['geo_db']['db_sites_cp']);
   //COMNT_DRV
   $db_record['COMNT_SITE'] = mb_convert_encoding(trim($db_record['COMNT_SITE']),
                                                  'UTF-8',
                                  $options['geo_db']['db_sites_cp']);
   $db_record['CONTID'] = mb_convert_encoding(trim($db_record['CONTID']),
                                              'UTF-8',
                              $options['geo_db']['db_sites_cp']);
   $db_record['CONTRACTID'] = mb_convert_encoding(trim($db_record['CONTRACTID']),
                                                  'UTF-8',
                                  $options['geo_db']['db_sites_cp']);
   //COSTFROM
   //COSTPERYEA
   //COSTTO
   $db_record['FINDDATE'] = trim($db_record['FINDDATE']);
   $db_record['FINDERID'] = mb_convert_encoding(trim($db_record['FINDERID']),
                                                'UTF-8',
                                  $options['geo_db']['db_sites_cp']);
   $db_record['GROUP'] = mb_convert_encoding(trim($db_record['GROUP']),
                                             'UTF-8',
                             $options['geo_db']['db_sites_cp']);
   //MAP
   $db_record['PERM_FROM'] = trim($db_record['PERM_FROM']);
   //PERM_REMIN
   $db_record['PERM_TO'] = trim($db_record['PERM_TO']);
   //PERMISSION
   //SITE
   //SITEID
   $is_active = (strcmp(trim($db_record['STATUS']), $status_active_string_db) == 0);
   $is_ex = (strcmp(trim($db_record['STATUS']), $status_ex_string_db) == 0);
   $db_record['STATUS'] = ($is_active ? $status_active_string_utf8
                                      : ($is_ex ? $status_ex_string_utf8
                              : mb_convert_encoding(trim($db_record['STATUS']),
                                                                      'UTF-8',
                                                      $options['geo_db']['db_sites_cp'])));
   $db_record['STREET'] = mb_convert_encoding(trim($db_record['STREET']),
                                              'UTF-8',
                              $options['geo_db']['db_sites_cp']);
   //ZIP
   //CONT_AUF
   //CONT_AB
   //LON
   //LAT
   break;
  default:
   dbase_close($db);
   die('invalid mode parameter (was: "' . $mode . '"), aborting');
 }

 $data_records[] = $db_record;
}
if (dbase_close($db) === FALSE)
{
 if ($mode == 'site') dbase_close($db_relation);
 die("failed to dbase_close(), aborting\n");
}
if (!$is_cli)
// $firephp->log('closed db...')
;
else fprintf(STDERR, "closed db...\n");
if ($mode == 'site') if (dbase_close($db_relation) === FALSE) die("failed to dbase_close(), aborting\n");
if (!$is_cli)
// $firephp->log('closed relation db...')
;
else fprintf(STDERR, "closed relation db...\n");

$json_content = json_encode($data_records);
if ($json_content === FALSE) die("failed to json_encode(\"$data_records\"): " . json_last_error() . ", aborting\n");
// var_dump($json_content);
// if (!$is_cli) $firephp->log($json_content, 'response');

if (!$is_cli)
{
 if ($not_found)
 {
//   http_response_code(404); // == 'Not Found'
  header('', TRUE, 404); // == 'Not Found'
 }
 else header('', TRUE, 200); // == 'OK'
}

// send the content back
echo("$json_content");

if (!$is_cli)
{
 // fini output buffering
 if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
}
?>
