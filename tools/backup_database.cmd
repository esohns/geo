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

:: work around unsupported UNC paths...
set unc_dir=\\Rechner1\Coffee
net use %unc_dir% /user:Rechner1\www www >>%cd%\data\%std_err% 2>>&1
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
set backup_dir=C:\Coffee_Backup
if NOT exist "!backup_dir!" (
 echo invalid directory ^(was: "!backup_dir!"^)^, exiting
 goto Failed
)
echo database directory: "!unc_dir!"
echo backup directory  : "!backup_dir!"
set std_out_log=%cd%\data\%std_out%
set std_err_log=%cd%\data\%std_err%

echo processing database directories...
echo --------------------- processing database directories --------------------- >>!std_out_log!
echo --------------------- processing database directories --------------------- >>!std_err_log!
REM copy /B /D /V /Y /Z "!unc_dir!\*" "!backup_dir!" >>%cd%\data\error.log 2>>&1
xcopy "!unc_dir!\*" "!backup_dir!" /C /I /E /F /G /H /K /R /V /Y /Z >>%std_out_log% 2>>%std_err_log%
if %ERRORLEVEL% NEQ 0 (
 echo failed to backup database files^, exiting
 set RC=%ERRORLEVEL%
 goto Failed
)
echo processing database directories...DONE
echo --------------------- processing database directories DONE --------------------- >>!std_out_log!
echo --------------------- processing database directories DONE --------------------- >>!std_err_log!
goto Clean_Up_3

:Failed
echo processing database directories...FAILED

:Clean_Up_3
:: undo UNC workaround
net use %unc_dir% /DELETE >>%cd%\data\%std_err% 2>>&1
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
::endlocal & set RC=%ERRORLEVEL%
endlocal & set RC=%RC%
goto Error_Level

:Exit_Code
:: echo %ERRORLEVEL% %1 *WORKAROUND*
exit /b %1

:Error_Level
call :Exit_Code %RC%
