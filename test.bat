@echo off
echo ========================================
echo Metin2 Web Panel - Quick Test Script
echo ========================================
echo.

echo [1/3] Testing API Status...
curl -s "http://192.168.1.105/api-bridge/?action=status" > test_status.json
echo Status response saved to test_status.json
type test_status.json
echo.
echo.

echo [2/3] Testing Login...
echo Enter your Metin2 username:
set /p username=
echo Enter your Metin2 password:
set /p password=

curl -s -X POST "http://192.168.1.105/api-bridge/?action=login" ^
  -H "Content-Type: application/json" ^
  -d "{\"login\":\"%username%\",\"password\":\"%password%\"}" > test_login.json

echo Login response saved to test_login.json
type test_login.json
echo.
echo.

echo [3/3] Starting Web Panel...
cd C:\Users\Windows\Desktop\metin2\server\web-panel
start "" npm run dev

echo.
echo ========================================
echo Tests Complete!
echo.
echo If login succeeded, open: http://localhost:3000
echo ========================================
pause
