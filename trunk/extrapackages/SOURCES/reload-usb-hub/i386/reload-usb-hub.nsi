; reload-usb-hub.nsi
;
; This script is based on example1.nsi, but it remember the directory, 
; has uninstall support and (optionally) installs start menu shortcuts.
;
; It will install reload-usb-hub.nsi into a directory that the user selects,

;--------------------------------

; The name of the installer
Name "reload-usb-hub"

; The file to write
OutFile "reload-usb-hub.exe"

; The default installation directory
InstallDir $PROGRAMFILES\reload-usb-hub

; Registry key to check for directory (so if you install again, it will 
; overwrite the old one automatically)
InstallDirRegKey HKLM "Software\NSIS_reload-usb-hub" "Install_Dir"

; Request application privileges for Windows Vista
RequestExecutionLevel admin

;--------------------------------

; Includes

!include "WinMessages.nsh"
!include "EnvVarUpdate.nsh"
!include "TextFunc.nsh"
!include "FileFunc.nsh"

;--------------------------------

; Vars

;Var INI

;--------------------------------

; Pages

;Page components
Page directory
Page instfiles

UninstPage uninstConfirm
UninstPage instfiles

;--------------------------------

; The stuff to install
Section ""

  SectionIn RO
 
  ; Set output path to the installation directory.
  SetOutPath $INSTDIR
  
  ; Put file there
  ;File "instsrv.exe"
  File "SRVANY.EXE"
  File "reload_usb_hub.bat"
  File "devcon.exe"

  ; Write the installation path into the registry
  WriteRegStr HKLM SOFTWARE\reload-usb-hub "Install_Dir" "$INSTDIR"
  
  ; Write the uninstall keys for Windows
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\reload-usb-hub" "DisplayName" "reload-usb-hub"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\reload-usb-hub" "UninstallString" '"$INSTDIR\uninstall.exe"'
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\reload-usb-hub" "NoModify" 1
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\reload-usb-hub" "NoRepair" 1

  WriteRegStr HKLM "SYSTEM\CurrentControlSet\Services\reload-usb-hub" "ObjectName" "LocalSystem"
  WriteRegStr HKLM "SYSTEM\CurrentControlSet\Services\reload-usb-hub\Parameters" "Application" '"$INSTDIR\reload_usb_hub.bat"'
  WriteRegStr HKLM "SYSTEM\CurrentControlSet\Services\reload-usb-hub\Parameters" "AppDirectory" '"$INSTDIR"'

  WriteUninstaller "uninstall.exe"
  
  CreateDirectory "$SMPROGRAMS\reload-usb-hub"
  CreateShortCut "$SMPROGRAMS\reload-usb-hub\Uninstall.lnk" "$INSTDIR\uninstall.exe" "" "$INSTDIR\uninstall.exe" 0

  ; Remove $INSTDIR from PATH var
  ${EnvVarUpdate} $0 "PATH" "R" "HKLM" "$INSTDIR"

  ; Add $INSTDIR TO PATH var
  ${EnvVarUpdate} $0 "PATH" "A" "HKLM" "$INSTDIR"

  ;nsExec::ExecToLog '$INSTDIR\instsrv.exe reload-usb-hub "$INSTDIR\SRVANY.EXE"'
  nsExec::ExecToLog 'sc create reload-usb-hub start= auto binPath= "$INSTDIR\SRVANY.EXE"'
  nsExec::ExecToLog 'sc start reload-usb-hub'

SectionEnd

;--------------------------------

; Uninstaller

Section "Uninstall"
  
  ;nsExec::ExecToLog '$INSTDIR\instsrv.exe reload-usb-hub remove'
  nsExec::ExecToLog 'sc stop reload-usb-hub'
  nsExec::ExecToLog 'sc delete reload-usb-hub'

  ; Remove registry keys
  DeleteRegKey HKLM "SYSTEM\CurrentControlSet\Services\reload-usb-hub\Parameters"
  DeleteRegKey HKLM "SYSTEM\CurrentControlSet\Services\reload-usb-hub"
  DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\reload-usb-hub"
  DeleteRegKey HKLM SOFTWARE\NSIS_reload-usb-hub

  ; Remove $INSTDIR from PATH var
  ${un.EnvVarUpdate} $0 "PATH" "R" "HKLM" "$INSTDIR"

  ; Remove files and uninstaller
  Delete $INSTDIR\reload_usb_hub.bat
  Delete $INSTDIR\devcon.exe
  Delete $INSTDIR\instsrv.exe
  Delete $INSTDIR\SRVANY.EXE
  Delete $INSTDIR\uninstall.exe

  ; Remove shortcuts, if any
  Delete "$SMPROGRAMS\reload-usb-hub\*.*"

  ; Remove directories used
  RMDir "$SMPROGRAMS\reload-usb-hub"
  RMDir "$INSTDIR"

SectionEnd

Function .onInit

  System::Call "kernel32::GetCurrentProcess() i .s"
  System::Call "kernel32::IsWow64Process(i s, *i .r0)"
  IntCmp $0 0 init32bits

  ; we are running on 64-bit windows
  
  Messagebox MB_OK|MB_ICONSTOP "Sorry, this installation package is for 32-bit Windows version only."
  Abort

init32bits:

FunctionEnd

