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
set statistics=1
rem set statistics=0
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
if %refresh_only% NEQ 0 (
 set statistics=0
 echo statistics disabled...
)
:: sanity check(s)
if NOT exist %php_exe% (
 echo PHP runtime not found^, exiting
 set RC=1
 goto Clean_Up_2
)

:: work around unsupported UNC paths...
::set temp_drive=K:
REM set unc_dir=C:\Coffee
set unc_dir=\\Rechner1\Coffee
net use %unc_dir% /user:Rechner1\www www >>%cd%\data\error.log 2>>&1
::net use %temp_drive% %unc_dir% /user:Rechner1\www www >NUL 2>%cd%\data\error.log
if %ERRORLEVEL% NEQ 0 (
 echo failed to map database directory "%unc_dir%"^, exiting
 set RC=%ERRORLEVEL%
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
 goto :EOF
)
rem if NOT exist %cd%\data\%1\kml\NUL goto NotExist
if NOT exist "%cd%\data\%1\kml" (
 echo invalid directory ^(was: "%cd%\data\%1\kml"^)^, exiting
 goto :EOF
)
set std_out_log=%cd%\data\%1\%std_out%
set std_err_log=%cd%\data\%1\%std_err%

:Start
if %refresh_only% NEQ 0 goto Refresh_Only_Skip_1

REM echo processing contacts (JSON)...
REM :: step1: contacts --> JSON
REM %php_exe% -f %tools_dir%\contacts_2_json.php %1 "!contacts_file!" >%cd%\data\%1\contacts.json 2>>!std_err_log!
REM if %ERRORLEVEL% NEQ 0 (
 REM echo failed processing contacts ^(JSON^)
 REM goto Failed
REM )
REM echo processing contacts (JSON)...DONE

:Refresh_Only_Skip_1
echo processing site coordinates...
echo --------------------- processing site coordinates --------------------- >>!std_out_log!
echo --------------------- processing site coordinates --------------------- >>!std_err_log!
:: step1: addresses --> coordinates
%php_exe% -f %tools_dir%\sites_2_LatLong.php -- -i -l%1 >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing new sites ^(coordinates^)
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing site coordinates...DONE
echo --------------------- processing site coordinates DONE--------------------- >>!std_out_log!
echo --------------------- processing site coordinates DONE--------------------- >>!std_err_log!

echo processing sites ^(JSON^)...
echo --------------------- processing sites ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- processing sites ^(JSON^) --------------------- >>!std_err_log!
:: step2: sites --> JSON
set sites_cmdline_args=-l%1 -o%cd%\data\%1\sites.json
if %statistics% NEQ 0 (
 set sites_cmdline_args=!sites_cmdline_args! -y
)
%php_exe% -f %tools_dir%\sites_2_json.php -- !sites_cmdline_args! >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing sites ^(JSON^)
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing sites ^(JSON^)...DONE
echo --------------------- processing sites ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- processing sites ^(JSON^) DONE --------------------- >>!std_err_log!

echo processing known finders ^(JSON^)...
echo --------------------- processing known finders ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- processing known finders ^(JSON^) --------------------- >>!std_err_log!
:: step3: known finders --> JSON
%php_exe% -f %tools_dir%\finders_2_json.php -- -l%1 >%cd%\data\%1\finders.json 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing known finders ^(JSON^)
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing known finders ^(JSON^)...DONE
echo --------------------- processing known finders ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- processing known finders ^(JSON^) DONE --------------------- >>!std_err_log!

echo processing duplicate sites ^(JSON^)...
echo --------------------- processing duplicate sites ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- processing duplicate sites ^(JSON^) --------------------- >>!std_err_log!
:: step4: sites --> JSON (duplicates)
%php_exe% -f %tools_dir%\sites_json_2_json_duplicates.php %1 >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing duplicate sites ^(JSON^)
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing duplicate sites ^(JSON^)...DONE
echo --------------------- processing duplicate sites ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- processing duplicate sites ^(JSON^) DONE --------------------- >>!std_err_log!

echo splitting sites ^(JSON^)...
echo --------------------- splitting sites ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- splitting sites ^(JSON^) --------------------- >>!std_err_log!
:: step5: sites --> JSON (split)
%php_exe% -f %tools_dir%\sites_2_json_split.php %1 >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed splitting sites ^(JSON^)
 set RC=%ERRORLEVEL%
 goto Failed
)
echo splitting sites ^(JSON^)...DONE
echo --------------------- splitting sites ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- splitting sites ^(JSON^) DONE --------------------- >>!std_err_log!

if %refresh_only% NEQ 0 goto Refresh_Only_Done

echo processing sites ^(KML^)...
echo --------------------- processing sites ^(KML^) --------------------- >>!std_out_log!
echo --------------------- processing sites ^(KML^) --------------------- >>!std_err_log!
:: step6: sites --> KML
%php_exe% -f %tools_dir%\sites_2_kml.php -- -l%1 -o%cd%\data\%1\kml\sites.kml >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing sites ^(KML^)
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing sites ^(KML^)...DONE
echo --------------------- processing sites ^(KML^) DONE --------------------- >>!std_out_log!
echo --------------------- processing sites ^(KML^) DONE --------------------- >>!std_err_log!

:Refresh_Only_Done
echo processing "%1"...DONE
goto :EOF
:Failed
echo processing "%1"...FAILED
goto :EOF
:Done2

:Clean_Up_3
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
REM echo %ERRORLEVEL% %1 *WORKAROUND*
exit /b %1

:Error_Level
call :Exit_Code %RC%
