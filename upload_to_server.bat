@echo off
echo ========================================
echo Uploading api-bridge to 192.168.1.105
echo ========================================
echo.

echo Using SCP to upload files...
scp -r api-bridge root@192.168.1.105:/usr/local/www/apache24/data/

echo.
echo Done! Now SSH and set permissions:
echo ssh root@192.168.1.105
echo cd /usr/local/www/apache24/data
echo chmod -R 755 api-bridge
echo chown -R www:www api-bridge
echo.
pause
