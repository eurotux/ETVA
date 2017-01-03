@echo off

C:
chdir C:\cygwin\bin

bash --login -i -c "(cd Documents/winagent/1-winagentd; perl primaveraagentd.pl >/dev/null 2>&1)"
