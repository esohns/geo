@echo off
set RC=0
setlocal enabledelayedexpansion
pushd . >NUL 2>&1

set jsmin_exe=%cd%\tools\3rd_party\jsmin.exe
set js_common_dir=%cd%\common\js
set js_geo_dir=%cd%\geo\js
set js_search_dir=%cd%\search\js
set std_out=output.log
set std_err=error.log
set copyright_default=(c)2013 Humana Kleidersammlung GmbH
set copyright=%copyright_default%
set argc=0
if "%*" EQU "" goto Done1
for %%x in (%*) do set /A argc+=1
if %argc% EQU 0 goto Done1
set copyright=
for %%x in (%*) do call :for_1 %%x
goto Done1
:for_1
if "%copyright%."=="." (
 set copyright=%1
 goto :EOF
)
set copyright=%copyright% %1
goto :EOF
:Done1

:: sanity check(s)
if NOT exist %jsmin_exe% (
 echo JSMIN tool not found^, exiting
 set RC=1
 goto Clean_Up
)
if NOT exist %js_common_dir% (
 echo js directory not found^, exiting
 set RC=1
 goto Clean_Up
)
if NOT exist %js_geo_dir% (
 echo js directory not found^, exiting
 set RC=1
 goto Clean_Up
)
if NOT exist %js_search_dir% (
 echo js directory not found^, exiting
 set RC=1
 goto Clean_Up
)

for %%f in ("%js_common_dir%\*.js") do (
	set filename=%%~pf..\src\%%~nf.min.js
 %jsmin_exe% "%copyright%" <%%f >!filename! 2>>%std_err%
)
for %%f in ("%js_geo_dir%\*.js") do (
	set filename=%%~pf..\src\%%~nf.min.js
 %jsmin_exe% "%copyright%" <%%f >!filename! 2>>%std_err%
)
for %%f in ("%js_search_dir%\*.js") do (
	set filename=%%~pf..\src\%%~nf.min.js
 %jsmin_exe% "%copyright%" <%%f >!filename! 2>>%std_err%
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
