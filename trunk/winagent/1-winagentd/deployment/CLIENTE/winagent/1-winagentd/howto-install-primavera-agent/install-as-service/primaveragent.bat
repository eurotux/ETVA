@echo off

C:
chdir C:\cygwin\bin

bash --login -i -c "(cd /cygdrive/c/Users/Administrator/winagentd; perl primaveraagentd.pl >/dev/null 2>&1)"
