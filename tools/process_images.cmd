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
copy /y NUL %running_file% >NUL

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

set tools_dir=.\tools
for %%x in (%locations%) do call :for_2 %%x
goto Done2
:for_2
echo processing "%1"...
:: step0: set: db file, db codepage, ...
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
goto :EOF
:Continue
:: sanity check(s)
::if NOT exist "%cd%\data\%1\NUL" (
if NOT exist "%cd%\data\%1" (
 echo invalid directory ^(was: "%cd%\data\%1"^)^, exiting
 goto :EOF
)
set std_out_log=%cd%\data\%1\%std_out%
set std_err_log=%cd%\data\%1\%std_err%
goto Start

:Start
echo processing images ^(JSON^)...
echo --------------------- processing images ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- processing images ^(JSON^) --------------------- >>!std_err_log!
:: step1: images --> JSON
%php_exe% -f %tools_dir%\images_2_json.php -- %1 >%cd%\data\%1\images.json 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing images ^(JSON^)
 goto Failed
)
echo processing images ^(JSON^)...DONE
echo --------------------- processing images ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- processing images ^(JSON^) DONE --------------------- >>!std_err_log!

echo splitting images ^(JSON^)...
echo --------------------- splitting images ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- splitting images ^(JSON^) --------------------- >>!std_err_log!
:: step2: images --> JSON (split)
%php_exe% -f %tools_dir%\images_2_json_split.php -- %1 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed splitting images ^(JSON^)
 goto Failed
)
echo splitting images ^(JSON^)...DONE
echo --------------------- splitting images ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- splitting images ^(JSON^) DONE --------------------- >>!std_err_log!

if %refresh_only% NEQ 0 goto Refresh_Only_Done

echo processing images ^(KML^)...
echo --------------------- processing images ^(KML^) --------------------- >>!std_out_log!
echo --------------------- processing images ^(KML^) --------------------- >>!std_err_log!
:: step3: images --> KML
%php_exe% -f %tools_dir%\images_2_kml.php %1 %cd%\data\style.kml >%cd%\data\%1\kml\images.kml 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing images ^(KML^)
 goto Failed
)
echo processing images ^(KML^)...DONE
echo --------------------- processing images ^(KML^) DONE --------------------- >>!std_out_log!
echo --------------------- processing images ^(KML^) DONE --------------------- >>!std_err_log!

:Refresh_Only_Done
echo processing "%1"...DONE
goto :EOF
:Failed
echo processing "%1"...FAILED
goto :EOF
:Done2

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
