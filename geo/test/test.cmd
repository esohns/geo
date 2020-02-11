@echo off

setlocal enabledelayedexpansion

:: work around unsupported UNC paths...
set temp_drive=Z:
set unc_dir=\\Rechner1\Coffee
::net use %unc_dir% /user:Rechner1\nobody nobody >NUL
net use %temp_drive% "%unc_dir%" /user:Rechner1\www www >NUL
if %ERRORLEVEL% NEQ 0 (
 echo failed to map directory "%unc_dir%"
 goto Clean_Up
)
::pushd %unc_dir% 2>NUL
::set temp_drive=%cd%
::cd /D %~dp0..
echo mapped directory "%unc_dir%" to %temp_drive%...

set file=%unc_dir%\Coffee BW\Sites.dbf
if NOT exist "%file%" (
 echo file "%file%" does not exist
 goto Not_Exist_1
)
echo file "%file%" exists
:Not_Exist_1
set dir=%unc_dir%\Coffee BW\
if NOT exist "%dir%" (
 echo dir "%dir%" does not exist
 goto Not_Exist_2
)
echo dir "%dir%" exists
:Not_Exist_2
set file=%temp_drive%\Coffee BW\Sites.dbf
if NOT exist "%file%" (
 echo file "%file%" does not exist
 goto Not_Exist_3
)
echo file "%file%" exists
:Not_Exist_3
set dir=%temp_drive%\Coffee BW\
if NOT exist "%dir%" (
 echo dir "%dir%" does not exist
 goto Not_Exist_4
)
echo dir "%dir%" exists

:Not_Exist_4
set dir=%cd%\data\test\NUL
if NOT exist "!dir!" (
 echo dir "!dir!" does not exist
 goto Not_Exist_5
)
echo dir "!dir!" exists

:Not_Exist_5
set dir=%cd%\data\test
if NOT exist "!dir!" (
 echo dir "!dir!" does not exist
 goto Clean_Up
)
echo dir "!dir!" exists

:Clean_Up
:: undo UNC workaround
net use %temp_drive% /d >NUL
::popd

endlocal
