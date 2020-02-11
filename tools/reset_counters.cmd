@echo off
set RC=0
setlocal enabledelayedexpansion
pushd . >NUL 2>&1

set log_dir=%cd%\logs
set std_out=output.log
set std_err=error.log

:: sanity check(s)
if NOT exist %log_dir% (
 echo log directory "%log_dir%" not found^, exiting 
 set RC=1
 goto Clean_Up
)

for %%f in ("%log_dir%\*.txt") do (
 copy /Y nul: %%f >>%std_out% 2>>%std_err%
)

:Clean_Up
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
