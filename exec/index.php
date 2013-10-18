<?php
#a:admin or not/m:module/f:function/x:action/p:params
#RewriteRule	^\/(admin\/)?([a-z][_0-9a-z]+)\/(.+)\/([a-z][_0-9a-z]+)\.(view|do|ajax|jsp|csp)$	/index.php?a=$1&m=$2&f=$4&x=$5&p=$3	[L,NC,PT,QSA]
$strAdmin = strtolower( $_GET["a"] );
$strModule = strtolower( $_GET["m"] );
$strFunction = strtolower( $_GET["f"] );
$strAction = strtolower( $_GET["x"] );
$strParams = $_GET["p"];
?>