<?php

  /**
   Protection against bad includes.
  */
if (!defined("IS_MAIN_PAGE")) {
    return;
}

define('CONFIG_FILE_PATH', 'config.php');

class checks{
    function configFileExists(){
        return file_exists(CONFIG_FILE_PATH);
    }
}


class InstallApp
extends Application
{

    function check_dependencies()
    {
        $res = null;
        
        if(!class_exists("PDO")) {
            $res[] = "<span class='error'>The PDO library is missing. You can install it by writing <code>pecl install PDO</code> at the command line.</span>";
        }

        return $res;
        
    }
    
    function writeMenu($controller)
    {
        return "";
    }
    
    function view()
    {
        
        $this->writeHeader("Install","");
        
        $dep_str="";
        $dep = $this->check_dependencies();
        if (count($dep)) {
            $dep_str = "<p>The following problems have been detected with your server setup.: </p><p>" . implode("</p><p>", $dep) ." </p>";
        }

?>
<div class='content_install'>
<div class='content_install_inner'>
<h2>Install software</h2>

 <?= $dep_str; ?>
<p>
This application has not been installed. Please fill out the following form to install it. All fields are required.
</p>
<form action="" name="dbDetailForm" method="post" onSubmit='return installCheckFields();'>
<input type='hidden' name='action' value='install'/>
<table class='striped'>
	<thead>
		<tr>
			<th colspan="2">Database Details</th>
		</tr>
	</thead>
	<tbody>
		<tr>
   <td align="right"><label for="dsn">DSN</label></td>
			<td align="left"><input type="text" id="dsn" name="dsn"
				size="80" class="required"
				value="<?= param('dsn',"pgsql:dbname=DATABASE;host=localhost;user=USERNAME;password=PASSWORD"); ?>"> <span
				class="error" id="dsn_error"></span></td>
		</tr>
        </tbody>
</table>

<div class='button_list'>
<p>
<button type='button' onclick='installDbCheck();'>Test database...</button>
<span id='db_notification'><?= param('db_notification','');?></span>
</p>
<p>
<button>Install!</button>
</p>
</div>

</form>        
<script>
stripe();
</script>
</div>
</div>
<?php
  $this->writeFooter();
        
    }

    function db_check()
    {
        if (!db::init(param('dsn',''))) {
            echo  "<span class='error'>" . db::getError()."</span>";
        } else {
            echo "Database details ok!";
        }
    }

    function install() 
    {

        $ok = true;
        
        $dep = $this->check_dependencies();
        if (count($dep)) {
            $ok = false;
        }
        else {
            
            if (!db::init(param('dsn',''))) {
                $_REQUEST['db_notification'] = "<span class='error'>" . db::getError()."</span>";
                $ok = false;
            }
        }
        
        if (!$ok) {
            $this->view();
            return;
        }
        
        $this->writeHeader("install","");
        
        foreach( explode(';', file_get_contents('./static/schema.sql')) as $sql) {
            db::query($sql);
        }

        $config = "<?php
define('DB_DSN', '".addSlashes(param('dsn',''))."');
?>";
        
        $write_ok = @file_put_contents("./config.php", $config);
        
        if (!$write_ok) {
            $uid = posix_getuid();
            $passwd = posix_getpwuid($uid);
            $username = $passwd['name'];
            
            $script_dir =  dirname($_SERVER['SCRIPT_FILENAME']);

            ?>

            Could not write configuration file. This probably means that the web server 
            does not have write privileges in the install directory. You can either add the 
            correct privileges using a command like <pre>chown -R <?=$username;?> <?= $script_dir ?></pre> and press the «Reload» button, or manually create the file <?= $_SERVER['DOCUMENT_ROOT']; ?>/config.php on the web server with the following contents:

<?php
            
            echo "<pre>";

            echo htmlEncode($config);
            
            echo "</pre>";
            
            $this->writeFooter();

            $ok = false;
            
        }
        
        if ($ok) {
            echo "<h2>Success!</h2>The installation is complete. Click <a href=''>here</a> to start using the application.";
        }
        
    }
    

    function main() 
    {
        if (!checks::configFileExists()) {
            $action = param('action','view');
              
            switch ($action) {
            case 'db_check':
                $this->db_check();
                break;
                
            case 'install':
                $this->install();
                break;
                
            default:
                $this->view();
                break;
                
            }
            exit(0);
        }
    }

}

$cmdb = new InstallApp();
$cmdb->main();

?>