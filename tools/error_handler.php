<?php
function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
 global $is_cli/*, $firephp*/;

 if ($is_cli) fwrite(STDERR, $errfile . '[' . strval($errline) . ']: "' . $errstr . "\"\n");
 else
  //$firephp->log($errfile . '[' . strval($errline) . ']: "' .$errstr . '"')
  ;

 if ($errno !== 0) die($errno);

 return FALSE;
}
?>
