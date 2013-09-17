md c:\bin

copy primaveragent.bat c:\bin

copy SRVANY.exe c:\bin

reg add "HKLM\SYSTEM\CurrentControlSet\Services\primavera-agent" /v "ObjectName" /d ".\Administrator" /t REG_SZ

reg add "HKLM\SYSTEM\CurrentControlSet\Services\primavera-agent\Parameters" /v "Application" /d "c:\bin\primaveragent.bat" /t REG_SZ
reg add "HKLM\SYSTEM\CurrentControlSet\Services\primavera-agent\Parameters" /v "AppDirectory" /d "c:\bin" /t REG_SZ


sc create primavera-agent start= auto binPath= "c:\bin\SRVANY.EXE"
sc start primavera-agent

