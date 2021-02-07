#!/bin/bash
if [ -d "public/themes/admin" ]; then
    rsync -avz vendor/uicms/admin/install/public/themes/admin/ public/themes/admin/
fi