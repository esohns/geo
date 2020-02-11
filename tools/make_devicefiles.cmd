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
if %1 EQU +%1 (
 set get_yields=%1
 goto :EOF
)
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
set devices=TomTom Garmin
set toursets=New Standard
:: step0: pre-step0: set: (default) tourset(s), ...
for %%x in (%locations%) do call :for_2 %%x
goto Done2
:for_2
echo processing "%1"...
if "%1" EQU "b" (
 set toursets=2010 2011 063112 "2008 Jan" "JAN 05" Kombi Import Okt07 SEP07 Standard TourAlt
 goto Continue2
)
if "%1" EQU "bw" (
 set toursets=2006Q2 NEW
 goto Continue2
)
if "%1" EQU "d" (
 set toursets=Standard
 goto Continue2
)
if "%1" EQU "hh" (
 set toursets=Standard 2009 2010
 goto Continue2
)
if "%1" EQU "ks" (
 goto Continue2
)
if "%1" EQU "mh" (
 set toursets=New
 goto Continue2
)
if "%1" EQU "th" (
 set toursets=New
 goto Continue2
)
if "%1" EQU "wf" (
 set toursets=New
 goto Continue2
)
if "%1" EQU "nrw" (
 set toursets=New
 goto Continue2
)
if "%1" EQU "test" (
 set toursets=New
 goto Continue2
)
echo invalid location (was: "%1")
goto Failed2
:Continue2
echo location "%1" --^> toursets          : "!toursets!"
set std_out_log=%cd%\data\%1\%std_out%
set std_err_log=%cd%\data\%1\%std_err%
for %%y in (%toursets%) do call :for_3 %%x %%y
goto Done3
:for_3
echo processing tourset "%2"...
for %%z in (%devices%) do call :for_4 %%x %%y %%z
goto Done4
:for_4
echo processing device "%3"...
set cmd_line=%php_exe% .\make_devicefile.php -f%3 -l%1 -t%2
::!cmd_line! >NUL 2>%cd%\data\%1\error.log
::echo "!cmd_line!"...
cmd.exe /c !cmd_line! >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing device "%3"^, aborting
 set RC=%ERRORLEVEL%
 goto Failed4
)
goto :EOF
:Failed4
echo processing device "%3"...FAILED
goto :EOF
:Done4
echo processing tourset "%2"...DONE
goto :EOF
:Done3
echo processing "%1"...DONE
goto :EOF
:Failed2
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
