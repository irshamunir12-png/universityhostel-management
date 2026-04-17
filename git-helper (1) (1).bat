@echo off
title Git Project Backup Helper
color 0B
setlocal EnableDelayedExpansion

:: =========================================================
:: 1. SETUP AND CHECKS
:: =========================================================

:: Change directory to where this script is located
cd /d "%~dp0"

:: Check if Git is installed on this computer
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Git is not installed or not found.
    echo Please install from: https://git-scm.com/
    echo.
    pause
    exit /b
)

:: Clear screen for a clean look
cls

echo.
echo ========================================================
echo           GIT PROJECT BACKUP HELPER
echo ========================================================
echo   Current Project Folder: %CD%
echo ========================================================
echo.

:: =========================================================
:: 2. MAIN MENU (Organized by Workflow)
:: =========================================================

:MENU
echo === FIRST TIME SETUP (Do this once per project) ===
echo [1] Setup User Name and Email
echo [2] Initialize Git Repository
echo [3] Create .gitignore File (Ignore junk files)
echo [4] Connect to GitHub Remote
echo.
echo === DAILY WORKFLOW (Use this every day) ===
echo [5] Check Status (See what changed)
echo [6] Stage All Files (Prepare for backup)
echo [7] Commit Changes (Save snapshot with message)
echo [8] Push to GitHub (Upload to cloud)
echo.
echo === UTILITIES ===
echo [9] Quick Backup (Stage + Commit + Push in one go)
echo [0] Exit
echo.
echo ========================================================

set /p "choice=Enter your choice (0-9): "

if "%choice%"=="1" goto CONFIG
if "%choice%"=="2" goto INIT
if "%choice%"=="3" goto GITIGNORE
if "%choice%"=="4" goto REMOTE
if "%choice%"=="5" goto STATUS
if "%choice%"=="6" goto ADD
if "%choice%"=="7" goto COMMIT
if "%choice%"=="8" goto PUSH
if "%choice%"=="9" goto QUICK
if "%choice%"=="0" goto END

echo.
echo [!] Invalid choice. Please select 0-9.
echo.
pause
cls
goto MENU

:: =========================================================
:: 3. SETUP FUNCTIONS
:: =========================================================

:CONFIG
cls
echo.
echo === SETUP USER NAME AND EMAIL ===
echo.
echo WHY: Git needs to know who is saving the code.
echo      This is required only ONCE per computer.
echo.
echo COMMAND: git config --global user.name "Your Name"
echo          git config --global user.email "your@email.com"
echo.

set /p "username=Enter your Git User Name: "
set /p "email=Enter your Git Email: "

:: Save configuration globally
git config --global user.name "%username%"
git config --global user.email "%email%"

echo.
echo [SUCCESS] Configuration saved!
echo.
pause
cls
goto MENU

:INIT
cls
echo.
echo === INITIALIZE GIT REPOSITORY ===
echo.
echo WHY: Creates a hidden .git folder to track changes.
echo      Do this only ONCE per project folder.
echo.
echo COMMAND: git init
echo.

set /p "confirm=Are you sure? (y/n): "
if /i not "%confirm%"=="y" goto MENU

:: Initialize the repository
git init

echo.
echo [SUCCESS] Git repository initialized!
echo.
pause
cls
goto MENU

:GITIGNORE
cls
echo.
echo === CREATE .GITIGNORE FILE ===
echo.
echo WHY: Tells Git which files to IGNORE (like passwords, 
echo      temporary files, large libraries).
echo.
echo COMMAND: Creates a .gitignore file with common rules
echo.

set /p "confirm=Create standard .gitignore? (y/n): "
if /i not "%confirm%"=="y" goto MENU

:: Create the file with common ignore rules
(
echo # Operating System
echo .DS_Store
echo Thumbs.db
echo Desktop.ini
echo.
echo # Editor Files
echo .vscode/
echo .idea/
echo *.swp
echo.
echo # Secrets (Never upload these!)
echo .env
echo *.key
echo passwords.txt
echo.
echo # Dependencies
echo node_modules/
echo venv/
echo __pycache__/
echo.
echo # Build Outputs
echo dist/
echo build/
echo *.log
) > .gitignore

echo.
echo [SUCCESS] .gitignore file created!
echo.
pause
cls
goto MENU

:REMOTE
cls
echo.
echo === CONNECT TO GITHUB REMOTE ===
echo.
echo WHY: Links your local folder to a GitHub repository.
echo      You need to create a repo on GitHub.com first.
echo.
echo COMMAND: git remote add origin [URL]
echo.
echo INSTRUCTIONS:
echo 1. Go to github.com and create a new repository.
echo 2. Copy the URL (e.g., https://github.com/user/repo.git)
echo 3. Paste it below.
echo.

set /p "repourl=Enter GitHub Repository URL: "
if "%repourl%"=="" (
    echo [ERROR] URL cannot be empty.
    pause
    goto MENU
)

:: Check if remote already exists
git remote get-url origin >nul 2>&1
if %errorlevel% equ 0 (
    echo [INFO] Remote exists. Updating URL...
    git remote set-url origin "%repourl%"
) else (
    git remote add origin "%repourl%"
)

echo.
echo [SUCCESS] Connected to GitHub!
echo.
pause
cls
goto MENU

:: =========================================================
:: 4. DAILY WORKFLOW FUNCTIONS
:: =========================================================

:STATUS
cls
echo.
echo === CHECK STATUS ===
echo.
echo WHY: Shows which files are new, modified, or ready to save.
echo.
echo COMMAND: git status
echo.
echo --- OUTPUT ---
git status
echo --- END OUTPUT ---
echo.
pause
cls
goto MENU

:ADD
cls
echo.
echo === STAGE ALL FILES ===
echo.
echo WHY: Prepares all changed files for the next backup snapshot.
echo      The dot (.) means "all files in this folder and subfolders".
echo.
echo COMMAND: git add .
echo.

git add .

echo.
echo [SUCCESS] All files staged and ready to commit!
echo.
pause
cls
goto MENU

:COMMIT
cls
echo.
echo === COMMIT CHANGES ===
echo.
echo WHY: Saves a snapshot of your staged files with a message.
echo      This creates a permanent record in your local history.
echo.
echo COMMAND: git commit -m "Your message here"
echo.

set /p "message=Enter commit message (e.g., Fixed login bug): "
if "%message%"=="" (
    echo [ERROR] Message cannot be empty.
    pause
    goto MENU
)

:: Create the commit with the message
git commit -m "%message%"

echo.
echo [SUCCESS] Changes committed locally!
echo.
pause
cls
goto MENU

:PUSH
cls
echo.
echo === PUSH TO GITHUB ===
echo.
echo WHY: Uploads your local commits to the GitHub cloud.
echo      This is your actual backup.
echo.
echo COMMAND: git push -u origin main
echo.

set /p "confirm=Ready to upload? (y/n): "
if /i not "%confirm%"=="y" goto MENU

:: Ensure branch is named 'main'
git branch -M main >nul 2>&1

:: Push to remote repository
git push -u origin main

if %errorlevel% neq 0 (
    echo.
    echo [NOTE] Push requires authentication.
    echo Please complete the login in your browser or terminal.
) else (
    echo.
    echo [SUCCESS] Code uploaded to GitHub!
)
echo.
pause
cls
goto MENU

:: =========================================================
:: 5. UTILITY FUNCTIONS
:: =========================================================

:QUICK
cls
echo.
echo === QUICK BACKUP (All-in-One) ===
echo.
echo This will: Stage + Commit + Push in one step.
echo.

:: Step 1: Stage
echo [1/3] Staging files...
git add .

:: Step 2: Commit
set /p "message=Enter commit message: "
if "%message%"=="" set message=Quick backup
git commit -m "%message%"

:: Step 3: Push
echo [3/3] Pushing to GitHub...
git branch -M main >nul 2>&1
git push -u origin main

echo.
echo [SUCCESS] Quick backup complete!
echo.
pause
cls
goto MENU

:END
cls
echo.
echo ========================================================
echo   Thank you for using Git Project Backup Helper!
echo ========================================================
echo.
pause
exit /b