<?php

class MyInstallApp
extends InstallApplication
{
    
    function getDsn()
    {
        return array('default','egs');
    }
}


$app = new MyInstallApp();
$app->main();

?>