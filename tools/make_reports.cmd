@echo off
set RC=0
setlocal enabledelayedexpansion
pushd . >NUL 2>&1

set running_file=running.pid
if exist %running_file% (
 echo already processing^, exiting
 set RC=1
 goto Clean_Up
)
copy /Y NUL %running_file% >NUL

set php_exe=C:\PHP\php.exe
set std_out=output.log
set std_err=error.log
:: set locations="b bw nrw"
set locations_default=b bw d hh ks mh th wf nrw
set locations=%locations_default%
set argc=0
if "%*" EQU "" goto Done1
for %%x in (%*) do set /A argc+=1
if %argc% EQU 0 goto Done1
set locations=
for %%x in (%*) do call :for_1 %%x
goto Done1
:for_1
if "%locations%."=="." (
 set locations=%1
 goto :EOF
)
set locations=%locations% %1
goto :EOF
:Done1

if "%locations%."=="." set locations=%locations_default%

:: sanity check(s)
if NOT exist %php_exe% (
 echo PHP runtime not found^, exiting
 set RC=1
 goto Clean_Up_2
)

:: work around unsupported UNC paths...
set unc_dir=\\Rechner1\Coffee
::net use %unc_dir% /user:Rechner1\nobody nobody >NUL
net use %unc_dir% /user:Rechner1\www www >>%cd%\data\%std_err% 2>>&1
if %ERRORLEVEL% NEQ 0 (
 echo failed to map database directory "%unc_dir%"^, exiting
 set RC=%ERRORLEVEL%
 goto Clean_Up_2
)
::pushd %db_dir% 2>NUL
::set db_dir=%cd%
::cd /D %~dp0..
echo mapped database directory "%unc_dir%"...

set tools_dir=.\tools
:: step0: pre-step0: set: (default) tourset(s), ...
for %%x in (%locations%) do call :for_2 %%x
goto Done2
:for_2
echo processing "%1"...
if "%1" EQU "b" (
 goto Continue
)
if "%1" EQU "bw" (
 goto Continue
)
if "%1" EQU "d" (
 goto Continue
)
if "%1" EQU "hh" (
 goto Continue
)
if "%1" EQU "ks" (
 goto Continue
)
if "%1" EQU "mh" (
 goto Continue
)
if "%1" EQU "th" (
 goto Continue
)
if "%1" EQU "wf" (
 goto Continue
)
if "%1" EQU "nrw" (
 goto Continue
)
if "%1" EQU "test" (
 goto Continue
)
echo invalid location (was: "%1")
goto Failed
:Continue
set std_out_log=%cd%\data\%1\%std_out%
set std_err_log=%cd%\data\%1\%std_err%

echo processing area IDs ^(DBF^)...
:: step1: area --> DBF
set cmd_line=%php_exe% %tools_dir%\update_site_areaIDs.php %1
::!cmd_line! >NUL 2>&1
::echo "!cmd_line!"...
cmd.exe /c !cmd_line! >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing area IDs ^(DBF^)
 goto Failed
)
echo processing processing area IDs ^(DBF^)...DONE

echo generating report ^(ODS^)...
:: step2: yields --> ODS
set cmd_line=%php_exe% .\make_report.php -- -l%1
::!cmd_line! >NUL 2>&1
::echo "!cmd_line!"...
cmd.exe /c !cmd_line! >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed generating report ^(ODS^)
 set RC=%ERRORLEVEL%
 goto Failed
)
echo generating report ^(ODS^)...DONE

echo processing "%1"...DONE
goto :EOF
:Failed
echo processing "%1"...FAILED
goto :EOF
:Done2

:: undo UNC workaround
net use %unc_dir% /DELETE >>%cd%\data\error.log 2>>&1
if %ERRORLEVEL% NEQ 0 (
 echo failed to unmap database directory^, continuing
 set RC=%ERRORLEVEL%
)
::net use %temp_drive% /d >NUL

:Clean_Up_2
del %running_file% >NUL
if %ERRORLEVEL% NEQ 0 (
 set RC=%ERRORLEVEL%
)

:Clean_Up
popd
if %ERRORLEVEL% NEQ 0 (
 set RC=%ERRORLEVEL%
)
::endlocal & set RC=%ERRORLEVEL%
endlocal & set RC=%RC%
goto Error_Level

:Exit_Code
:: echo %ERRORLEVEL% %1 *WORKAROUND*
exit /b %1

:Error_Level
call :Exit_Code %RC%
