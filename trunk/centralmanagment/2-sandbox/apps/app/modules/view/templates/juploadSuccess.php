<applet code="wjhk.jupload2.JUploadApplet"
    archive = "/js/jupload/wjhk.jupload.jar, /js/jupload/jakarta-commons-oro.jar, /js/jupload/jakarta-commons-net.jar"
    width="640" height="300" name="FTP Upload"
    alt="The java pugin must be installed."    
   mayscript >
   <!-- No parameter is mandatory. We don't precise the UploadPolicy, so
        DefaultUploadPolicy is used. The applet behaves like the original
        JUpload. (jupload v1) -->
   <param name="postURL" value="<?php echo ($postUrl); ?>"/>
   Java 1.5 or higher plugin required.
<param name="nbFilesPerRequest" value="1"/>
<param name="lang" value="<?php echo($lang); ?>"/>
<param name="debugLevel" value="-1"/>
<param name="afterUploadURL" value="javascript:top.Ext.getCmp('view-iso-grid').getStore().reload();"/>
</applet>