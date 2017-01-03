; primaveraagentd.nsi
;
; This script is based on example1.nsi, but it remember the directory, 
; has uninstall support and (optionally) installs start menu shortcuts.
;
; It will install primaveraagentd.nsi into a directory that the user selects,

;--------------------------------

; The name of the installer
Name "Primaveraagentd"

; The file to write
OutFile "primaveraagentdinstaller.exe"

; The default installation directory
InstallDir $PROGRAMFILES\primaveraagentd

; Registry key to check for directory (so if you install again, it will 
; overwrite the old one automatically)
InstallDirRegKey HKLM "Software\NSIS_Primaveraagentd" "Install_Dir"

; Request application privileges for Windows Vista
RequestExecutionLevel admin

;--------------------------------

; Includes

!include "WinMessages.nsh"
!include "EnvVarUpdate.nsh"

;--------------------------------

; Vars

Var HWND
Var INI
VAR CMURI
VAR USER
VAR PASSWD
VAR SAUSER
VAR SAPASSWD
VAR INSTANCE

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
  File "primaveraagentd.exe"
  File "primaveraagentd.ini"

  File "cygcrypt-0.dll"
  File "cyggcc_s-1.dll"
  File "cygperl5_10.dll"
  File "cygssp-0.dll"
  File "cygwin1.dll"

  File "primaveraconsole\primaveraconsole\bin\Release\primaveraconsole.exe"

  File "primaveraconsole\primaveraconsole\bin\Release\VBA.dll"
  File "primaveraconsole\primaveraconsole\bin\Release\Interop.AdmBE750.dll"
  File "primaveraconsole\primaveraconsole\bin\Release\Interop.AdmBS750.dll"
  File "primaveraconsole\primaveraconsole\bin\Release\Interop.ErpBS750.dll"
  File "primaveraconsole\primaveraconsole\bin\Release\Interop.StdBE750.dll"
  
  ; Write the installation path into the registry
  WriteRegStr HKLM SOFTWARE\Primaveraagentd "Install_Dir" "$INSTDIR"
  
  ; Write the uninstall keys for Windows
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Primaveraagentd" "DisplayName" "Primaveraagentd"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Primaveraagentd" "UninstallString" '"$INSTDIR\uninstall.exe"'
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Primaveraagentd" "NoModify" 1
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Primaveraagentd" "NoRepair" 1
  WriteUninstaller "uninstall.exe"
  
  CreateDirectory "$SMPROGRAMS\Primaveraagentd"
  CreateShortCut "$SMPROGRAMS\Primaveraagentd\Uninstall.lnk" "$INSTDIR\uninstall.exe" "" "$INSTDIR\uninstall.exe" 0
  CreateShortCut "$SMPROGRAMS\Primaveraagentd\Primaveraagentd.lnk" "$INSTDIR\primaveraagentd.exe" "" "$INSTDIR\primaveraagentd.exe" 0

  CreateShortCut "$SMSTARTUP\Primaveraagentd.lnk" "$INSTDIR\primaveraagentd.exe" "" "$INSTDIR\primaveraagentd.exe" 0

  ; Remove $INSTDIR from PATH var
  ${EnvVarUpdate} $0 "PATH" "R" "HKLM" "$INSTDIR"

  ; Add $INSTDIR TO PATH var
  ${EnvVarUpdate} $0 "PATH" "A" "HKLM" "$INSTDIR"

SectionEnd

;--------------------------------

; Uninstaller

Section "Uninstall"
  
  ; Remove registry keys
  DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Primaveraagentd"
  DeleteRegKey HKLM SOFTWARE\NSIS_Primaveraagentd

  ; Remove $INSTDIR from PATH var
  ${un.EnvVarUpdate} $0 "PATH" "R" "HKLM" "$INSTDIR"

  ; Remove files and uninstaller
  Delete $INSTDIR\primaveraagentd.exe
  Delete $INSTDIR\primaveraagentd.ini
  Delete $INSTDIR\uninstall.exe
  Delete $INSTDIR\cygcrypt-0.dll
  Delete $INSTDIR\cyggcc_s-1.dll
  Delete $INSTDIR\cygperl5_10.dll
  Delete $INSTDIR\cygssp-0.dll
  Delete $INSTDIR\cygwin1.dll

  Delete $INSTDIR\primaveraconsole.exe

  Delete $INSTDIR\VBA.dll
  Delete $INSTDIR\Interop.AdmBE750.dll
  Delete $INSTDIR\Interop.AdmBS750.dll
  Delete $INSTDIR\Interop.ErpBS750.dll
  Delete $INSTDIR\Interop.StdBE750.dll

  ; Remove shortcuts, if any
  Delete "$SMPROGRAMS\Primaveraagentd\*.*"
  Delete "$SMSTARTUP\Primaveraagentd.lnk"

  ; Remove directories used
  RMDir "$SMPROGRAMS\Primaveraagentd"
  RMDir "$INSTDIR"

SectionEnd

Function showCustomCM
   InstallOptions::initDialog $INI
   Pop $hwnd

   ReadINIStr $CMURI "$INSTDIR\primaveraagentd.ini" "geral" "cm_uri"

   GetDlgItem $1 $HWND 1201
   SendMessage $1 ${WM_SETTEXT} 1 "STR:$CMURI"
   
   ReadINIStr $USER "$INSTDIR\primaveraagentd.ini" "geral" "username"

   GetDlgItem $1 $HWND 1203
   SendMessage $1 ${WM_SETTEXT} 3 "STR:$USER"
   
   ReadINIStr $PASSWD "$INSTDIR\primaveraagentd.ini" "geral" "password"

   GetDlgItem $1 $HWND 1205
   SendMessage $1 ${WM_SETTEXT} 5 "STR:$PASSWD"
   
   ReadINIStr $SAUSER "$INSTDIR\primaveraagentd.ini" "geral" "sa_username"

   GetDlgItem $1 $HWND 1207
   SendMessage $1 ${WM_SETTEXT} 7 "STR:$SAUSER"
   
   ReadINIStr $SAPASSWD "$INSTDIR\primaveraagentd.ini" "geral" "sa_password"

   GetDlgItem $1 $HWND 1209
   SendMessage $1 ${WM_SETTEXT} 9 "STR:$SAPASSWD"
   
   ReadINIStr $INSTANCE "$INSTDIR\primaveraagentd.ini" "geral" "instance"

   GetDlgItem $1 $HWND 1211
   SendMessage $1 ${WM_SETTEXT} 11 "STR:$INSTANCE"
   
   InstallOptions::show
FunctionEnd

Function leaveCustomCM

   ReadINIStr $CMURI $INI "Field 2" "State"
   WriteINIStr "$INSTDIR\primaveraagentd.ini" "geral" "cm_uri" "$CMURI"

   ReadINIStr $USER $INI "Field 4" "State"
   WriteINIStr "$INSTDIR\primaveraagentd.ini" "geral" "username" "$USER"

   ReadINIStr $PASSWD $INI "Field 6" "State"
   WriteINIStr "$INSTDIR\primaveraagentd.ini" "geral" "password" "$PASSWD"

   ReadINIStr $SAUSER $INI "Field 8" "State"
   WriteINIStr "$INSTDIR\primaveraagentd.ini" "geral" "sa_username" "$SAUSER"

   ReadINIStr $SAPASSWD $INI "Field 10" "State"
   WriteINIStr "$INSTDIR\primaveraagentd.ini" "geral" "sa_password" "$SAPASSWD"

   ReadINIStr $INSTANCE $INI "Field 12" "State"
   WriteINIStr "$INSTDIR\primaveraagentd.ini" "geral" "instance" "$INSTANCE"

   WriteINIStr "$INSTDIR\primaveraagentd.ini" "geral" "INSTALLDIR" "$INSTDIR"

   ; prompt user, and if they select no, go to NoRunAgent
   ;MessageBox MB_YESNO|MB_ICONQUESTION \
   ;          "The agent was installed. Would you like to run now?" \
   ;          IDNO NoRunAgent
   ;  Exec '"$INSTDIR\primaveraagentd.exe"'
   ;NoRunAgent:
   
   MessageBox MB_YESNO|MB_ICONQUESTION \
             "The agent was installed. Would you like reboot now?" \
             IDNO NoReboot
     SetRebootFlag true
     reboot
   NoReboot:

FunctionEnd

Function .onInit
	InitPluginsDir
	GetTempFileName $INI $PLUGINSDIR
	File /oname=$INI "nsi_primaveraagentd_cm.ini"
FunctionEnd

