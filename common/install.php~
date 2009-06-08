<?php

  /**
   Protection against bad includes.
  */
if (!defined("FREECMDB")) {
    return;
}


define('CONFIG_FILE_PATH', 'config.php');

class checks{
    function configFileExists(){
        return file_exists(CONFIG_FILE_PATH);
    }
    
}


class FreeCMDBInstall
{

    function head() 
    {
          header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
        <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
                <link rel="stylesheet" href="static/FreeCMDB.css" type="text/css" media="screen,projection" />
                <script type="text/javascript" src="common/static/jquery.js"></script>
                <script type="text/javascript" src="common/static/FreeCMDB.js"></script>
                <title>Install FreeCMDB</title>
        </head>
        <body>
<div class="content_install">
<div class="content_inner">

<?php      

    }
    
    function check_dependencies()
    {
        $res = null;
        
        if(! (@include_once 'Image/GraphViz.php') || !class_exists("Image_GraphViz")) {
            $res[] = "<span class='error'>The Image_Graphviz library is missing. You can install it by writing <code>pear install Image_Graphviz</code> at the command line.</span>";
        }

        if(!class_exists("PDO")) {
            $res[] = "<span class='error'>The PDO library is missing. You can install it by writing <code>pecl install PDO</code> at the command line.</span>";
        }

        return $res;
        
    }

    
    function view()
    {
        $this->head();
        
        $dep_str="";
        $dep = $this->check_dependencies();
        if (count($dep)) {
            $dep_str = "<p>The following problems have been detected with your server setup.: </p><p>" . implode("</p><p>", $dep) ." </p>";
        }
        

?>
<h2>Install FreeCMDB</h2>

 <?= $dep_str; ?>
<p>
FreeCMDB has not been installed. Please fill out the following form to install FreeCMDB. All fields are required.
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
	<thead>
		<tr>
			<th colspan="2">Administrator Information</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td align="right"><label for="admin_username">Administrator username</label></td>
			<td align="left"><input type="text" id="admin_username"
				name="admin_username" size="60" class="required"
				value="<?= param('admin_username','');?>">
			<span class="error" id="admin_username_error"></span></td>
		</tr>
		<tr>
			<td align="right"><label for="admin_password">Administrator password</label></td>
			<td align="left"><input type="password" id="admin_password"
				name="admin_password" size="60" class="required"
				value="<?= param('admin_password');?>">
			<span class="error" id="admin_password_error"></span></td>
		</tr>
		<tr>
                <td align="right"><label for="admin_password2">Administrator password (Again)</label></td>
			<td align="left"><input type="password" id="admin_password2"
				name="admin_password2" size="60" class="required"
				value="<?= param('admin_password2',''); ?>">
			<span class="error" id="admin_password2_error"></span></td>
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
<?php
        $this->tail();
        
    }


    function tail()
    {
        ?>
</div>
</div>

        </body>
</html>
    <?php
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
            
            $admin_password2 = param('admin_password2');
            $admin_password = param('admin_password');
            $admin_username = param('admin_username');
            
            if ($admin_password2 == '' ||
                $admin_password2 == '' ||
                $admin_password2 == '' ) {
                $ok = false;
            }
        }
        
        
        if (!$ok) {
            $this->view();
            return;
        }
        
        $this->head();
        
        foreach( explode(';', file_get_contents('./static/schema.sql')) as $sql) {
            db::query($sql);
        }

        db::query("insert into ci_user (username, fullname, password) values (:name, 'Admin', :passwd)",
                  array(":name"=>$admin_username,":passwd"=>$admin_password));
        
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
            does not have write privileges in the FreeCMDB directory. You can either add the 
            correct privileges using a command like <pre>chown -R <?=$username;?> <?= $script_dir ?></pre> and press the «Reload» button, or manually create the file <?= $_SERVER['DOCUMENT_ROOT']; ?>/config.php on the web server with the following contents:

<?php
            
            echo "<pre>";

            echo htmlEncode($config);
            
            echo "</pre>";
            
            $this->tail();

            $ok = false;
            
        }
        
        if ($ok) {
            echo "<h2>Success!</h2>The installation is complete. Click <a href=''>here</a> to start using FreeCMDB.";
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

$cmdb = new FreeCMDBInstall();
$cmdb->main();

?>