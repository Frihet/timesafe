<?php

require_once('tr_admin_editor.php');
require_once('tr_editor.php');

/** Base class for all editors. The editor takes on the role of view
 and controller and MVC parlance.
*/
class Editor
{

    /** Check the task param and try to run the corresponding
     function, if it exists. Gives an error otherwise.
    */
    function run() 
    {
        $task = param('task','view');
        
        $str = "{$task}Write";
        if(method_exists($this, $str)) {
            $this->$str();
        }
        else {
            echo "Unknown task: $task";
        }
    }

    /** A function to output the basic page layout given a set of menu
     items and content for the main pane.
    */
    function show($content)
    {
        echo "<div class='content'>";
        echo "<div class='content_inner'>";
		
        echo $content;

        echo "<div class='content_post'>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }

    function isAdmin() 
    {
	    return false;
    }
    
    function isHelp() 
    {
	    return false;
    }
    
	
}

class HelpEditor
extends Editor
{
	
	function viewWrite()
	{
		$content = "There is no help";
		
		$this->show($content);
		
	}
	

	function isHelp()
	{
		return true;
                
	}
	
}


?>