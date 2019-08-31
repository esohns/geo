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

set std_out_log=%cd%\data\%std_out%
set std_err_log=%cd%\data\%std_err%

:: step0: pre-step0: set: db dir/file, (default) tourset(s), working days/week, ...
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
set std_out_log=%cd%\data\%1\%std_out%
set std_err_log=%cd%\data\%1\%std_err%
goto Start
REM :NotExist
REM echo invalid file/dir^, exiting
REM goto :EOF

:Start
REM remove toursheets
del /F /S %cd%\data\%1\doc\toursheets\*.ods 2>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 set RC=%ERRORLEVEL%
	goto Failed
)

REM remove devicefiles
del /F /S %cd%\data\%1\routes\*.itn 2>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 set RC=%ERRORLEVEL%
	goto Failed
)
del /F /S %cd%\data\%1\routes\*.gpx 2>!std_err_log!
if %ERRORLEVEL% NEQ 0 (
 set RC=%ERRORLEVEL%
	goto Failed
)
echo processing "%1"...DONE
goto :EOF

:Failed
echo processing "%1"...FAILED
goto :EOF

:Done2
:Clean_Up
del %running_file% >NUL
if %ERRORLEVEL% NEQ 0 (
 set RC=%ERRORLEVEL%
)

popd
::endlocal & set RC=%ERRORLEVEL%
endlocal & set RC=%RC%
goto Error_Level

:Exit_Code
:: echo %ERRORLEVEL% %1 *WORKAROUND*
exit /b %1

:Error_Level
call :Exit_Code %RC%
