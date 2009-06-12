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
To perform time reporting, simply click the cell representing the project and day you wish to report for, and enter the number of hours worked and a description of the work performed. It may also be required to select one or more tags. To select multiple tags, hold the Ctrl key while selecting.
</p>

");

        $this->add("Project visibility", "
<p>
By default, only only newly created projects and projects where you have recently filed hours are visible on the page. To show all projects, click the «Show all projects» checkbox at the top of the registration page.
</p>
");
        
        $this->add("More on tags", "
<p>
Tags are central to the reporting capabilities of TimeSafe. Tags make it possible to locate all billable hors worked on any project. Tags can be divided into tag groups, where at most one tag from a specified group can be selected. These tag groups can optionally require that a tag in the group must be selected.
</p>
");
        
        $this->add("Error checking", "
<p>
TimeSafe tries to detect invalidly reported hours. The error checks include making sure that no incorrect tag combination has been filled in, that a proper description has been made, and so on. If an error has been detected, the faulty cell is marked in red, and if you click on the cell, the exact problem is described in the popup window.
</p>
");
                       
        $this->add("Speed", "
<p>
TimeSafe requires a standards compliant browser to function at all. Because it performs a lot of table manipulation in JavaScript, it will be extremely slow on browsers where such operations are slow, typically on older browsers. If TimeSafe is running slowly, consider updating to the latest browser version.
</p>
");

        $this->add("Administrating TimeSafe", "
<p>
There are only two types of administrative action possible in TimeSafe - changing tag groups and changing tags. The project list is imported from Egs and can not be edited here.
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