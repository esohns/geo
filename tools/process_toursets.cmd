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
REM set unc_dir=C:\\Coffee
set unc_dir=\\Rechner1\Coffee
net use %unc_dir% /user:Rechner1\www www >>"%cd%\data\%std_err%" 2>>&1
::net use %temp_drive% %unc_dir% /user:Rechner1\www www >NUL 2>%cd%\data\error.log
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
echo processing warehouse locations ^(JSON^)...
:: step0: warehouse locations --> JSON
%php_exe% -f %tools_dir%\warehouse_locations_2_json.php -o"%cd%\data\warehouse_locations.json" >>"%cd%\data\%std_out%" 2>>"%cd%\data\%std_err%"
if %ERRORLEVEL% NEQ 0 (
 echo failed processing warehouse locations ^(JSON^)^, exiting
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing warehouse locations ^(JSON^)...DONE

:: step0: pre-step0: set: db dir/file, (default) tourset(s), working days/week, ...
for %%x in (%locations%) do call :for_2 %%x
goto Done2
:for_2
echo processing "%1"...
set toursets=New Standard
if "%1" EQU "b" (
 set toursets=2010 2011 063112 "2008 Jan" "JAN 05" Kombi Import Okt07 SEP07 Standard TourAlt
 goto Continue
)
if "%1" EQU "bw" (
 set toursets=2006Q2 NEW
 goto Continue
)
if "%1" EQU "d" (
 set toursets=Standard
 goto Continue
)
if "%1" EQU "hh" (
 set toursets=Standard 2009 2010
 goto Continue
)
if "%1" EQU "ks" (
 set toursets=Standard
 goto Continue
)
if "%1" EQU "mh" (
 set toursets=2006Q2 NEW
 goto Continue
)
if "%1" EQU "th" (
 set toursets=2012Q1
 goto Continue
)
if "%1" EQU "wf" (
 set toursets=Standard
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
rem if NOT exist %cd%\data\%1\NUL (
if NOT exist %cd%\data\%1 (
 echo invalid directory ^(was: "%cd%\data\%1"^)^, exiting
 goto :EOF
)
rem if NOT exist %cd%\data\%1\kml\NUL (
if NOT exist %cd%\data\%1\kml (
 echo invalid directory ^(was: "%cd%\data\%1\kml"^)^, exiting
 goto :EOF
)
echo location "%1" --^> tourset(s)        : "!toursets!"
set std_out_log=%cd%\data\%1\%std_out%
set std_err_log=%cd%\data\%1\%std_err%
goto Start
REM :NotExist
REM echo invalid file/dir^, exiting
REM goto :EOF

:Start
echo processing tourset IDs ^(JSON^)...
echo --------------------- processing tourset IDs ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- processing tourset IDs ^(JSON^) --------------------- >>!std_err_log!
:: step1: tourset IDs --> JSON
%php_exe% -f %tools_dir%\tourlist_ids_2_json.php -- -l%1 -o"%cd%\data\%1\tourset_ids.json" >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing tourset IDs ^(JSON^)^, exiting
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing tourset IDs ^(JSON^)...DONE
echo --------------------- processing tourset IDs ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- processing tourset IDs ^(JSON^) DONE --------------------- >>!std_err_log!

echo processing toursets ^(JSON^)...
echo --------------------- processing toursets ^(JSON^) --------------------- >>!std_out_log!
echo --------------------- processing toursets ^(JSON^) --------------------- >>!std_err_log!
:: step2: toursets --> JSON
%php_exe% -f %tools_dir%\tours_2_json.php -- -l%1 -o"%cd%\data\%1\toursets.json" >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing toursets ^(JSON^)^, exiting
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing toursets ^(JSON^)...DONE
echo --------------------- processing toursets ^(JSON^) DONE --------------------- >>!std_out_log!
echo --------------------- processing toursets ^(JSON^) DONE --------------------- >>!std_err_log!

if %refresh_only% NEQ 0 goto Refresh_Only_Done

REM echo processing toursets ^(TXT^)...
REM echo --------------------- processing toursets ^(TXT^) --------------------- >>!std_out_log!
REM echo --------------------- processing toursets ^(TXT^) --------------------- >>!std_err_log!
REM :: step3: toursets --> TXT
REM %php_exe% -f %tools_dir%\tours_2_lists.php -- l%1 -o"%cd%\data\%1\toursets.json" >>!std_out_log! 2>>!std_err_log!
REM if %ERRORLEVEL% NEQ 0 (
 REM echo failed processing toursets ^(TXT^)^, exiting
 REM goto Failed
REM )
REM echo processing toursets ^(TXT^)...DONE
REM echo --------------------- processing toursets ^(TXT^) DONE --------------------- >>!std_out_log!
REM echo --------------------- processing toursets ^(TXT^) DONE --------------------- >>!std_err_log!

echo processing toursets ^(KML^)...
echo --------------------- processing toursets ^(KML^) --------------------- >>!std_out_log!
echo --------------------- processing toursets ^(KML^) --------------------- >>!std_err_log!
:: step4: toursets --> KML
%php_exe% -f %tools_dir%\tours_2_kml.php -- -d -l%1 -o"%cd%\data\%1\kml\toursets.kml" >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing toursets ^(KML^)^, exiting
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing toursets ^(KML^)...DONE
echo --------------------- processing toursets ^(KML^) DONE --------------------- >>!std_out_log!
echo --------------------- processing toursets ^(KML^) DONE --------------------- >>!std_err_log!

for %%y in (%toursets%) do call :for_3 %1 %%y
goto Done3
:for_3
echo processing tourset "%2"...
REM echo processing tourset "%2" ^(JSON^)...
REM echo --------------------- processing tourset "%2" ^(JSON^) --------------------- >>!std_out_log!
REM echo --------------------- processing tourset "%2" ^(JSON^) --------------------- >>!std_err_log!
REM :: step5a: tourset --> JSON
REM %php_exe% -f %tools_dir%\tours_2_json.php -- -l%1 -o"%cd%\data\%1\tourset_%2.json" -t"%2" >>!std_out_log! 2>>!std_err_log!
REM if %ERRORLEVEL% NEQ 0 (
 REM echo failed processing tourset "%2" ^(JSON^)^, exiting
 REM goto Failed_2
REM )
REM echo processing tourset "%2" ^(JSON^)...DONE
REM echo --------------------- processing tourset "%2" ^(JSON^) DONE --------------------- >>!std_out_log!
REM echo --------------------- processing tourset "%2" ^(JSON^) DONE --------------------- >>!std_err_log!

echo processing tourset "%2" ^(TXT^)...
echo --------------------- processing tourset "%2" ^(TXT^) --------------------- >>!std_out_log!
echo --------------------- processing tourset "%2" ^(TXT^) --------------------- >>!std_err_log!
:: step5b: tourset --> TXT
%php_exe% -f %tools_dir%\tours_2_lists.php -- -l%1 -o"%cd%\data\%1\tourset_%2.txt" -t"%2" >>!std_out_log! 2>>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 echo failed processing tourset "%2" ^(TXT^)^, exiting
 set RC=%ERRORLEVEL%
 goto Failed_2
)
echo processing tourset "%2" ^(TXT^)...DONE
echo --------------------- processing tourset "%2" ^(TXT^) DONE --------------------- >>!std_out_log!
echo --------------------- processing tourset "%2" ^(TXT^) DONE --------------------- >>!std_err_log!

REM echo processing tourset "%2" ^(KML^)...
REM echo --------------------- processing tourset "%2" ^(KML^) --------------------- >>!std_out_log!
REM echo --------------------- processing tourset "%2" ^(KML^) --------------------- >>!std_err_log!
REM :: step5c: tourset --> KML
REM %php_exe% -f %tools_dir%\tours_2_kml.php -- l%1 -o"%cd%\data\%1\kml\tourset_%2.kml" -t"%2" >>!std_out_log! 2>>!std_err_log!
REM if %ERRORLEVEL% NEQ 0 (
 REM echo failed processing tourset "%2" ^(KML^)^, exiting
 REM goto Failed_2
REM )
REM echo processing tourset "%2" ^(KML^)...DONE
REM echo --------------------- processing tourset "%2" ^(KML^) DONE --------------------- >>!std_out_log!
REM echo --------------------- processing tourset "%2" ^(KML^) DONE --------------------- >>!std_err_log!

echo processing tourset "%2"...DONE
goto :EOF
:Failed_2
echo processing tourset "%2"...FAILED
goto :EOF

:Done3
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
:: echo %ERRORLEVEL% %1 *WORKAROUND*
exit /b %1

:Error_Level
call :Exit_Code %RC%
