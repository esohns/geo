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

set GEO_INI_FILE=%cd%\..\common\geo_php.ini
if NOT exist %GEO_INI_FILE% (
 echo ini file not found^, exiting
 set RC=1
 goto Clean_Up_2
)

set php_exe=C:\PHP\php.exe
set refresh_only=0
set std_out=output.log
set std_err=error.log
:: set locations_default="bw nrw"
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
 set refresh_only=%1
 echo refreshing...
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
::set temp_drive=K:
set unc_dir=C:\Coffee
REM set unc_dir=\\Rechner1\Coffee
REM net use %unc_dir% /user:Rechner1\www www >>%cd%\data\%std_err% 2>>&1
::net use %temp_drive% %unc_dir% /user:Rechner1\www www >NUL 2>%cd%\data\%std_err%
if !ERRORLEVEL! NEQ 0 (
 echo failed to map database directory "%unc_dir%"^, exiting
 set RC=!ERRORLEVEL!
 goto Clean_Up_2
)
::pushd %unc_dir% 2>NUL
::set temp_drive=%cd%
::cd /D %~dp0..
echo mapped database directory "%unc_dir%"...
::echo mapped database directory "%unc_dir%" to %temp_drive%...

set tools_dir=.\tools
for %%x in (%locations%) do call :for_2 %%x
goto Done2
:for_2
echo processing "%1"...
rem if NOT exist %cd%\data\%1\NUL goto NotExist
if NOT exist "%cd%\data\%1" (
 echo invalid directory ^(was: "%cd%\data\%1"^)^, exiting
 set RC=1
 goto :EOF
)
set std_out_log=%cd%\data\%1\%std_out%
set std_err_log=%cd%\data\%1\%std_err%

:Start
echo processing containers ^(JSON^)...
echo --------------------- processing containers ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- processing containers ^(JSON^) --------------------- >>!std_err_log!
:: step1: containers --> JSON
REM set containers_cmdline_args=-l%1
%php_exe% -f %tools_dir%\containers_2_json.php -- -l%1 >%cd%\data\%1\containers.json 2>>!std_err_log!
if !ERRORLEVEL! NEQ 0 (
 echo failed processing containers ^(JSON^)^, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo processing containers ^(JSON^)...DONE
echo --------------------- processing containers ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- processing containers ^(JSON^) DONE --------------------- >>!std_err_log!

echo splitting containers ^(JSON^)...
echo --------------------- splitting containers ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- splitting containers ^(JSON^) --------------------- >>!std_err_log!
:: step2: containers --> JSON (split)
%php_exe% -f %tools_dir%\containers_2_json_split.php %1 >>!std_out_log! 2>>!std_err_log!
if !ERRORLEVEL! NEQ 0 (
 echo failed splitting containers ^(JSON^)^, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo splitting containers ^(JSON^)...DONE
echo --------------------- splitting containers ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- splitting containers ^(JSON^) DONE --------------------- >>!std_err_log!

echo processing "%1"...DONE
goto :EOF
:Failed
echo processing "%1"...FAILED
goto :EOF
:Done2

:Clean_Up_3
:: undo UNC workaround
REM net use %unc_dir% /DELETE >>%cd%\data\error.log 2>>&1
if !ERRORLEVEL! NEQ 0 (
 echo failed to unmap database directory^, continuing
 set RC=!ERRORLEVEL!
)
::net use %temp_drive% /d >NUL

:Clean_Up_2
del %running_file% >NUL
if !ERRORLEVEL! NEQ 0 (
 set RC=!ERRORLEVEL!
)
 
:Clean_Up
popd
if !ERRORLEVEL! NEQ 0 (
 set RC=!ERRORLEVEL!
)
endlocal & set RC=%RC%
goto Error_Level

:Exit_Code
exit /b %1

:Error_Level
call :Exit_Code %RC%
