#!/bin/bash
if [ -d "public/themes/admin" ]; then
    rsync -avz --delete vendor/uicms/admin/install/public/themes/admin/ public/themes/admin/
fi