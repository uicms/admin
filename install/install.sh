#!/bin/bash
if [ ! -d "public/themes" ]; then
    mkdir public/themes
fi
if [ ! -d "public/themes/admin" ]; then
    cd public/themes
    cp -r ../../vendor/uicms/admin/install/public/themes/admin admin
    cd ../..
fi