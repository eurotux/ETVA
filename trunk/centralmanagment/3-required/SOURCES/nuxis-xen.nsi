; ebackup-client-installer.nsi
;
; This script is based on example1.nsi, but it remember the directory, 
; has uninstall support and (optionally) installs start menu shortcuts.
;
; It will install ebackup-client-installer.nsi into a directory that the user selects,

;--------------------------------
!include "nuxis-xen-version.nsh"

!define PRODUCT_NAME "Nuxis-Xen"
!define COMPANY_NAME "Eurotux Informática, S.A."

; The name of the installer
Name "${PRODUCT_NAME}"

VIProductVersion "${VERSION}"
VIAddVersionKey ProductName "${PRODUCT_NAME}"
VIAddVersionKey CompanyName "${COMPANY_NAME}"
VIAddVersionKey LegalCopyright "${COMPANY_NAME}"
VIAddVersionKey FileDescription "${PRODUCT_NAME} (Installation Launcher)"
VIAddVersionKey FileVersion ${VERSION}
VIAddVersionKey ProductVersion ${VERSION}

; The file to write
OutFile "nuxix-xen-guest.exe"

; The default installation directory
InstallDir $PROGRAMFILES\nuxis-xen-guest

; Registry key to check for directory (so if you install again, it will 
; overwrite the old one automatically)
InstallDirRegKey HKLM "Software\NSIS_Nuxis-xen-guest-installer" "Install_Dir"

; Request application privileges for Windows Vista
RequestExecutionLevel admin

;--------------------------------

; Includes
!include "WinMessages.nsh"
!include "EnvVarUpdate.nsh"
!include "TextFunc.nsh"
!include "FileFunc.nsh"
!include "WinVer.nsh"
!include "x64.nsh"

;--------------------------------

; Vars

Var PARAMETERS
Var DEBUG

;--------------------------------

; Pages

;Page components
Page directory
Page instfiles
;Page custom ": Edit Username/Password"

UninstPage uninstConfirm
UninstPage instfiles

;--------------------------------

; The stuff to install
Section ""

  SectionIn RO
 
  ; read parameters from commandline 
  ${GetParameters} $PARAMETERS

  ${GetOptions} "$PARAMETERS" "/DEBUG" $R0
  IfErrors noDebug
   IntOp $DEBUG 1 | 1
   goto +2
  noDebug:
   IntOp $DEBUG 0 & 0

  ; Set output path to the installation directory.
  SetOutPath $INSTDIR
  
  ; Put file there
  File "gplpv_2000_signed_${VERSION}.msi"
  File "gplpv_2003x32_signed_${VERSION}.msi"
  File "gplpv_2003x64_signed_${VERSION}.msi"  
  File "gplpv_Vista2008x32_signed_${VERSION}.msi"
  File "gplpv_Vista2008x64_signed_${VERSION}.msi"  
  File "gplpv_XP_signed_${VERSION}.msi"
;  File "NuxisGuestServer.exe"
  
  ; Write the installation path into the registry
  WriteRegStr HKLM SOFTWARE\nuxis-xen-guest "Install_Dir" "$INSTDIR"
  
  ; Write the uninstall keys for Windows
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\nuxis-xen-guest" "DisplayName" "nuxis-xen-guest"
  WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\nuxis-xen-guest" "UninstallString" '"$INSTDIR\uninstall.exe"'
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\nuxis-xen-guest" "NoModify" 1
  WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\nuxis-xen-guest" "NoRepair" 1
  WriteUninstaller "uninstall.exe"
  
  CreateDirectory "$SMPROGRAMS\nuxis-xen-guest"
  CreateShortCut "$SMPROGRAMS\nuxis-xen-guest\Uninstall.lnk" "$INSTDIR\uninstall.exe" "" "$INSTDIR\uninstall.exe" 0

  ; Remove $INSTDIR from PATH var
  ${EnvVarUpdate} $0 "PATH" "R" "HKLM" "$INSTDIR"

  ; Add $INSTDIR TO PATH var
  ${EnvVarUpdate} $0 "PATH" "A" "HKLM" "$INSTDIR"

   call installPVDrivers

  ${GetOptions} "$PARAMETERS" "/S" $R0

SectionEnd

;--------------------------------


Function installPVDrivers

VAR /GLOBAL PVDRIVERS

${If} ${RunningX64}
  ${If} ${IsWin7}
    StrCpy $PVDRIVERS "gplpv_Vista2008x64_signed_${VERSION}.msi"  
  ${ElseIf} ${IsWin2008R2}
    StrCpy $PVDRIVERS "gplpv_Vista2008x64_signed_${VERSION}.msi"  
  ${ElseIf} ${IsWinVista}
    StrCpy $PVDRIVERS "gplpv_Vista2008x64_signed_${VERSION}.msi"  
  ${ElseIf} ${IsWin2008}
    StrCpy $PVDRIVERS "gplpv_Vista2008x64_signed_${VERSION}.msi"  
  ${ElseIf} ${IsWin2003}
    StrCpy $PVDRIVERS "gplpv_2003x64_signed_${VERSION}.msi"  
  ${Else}
    MessageBox MB_OK "Not supported!"
    abort
  ${EndIf}
${Else}
  ${If} ${IsWin7}
    StrCpy $PVDRIVERS "gplpv_Vista2008x32_signed_${VERSION}.msi"
  ${ElseIf} ${IsWin2008R2}
    StrCpy $PVDRIVERS "gplpv_Vista2008x32_signed_${VERSION}.msi"
  ${ElseIf} ${IsWinVista}
    StrCpy $PVDRIVERS "gplpv_Vista2008x32_signed_${VERSION}.msi"
  ${ElseIf} ${IsWin2008}
    StrCpy $PVDRIVERS "gplpv_Vista2008x32_signed_${VERSION}.msi"
  ${ElseIf} ${IsWin2003}
    StrCpy $PVDRIVERS "gplpv_2003x32_signed_${VERSION}.msi"
  ${ElseIf} ${IsWinXp}
    StrCpy $PVDRIVERS "gplpv_XP_signed_${VERSION}.msi"
  ${ElseIf} ${IsWin2000}
    StrCpy $PVDRIVERS "gplpv_2000_signed_${VERSION}.msi"
  ${Else}
    MessageBox MB_OK "Not supported!"
    abort
  ${EndIf}
${EndIf} 

nsExec::ExecToLog 'msiexec /quiet /i "$INSTDIR\$PVDRIVERS"'
FunctionEnd



; Uninstaller

Section "Uninstall"
  
  ReadRegStr $R5 HKLM "SOFTWARE\OpenVPN" ""

  nsExec::ExecToLog 'msiexec /quiet /uninstall "$INSTDIR\AvamarClient-windows-x86-6.0.101-66.msi"'
  nsExec::ExecToLog '$R5\Uninstall.exe /S'

  ; Remove registry keys
  DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\nuxis-xen-guest"
  DeleteRegKey HKLM SOFTWARE\NSIS_nuxis-xen-guest

  ; Remove $INSTDIR from PATH var
  ${un.EnvVarUpdate} $0 "PATH" "R" "HKLM" "$INSTDIR"

  ; Remove files and uninstaller
  Delete "$INSTDIR\*.msi"
  Delete $INSTDIR\uninstall.exe

  ; Remove shortcuts, if any
  Delete "$SMPROGRAMS\nuxis-xen-guest\*.*"

  ; Remove directories used
  RMDir "$SMPROGRAMS\nuxis-xen-guest"
  RMDir "$INSTDIR"

SectionEnd

Function .onInit
  InitPluginsDir
FunctionEnd

