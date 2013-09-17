timeout /t 4 > nul
devcon disable usb\root_hub
timeout /t 4 > nul
devcon enable usb\root_hub
