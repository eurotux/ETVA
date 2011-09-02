<applet code="VncViewer.class" archive="/js/VncViewer/VncViewer.jar" width="720" height="620">
<param name="HOST" value="<?php echo $host ?>">
<param name="PORT" value="<?php echo $port ?>">
<param name="Share desktop" value="Yes">
<param name="Scaling factor" value="Auto">
<param name="Open new window" value="Yes">
Java 1.5 or higher plugin required.
<param name="SocketFactory" value="AuthHTTPConnectSocketFactory">
<param name="PROXYHOST1" value="<?php echo $proxyhost1 ?>">
<param name="PROXYPORT1" value="80">
<param name="PROXYUSERNAME" value="<?php echo($username); ?>">
<param name="PROXYPASSWORD" value="<?php echo($token); ?>">
</applet>