#!/usr/bin/env python
"""
Quick launcher for Profil Sekolah Development Server
Execute: python start-dev.py
"""

import subprocess
import os
import sys
import webbrowser
import time

def main():
    project_dir = os.path.dirname(os.path.abspath(__file__))
    php_exe = r"G:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
    host = "localhost"
    port = 8000
    url = f"http://{host}:{port}"
    
    print("=" * 60)
    print("  PROFIL SEKOLAH - Development Server")
    print("=" * 60)
    print()
    print(f"📁 Project: {project_dir}")
    print(f"🐘 PHP:     {php_exe}")
    print(f"🌐 URL:     {url}")
    print()
    
    # Change to project directory
    os.chdir(project_dir)
    print(f"✅ Changed directory to: {os.getcwd()}")
    print()
    
    # Start server
    print("🚀 Starting PHP Development Server...")
    print("   Press Ctrl+C to stop")
    print()
    
    try:
        # Open browser after 2 seconds
        time.sleep(2)
        print(f"🌐 Opening {url} in browser...")
        webbrowser.open(url)
        
        # Run server
        subprocess.run([php_exe, "-S", f"{host}:{port}"], check=True)
        
    except KeyboardInterrupt:
        print("\n\n⛔ Server stopped.")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
