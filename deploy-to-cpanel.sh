#!/bin/bash

# ================================================
# Script Deployment GASPUL Backend ke cPanel
# ================================================

echo "======================================"
echo "  DEPLOYMENT GASPUL BACKEND TO CPANEL"
echo "======================================"
echo ""

# Warna untuk output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Pull latest code from GitHub
echo -e "${YELLOW}[1/5] Pulling latest code from GitHub...${NC}"
git pull origin main

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Git pull berhasil${NC}"
else
    echo -e "${RED}✗ Git pull gagal${NC}"
    exit 1
fi

echo ""

# Step 2: Clear Laravel cache
echo -e "${YELLOW}[2/5] Clearing Laravel cache...${NC}"
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Cache cleared${NC}"
else
    echo -e "${RED}✗ Clear cache gagal${NC}"
fi

echo ""

# Step 3: Set folder permissions
echo -e "${YELLOW}[3/5] Setting folder permissions...${NC}"
chmod -R 755 public/tiket
chmod -R 755 storage
chmod -R 755 bootstrap/cache

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Permissions set${NC}"
else
    echo -e "${RED}✗ Set permissions gagal${NC}"
fi

echo ""

# Step 4: Create required directories if not exist
echo -e "${YELLOW}[4/5] Checking required directories...${NC}"
mkdir -p public/tiket
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views

echo -e "${GREEN}✓ Directories checked${NC}"

echo ""

# Step 5: Show recent commits
echo -e "${YELLOW}[5/5] Recent commits:${NC}"
git log --oneline -5

echo ""
echo -e "${GREEN}======================================"
echo -e "  DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo -e "======================================${NC}"
echo ""
echo "Next steps:"
echo "1. Test API endpoint: /api/antrian/submit"
echo "2. Test admin dashboard"
echo "3. Test monitor antrian page"
echo ""
