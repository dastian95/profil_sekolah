#!/bin/bash
# run-dev-server.sh - Quick script to start development server

@echo off
cd /d "g:\laragon\www\profil_sekolah" || exit /b
echo.
echo ========================================
echo   PROFIL SEKOLAH - Development Server
echo ========================================
echo.
echo Starting PHP Development Server...
echo Server will run on: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server
echo.
"G:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" -S localhost:8000
