@echo off
set RC=0
setlocal enabledelayedexpansion
pushd . >NUL 2>&1
cd /D %~dp0..

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
 goto Clean_Up
)

if exist "%cd%\data\%std_err%" (
 del "%cd%\data\%std_err%" >NUL
 echo cleared standard logfile...
)

set tools_dir=.\tools
set containers_file=%tools_dir%\process_containers.cmd
set sites_file=%tools_dir%\process_sites.cmd
set toursets_file=%tools_dir%\process_toursets.cmd
set images_file=%tools_dir%\process_images.cmd
set toursheets_file=%tools_dir%\make_toursheets.cmd
set devices_file=%tools_dir%\make_devicefiles.cmd
if NOT exist "%containers_file%" (
 echo invalid file ^(was: "%containers_file%"^)^, exiting
 goto Failed
)
if NOT exist "%sites_file%" (
 echo invalid file ^(was: "%sites_file%"^)^, exiting
 goto Failed
)
if NOT exist "%toursets_file%" (
 echo invalid file ^(was: "%toursets_file%"^)^, exiting
 goto Failed
)
if NOT exist "%images_file%" (
 echo invalid file ^(was: "%images_file%"^)^, exiting
 goto Failed
)
if NOT exist "%toursheets_file%" (
 echo invalid file ^(was: "%toursheets_file%"^)^, exiting
 goto Failed
)
if NOT exist "%devices_file%" (
 echo invalid file ^(was: "%devices_file%"^)^, exiting
 goto Failed
)

for %%x in (%locations%) do call :for_2 %%x
goto Done2
:for_2
echo processing "%1"...
set std_out_log=%cd%\data\%1\%std_out%
if exist !std_out_log! (
 del !std_out_log! >NUL
 echo cleared output logfile...
)
set std_err_log=%cd%\data\%1\%std_err%
if exist !std_err_log! (
 del !std_err_log! >NUL
 echo cleared error logfile...
)

echo processing containers...
echo --------------------- processing containers --------------------- >!std_out_log!
echo --------------------- processing containers --------------------- >!std_err_log!
:: step1: containers
set cmd_line=%containers_file% %1
if !refresh_only! EQU 1 (
 set cmd_line=!cmd_line! 1
 echo !cmd_line!
)
call !cmd_line!
if !ERRORLEVEL! NEQ 0 (
 echo failed processing containers^, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo processing containers...DONE
echo --------------------- processing containers DONE --------------------- >>!std_out_log!
echo --------------------- processing containers DONE --------------------- >>!std_err_log!

echo processing sites...
echo --------------------- processing sites --------------------- >!std_out_log!
echo --------------------- processing sites --------------------- >!std_err_log!
:: step2: sites
set cmd_line=%sites_file% %1
if !refresh_only! EQU 1 (
 set cmd_line=!cmd_line! 1
)
call !cmd_line!
if !ERRORLEVEL! NEQ 0 (
 echo failed processing sites^, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo processing sites...DONE
echo --------------------- processing sites DONE --------------------- >>!std_out_log!
echo --------------------- processing sites DONE --------------------- >>!std_err_log!

echo processing toursets...
echo --------------------- processing toursets --------------------- >>!std_out_log!
echo --------------------- processing toursets --------------------- >>!std_err_log!
:: step3: toursets
set cmd_line=%toursets_file% %1
if !refresh_only! EQU 1 (
 set cmd_line=!cmd_line! 1
)
call !cmd_line!
if !ERRORLEVEL! NEQ 0 (
 echo failed processing toursets^, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo processing toursets...DONE
echo --------------------- processing toursets DONE --------------------- >>!std_out_log!
echo --------------------- processing toursets DONE --------------------- >>!std_err_log!

if !refresh_only! NEQ 0 goto Refresh_Only_Done

echo processing images...
echo --------------------- processing images --------------------- >>!std_out_log!
echo --------------------- processing images --------------------- >>!std_err_log!
:: step4: images
set cmd_line=%images_file% %1
if !refresh_only! EQU 1 (
 set cmd_line=!cmd_line! 1
)
call !cmd_line!
if !ERRORLEVEL! NEQ 0 (
 echo failed processing images^, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo processing images...DONE
echo --------------------- processing images DONE --------------------- >>!std_out_log!
echo --------------------- processing images DONE --------------------- >>!std_err_log!

echo merging "%1" KML...
echo --------------------- merging "%1" KML --------------------- >>!std_out_log!
echo --------------------- merging "%1" KML --------------------- >>!std_err_log!
:: step5: KML --> KML
%php_exe% -f %tools_dir%\merge_kml.php -- -l%1 -s%cd%\data\style.kml -o%cd%\data\%1\kml\%1_geo_data.kmz -z >>!std_out_log! 2>>!std_err_log!
if !ERRORLEVEL! NEQ 0 (
 echo failed merging "%1" KML^, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo merging "%1" KML...DONE
echo --------------------- merging "%1" KML DONE --------------------- >>!std_out_log!
echo --------------------- merging "%1" KML DONE --------------------- >>!std_err_log!

echo generating toursheets...
echo --------------------- generating toursheets --------------------- >>!std_out_log!
echo --------------------- generating toursheets --------------------- >>!std_err_log!
:: step6: toursheets
call %toursheets_file% %1 1
if !ERRORLEVEL! NEQ 0 (
 echo failed generating toursheets, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo generating toursheets...DONE
echo --------------------- generating toursheets DONE --------------------- >>!std_out_log!
echo --------------------- generating toursheets DONE --------------------- >>!std_err_log!

echo generating devicefiles...
echo --------------------- generating devicefiles --------------------- >>!std_out_log!
echo --------------------- generating devicefiles --------------------- >>!std_err_log!
:: step7: devicefiles
call %devices_file% %1 1
if !ERRORLEVEL! NEQ 0 (
 echo failed generating devicefiles, exiting
 set RC=!ERRORLEVEL!
 goto Failed
)
echo generating devicefiles...DONE
echo --------------------- generating devicefiles DONE --------------------- >>!std_out_log!
echo --------------------- generating devicefiles DONE --------------------- >>!std_err_log!

:Refresh_Only_Done
echo processing "%1"...DONE
goto :EOF
:Failed
echo processing "%1"...FAILED
goto :EOF
:Done2

REM echo processing KML...
REM for %%x in (%locations%) do call :for_2 %%x
REM goto Done2
REM :for_2
REM echo processing "%1"...
REM echo processing location "%1" ^(KML^)...
REM :: step3: KML --> KML
REM %php_exe% -f %tools_dir%\merge_kml.php %1 %cd%\data\style.kml >%cd%\data\%1\kml\%1_geo_data.kml 2>>!std_err_log!
REM if !ERRORLEVEL! NEQ 0 (
 REM echo failed processing location "%1" ^(KML^)^, exiting
 REM set RC=!ERRORLEVEL!
 REM goto Failed_2
REM )
REM echo processing location "%1" ^(KML^)...DONE
REM goto :EOF
REM :Failed_2
REM echo processing location "%1"...FAILED
REM goto :EOF
REM echo processing KML...DONE
REM goto Done2
REM :Failed

REM :Refresh_Only_Done
REM :Done2

:Clean_Up
popd >NUL 2>&1
if !ERRORLEVEL! NEQ 0 (
 set RC=!ERRORLEVEL!
)
endlocal & set RC=%RC%
goto Error_Level

:Exit_Code
exit /b %1

:Error_Level
call :Exit_Code %RC%
