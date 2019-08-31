<?php
/*******************************************************************************
*  Title: PHP hit counter (PHPcount)
*  Version: 1.4 @ March 3, 2012
*  Author: Klemen Stirn
*  Website: http://www.phpjunkyard.com
********************************************************************************
*  COPYRIGHT NOTICE
*  Copyright 2004-2012 Klemen Stirn. All Rights Reserved.
*
*  This script may be used and modified free of charge by anyone
*  AS LONG AS COPYRIGHT NOTICES AND ALL THE COMMENTS REMAIN INTACT.
*  By using this code you agree to indemnify Klemen Stirn from any
*  liability that might arise from it's use.
*
*  Selling the code for this program, in part or full, without prior
*  written consent is expressly forbidden.
*
*  Obtain permission before redistributing this software over the Internet
*  or in any other medium. In all cases copyright and header must remain
*  intact. This Copyright is in full effect in any country that has
*  International Trade Agreements with the United States of America or
*  with the European Union.
********************************************************************************
*
*  ACKNOWLEDGEMENT
*
*  Please support future script development by linking to us:
*  http://www.phpjunkyard.com/about/link2us.php
*
*  Or by sending a small donation:
*  http://www.phpjunkyard.com/about/donate.php
*
*******************************************************************************/

// SETUP YOUR COUNTER
// See file readme.html for detailed instructions

// Count unique visitors? 1 = YES, 0 = NO
$count_unique = 0;

// Number of hours a visitor is considered as "unique"
$unique_hours = 24;

// Minimum number of digits shown (zero-padding). Set to 0 to disable.
$min_digits = 0;

// * EDIT */
$cwd = getcwd();
if ($cwd === FALSE) die('ERROR: failed to getcwd(), aborting');

#############################
#     DO NOT EDIT BELOW     #
#############################

/* Turn error notices off */
error_reporting(E_ALL ^ E_NOTICE);

/* Tell browsers not to cache the file output so we can count all hits */
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* Is page ID set? */
if ( ! isset($_GET['page']) )
{
	die('ERROR: The counter.php file must be called with a <b>?page=PAGEID</b> parameter, for example <b>counter.php?page=test</b>');
}

/* Remove any illegal chars from the page ID */
$page = preg_replace('/[^a-zA-Z0-9\-_\.]/','',$_GET['page']);

/* Stop if $page is not valid */
if ( ! strlen($page) )
{
	die('ERROR: Page ID is missing or contains only invalid chars. Please use only these chars for the page ID: a-z, A-Z, 0-9, &quot;.&quot;, &quot;-&quot; and &quot;_&quot;');
}

/* Set values for cookie and log file names */
$cname   = 'tcount_unique_'.$page;
$logfile = $cwd . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $page . '.txt';

/* Does the log file exist? */
// * EDIT */
if ( ! file_exists($logfile) )
{
	die("ERROR: Log file \"$logfile\" not found. Make sure there is a file called <b>" . $page . '.txt</b> inside your <b>logs</b> folder. On most servers file names are CaSe SeNSiTiVe!');
}

/* Open log file for reading and writing */
if ($fp = @fopen($logfile, 'r+'))
{
	/* Lock log file from other scripts */
	$locked = flock($fp, LOCK_EX);

	/* Lock successful? */
	if ($locked)
	{
    	/* Let's read current count */
		$filesize = filesize($logfile);
		if ($filesize === FALSE) die("ERROR: Log file \"$logfile\" size is invalid. Make sure there is a file called <b>" . $page . '.txt</b> inside your <b>logs</b> folder. On most servers file names are CaSe SeNSiTiVe!');
		$count = (($filesize === 0) ? 0 : intval( trim( fread($fp, $filesize ) ) ));

        /* If counting unique hits is enabled make sure it's a unique hit */
        if ( $count_unique == 0 || ! isset($_COOKIE[$cname]) )
        {
        	/* Update count by 1 and write the new value to the log file */
			$count = $count + 1;
			rewind($fp);
			fwrite($fp, $count);

			/* Print the Cookie and P3P compact privacy policy */
			header('P3P: CP="NOI NID"');
			setcookie($cname, 1, time()+60*60*$unique_hours);
        }
	}
	else
	{
    	/* Lock not successful. Better to ignore than to damage the log file */
		$count = 1;
	}

	/* Release file lock and close file handle */
	flock($fp, LOCK_UN);
	fclose($fp);
}
else
{
	die("ERROR: Can't read/write to the log file ($logfile). Make sure this file is writable by PHP scripts. On UNIX servers, CHMOD the log file to 666 (rw-rw-rw-).");
}

/* Is zero-padding enabled? If yes, add zeros if required */
if ($min_digits)
{
	$count = sprintf('%0'.$min_digits.'s', $count);
}

/* Print out Javascript code and exit */
// * EDIT */
if (isset($_GET['echo']) && (strcmp(strtoupper($_GET['echo']), 'TRUE') === 0))
 echo 'document.write(\''.$count.'\');';
exit();
?>
