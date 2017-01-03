; winagentd.nsi
;
; This script is based on example1.nsi, but it remember the directory, 
; has uninstall support and (optionally) installs start menu shortcuts.
;
; It will install winagentd.nsi into a directory that the user selects,

;--------------------------------

; The name of the installer
Name "Winagentd"

; The file to write
OutFile "winagentdinstaller.exe"

; The default installation directory
InstallDir $PROGRAMFILES\winagentd

; Registry key to check for directory (so if you install again, it will 
; overwrite the old one automatically)
InstallDirRegKey HKLM "Software\NSIS_Winagentd" "Install_Dir"

; Request application privileges for Windows Vista
RequestExecutionLevel admin

;--------------------------------

; Includes

!include "WinMessages.nsh"

;--------------------------------

; Vars

Var HWND
Var INI
VAR CMURI

;--------------------------------

; Pages

;Page components
Page directory
Page instfiles
Page custom showCustomCM leaveCustomCM ": Edit CM URI"

UninstPage uninstConfirm
UninstPage instfiles

;--------------------------------

; The stuff to install
Section ""

  SectionIn RO
  
  ; Set output path to the installation directory.
  SetOutPath $INSTDIR
  
  ; Put file there
  File "winagentd.exe"
  File "winagentd.ini"

  File "cygcrypt-0.dll"
  File "cyggcc_s-1.dll"
  File "cygperl5_10.dll"
  File "cygssp-0.dll"
  File "cygwin1.dll"
  
  ; Write the installation path into the registry
  WriteRegStr HKLM SOFTWARE\Winagentd "Install_Dir" "$INSTDIR"
  
  ; Write the uninstall keys for Windows
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Winagentd" "DisplayName" "Winagentd"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Winagentd" "UninstallString" '"$INSTDIR\uninstall.exe"'
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Winagentd" "NoModify" 1
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Winagentd" "NoRepair" 1
  WriteUninstaller "uninstall.exe"
  
  CreateDirectory "$SMPROGRAMS\Winagentd"
  CreateShortCut "$SMPROGRAMS\Winagentd\Uninstall.lnk" "$INSTDIR\uninstall.exe" "" "$INSTDIR\uninstall.exe" 0
  CreateShortCut "$SMPROGRAMS\Winagentd\Winagentd.lnk" "$INSTDIR\winagentd.exe" "" "$INSTDIR\winagentd.exe" 0

  CreateShortCut "$SMSTARTUP\Winagentd.lnk" "$INSTDIR\winagentd.exe" "" "$INSTDIR\winagentd.exe" 0
  
SectionEnd

;--------------------------------

; Uninstaller

Section "Uninstall"
  
  ; Remove registry keys
  DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Winagentd"
  DeleteRegKey HKLM SOFTWARE\NSIS_Winagentd

  ; Remove files and uninstaller
  Delete $INSTDIR\winagentd.exe
  Delete $INSTDIR\winagentd.ini
  Delete $INSTDIR\uninstall.exe
  Delete $INSTDIR\cygcrypt-0.dll
  Delete $INSTDIR\cyggcc_s-1.dll
  Delete $INSTDIR\cygperl5_10.dll
  Delete $INSTDIR\cygssp-0.dll
  Delete $INSTDIR\cygwin1.dll

  ; Remove shortcuts, if any
  Delete "$SMPROGRAMS\Winagentd\*.*"
  Delete "$SMSTARTUP\Winagentd.lnk"

  ; Remove directories used
  RMDir "$SMPROGRAMS\Winagentd"
  RMDir "$INSTDIR"

SectionEnd

Function showCustomCM
   InstallOptions::initDialog $INI
   Pop $hwnd

   ReadINIStr $CMURI "$INSTDIR\winagentd.ini" "geral" "cm_uri"

   GetDlgItem $1 $HWND 1201
   SendMessage $1 ${WM_SETTEXT} 1 "STR:$CMURI"
   
   InstallOptions::show
FunctionEnd

Function leaveCustomCM

   ReadINIStr $CMURI $INI "Field 2" "State"

   WriteINIStr "$INSTDIR\winagentd.ini" "geral" "cm_uri" "$CMURI"

   ; prompt user, and if they select no, go to NoRunAgent
   MessageBox MB_YESNO|MB_ICONQUESTION \
             "The agent was installed. Would you like to run now?" \
             IDNO NoRunAgent
     Exec '"$INSTDIR\winagentd.exe"'
   NoRunAgent:

FunctionEnd

Function .onInit
	InitPluginsDir
	GetTempFileName $INI $PLUGINSDIR
	File /oname=$INI "nsi_winagentd_cm.ini"
FunctionEnd

