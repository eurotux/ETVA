<APPLET CODE=VncViewer.class ARCHIVE=/js/VncViewer/VncViewer.jar WIDTH=640 HEIGHT=480>
<param name="HOST" value="<?php echo $host ?>">
<param name="PORT" value="<?php echo $port ?>">
<param name="Share desktop" value="Yes">
<param name="SocketFactory" value="AuthHTTPConnectSocketFactory">
<param name="PROXYHOST1" value="<?php echo $proxyhost1 ?>">
<param name="PROXYPORT1" value="80">
<param name="PROXYUSERNAME" value="<?php echo($username); ?>">
<param name="PROXYPASSWORD" value="<?php echo($token); ?>">
</APPLET>
