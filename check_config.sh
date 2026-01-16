#!/bin/bash
# Metin2 Server - Quick Config Check
# SSH ile çalıştır: ./check_config.sh

echo "========================================="
echo "Metin2 Server Configuration Checker"
echo "========================================="
echo ""

echo "[1] Searching for CONFIG files..."
find /usr -name "CONFIG" -type f 2>/dev/null | head -10
find /home -name "CONFIG" -type f 2>/dev/null | head -10
echo ""

echo "[2] Checking first CONFIG file..."
CONFIG_FILE=$(find /usr/game -name "CONFIG" -type f 2>/dev/null | head -1)
if [ -f "$CONFIG_FILE" ]; then
    echo "Found: $CONFIG_FILE"
    echo ""
    echo "=== SQL Configuration ==="
    grep -i "SQL" "$CONFIG_FILE"
    echo ""
else
    echo "No CONFIG file found in /usr/game"
fi

echo "[3] Checking MySQL connection..."
mysql -u mt2 -p -e "SHOW DATABASES;" 2>&1 | grep -E "account|player|common|log"
echo ""

echo "[4] Checking account table..."
mysql -u mt2 -p account -e "SELECT login, status, LENGTH(password) as pwd_len FROM account LIMIT 3;" 2>&1
echo ""

echo "========================================="
echo "Done! Check results above."
echo "========================================="
