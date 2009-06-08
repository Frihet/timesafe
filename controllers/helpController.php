<?php

/**
 The help page
 */
class helpController
extends Controller
{
	
    private $content="";
    private $action_items=array();
    

    function add($header, $text)
    {
        $count = count($this->action_items);

        $this->content .= "<h2><a name='$count'>$header</a></h2> $text";
        $this->action_items[] = "<a href='#$count'>".$header."</a>";
    }
    
    function get()
    {
        return $this->content;
    }
    
    function getActionMenu()
    {
        return $this->action_items;
    }
    

    function viewRun()
    {
        util::setTitle("TimeSafe help");
	
        $this->add("Introduction", "
<p>
TimeSafe is a simple tool for reporting hours worked. It is layed out like a spreadsheet, with one project per row and one day per column. 
</p>
");
        
        $this->add("Hour reporting", "
<p>
...
</p>
");
        
        $this->add("Error checking", "
<p>
TimeSafe tries to detect invalidly reported hours. The error checks include making sure that no incorrect tag combination has been filled in, that a proper description has been made, and so on. If an error has been detected, the faulty cell is marked in red, and if you click on the cell, the exact problem is described in the popup window.
</p>
");
                       
            $this->show($this->getActionMenu(),$this->get());
		
	}
	

	function isHelp()
	{
		return true;
		
	}
	
}


?>