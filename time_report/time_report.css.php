<?php

header('Content-type: text/css');

if (is_dir('fctoolkit-dev')) {
    require_once ('fctoolkit-dev/php/FCToolkit.php');
    require_once ('fctoolkit-dev/php/Egs.php');
} else {
    require_once ('FCToolkit.php');
    require_once ('Egs.php');
}

require_once( "config.php");

$egs = new Egs ();

?>
img {
  margin-left: auto;
  margin-right: auto;
  display:block;
}

div.version
{
  text-align: center;
  font-style: italic;
  color: #444;
  font-size: xx-small;
}

em.bad {
    color: #aa4444;
}

em.good {
    color: #44aa44;
}

<?php

$tasks = $egs->get_hour_types();

foreach(array("Billable", "Nonbillable") as $billid => $bill) {
    
    foreach($tasks as $tagids => $task) {

        $col = config_get_color_str($billid, $tagids);
        
        $task_mangled = str_replace( "%", "p", str_replace(" ", "_", $task ));
        
echo " 
           
tr.hour_{$bill}_{$task_mangled}
{
    background: #$col;
    font-size:  small;
}
 
tr.hour_{$bill}_{$task_mangled}_odd
{
    background: #$col;
    font-size:  small;
}

";
 
    }
}

?>

div.figure {
  float: left; 
  margin-bottom: 3em;
  text-align: center;
}

table.hour_list
{
  border-collapse: separate;
}

#spinner {
  position:fixed; 
  left: 50%; 
  top:50%;
  margin-top: -5em;
  margin-left: -1em;
  width: 10em;
  height: 2em;
  color: #333;
  background-color: #eeeeee;
  padding: 0.5em;
  border: 1px solid #aaa;
  display: none;
  text-align: center;
}

#spinner img {
  display:inline;
}
