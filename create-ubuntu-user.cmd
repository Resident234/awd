@echo off
chcp 65001 >nul
Title Create Ubuntu user gsu
wsl.exe -d Ubuntu -u root -- adduser gsu
if errorlevel 1 (
  echo.
  echo User creation returned an error. Review the message above.
) else (
  echo.
  echo User gsu was created successfully.
)
echo.
pause
