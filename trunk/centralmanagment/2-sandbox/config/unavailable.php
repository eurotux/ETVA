<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<?php $path = preg_replace('#/[^/]+\.php5?$#', '', isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : '')) ?>
<?php 

$model = exec("grep '  acronym: ' ../apps/app/config/config.yml | sed 's/  acronym: //g'");

$this->headers = array();
foreach($_SERVER as $i=>$val) {  
    if (strpos($i, 'HTTP_') === 0) {  
        $name = str_replace(array('HTTP_', '_'), array('', '-'), $i);  
        $this->headers[$name] = $val;  
    }
}

$pattern = '/pt/';
if (preg_match($pattern, $this->headers['ACCEPT-LANGUAGE'])) {
    $msg = 'Website temporariamente indisponível';
    $sugestion = 'Por favor tente novamente dentro de instantes...';
    

}else{
    $msg = 'Website Temporarily Unavailable';
    $sugestion = 'Please try again in a few seconds...';
}
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=<?php echo sfConfig::get('sf_charset', 'utf-8') ?>" />
<meta name="title" content="symfony project" />
<meta name="robots" content="index, follow" />
<meta name="description" content="symfony project" />
<meta name="keywords" content="symfony, project" />
<meta name="language" content="en" />
<title>symfony project</title>

<link rel="shortcut icon" href="/favicon.ico" />
<!--<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $path ?>/sf/sf_default/css/screen.css" />-->
<link rel="stylesheet" type="text/css" href="<?php echo $path ?>/css/main.css" />
<!--[if lt IE 7.]>
<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $path ?>/sf/sf_default/css/ie.css" />
<![endif]-->
<style>
body{ 
    text-align: center; 
}

.sfTContainer{
    position: relative;
/*    text-align: left;*/
    width: 515px;
    margin: 0 auto;
    padding: 0;
    margin-top: 215px;
}

.sfTMessageContainer .sfTMessageWrap
{
    float: left;
    width: 440px;
}

.sfTAlert
{
    padding-bottom: 35px;
/*    padding-left: 10px;*/
    border: 1px solid #FFFFFF;
    border-bottom-color: red;
    border-top-color: red;
    /*border-bottom-color: #F0B17C;*/
    /*border-top-color: #F0B17C;*/
    display: inline-block;
}


.sfTMessageContainer .sfTMessageWrap h1
{
    color: #503512;
    font-weight: normal;
    font-size: 165%;
    padding: 0;
    padding-top: 35px;
    padding-bottom: 20px;
    margin: 0;
    line-height: 100%;
}

.sfTMessageContainer .sfTMessageWrap h5
{
    font-weight: normal;
    font-size: 100%;
    padding: 0;
    margin: 0;
}
</style>
</head>
<body>
<div class="header-login <?php echo $model?>">
</div>
<div class="sfTContainer">
  <div class="sfTMessageContainer sfTAlert">

    <!--<img alt="page not found" class="sfTMessageIcon" src="<#?php echo $path ?>/sf/sf_default/images/icons/tools48.png" height="48" width="48" />-->
    <div class="sfTMessageWrap">
      <h1><?php echo $msg ?></h1>
      <h5><?php echo $sugestion ?></h5>
    </div>
  </div>

</div>
<div class="footer-login"></div>

</body>
</html>
