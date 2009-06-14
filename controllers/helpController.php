<?php

/**
 The help page. Just display a bunch of info.
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
<p>
If you need to report hours to the same project int the same day more than once, e.g. with a different set of tags, you can create an extra line by clicking the «+» right next to the project name. This will create a new, empty row in the form.
</p>
");

        $this->add("Project visibility", "
<p>
By default, only newly created projects and projects where you have recently filed hours are visible on the page. To show all projects, click the «Show all projects» checkbox at the top of the registration page.
</p>
");
        
        $this->add("More on tags", "
<p>
Tags are central to the reporting capabilities of TimeSafe. They make it possible to e.g. locate all billable hours worked on any project and other cross-project groupings of hours. Tags can be divided into tag groups, where at most one tag from a specified group can be selected. These tag groups can optionally require that a tag in the group must be selected.
</p>
");
        
        $this->add("Error checking", "
<p>
TimeSafe tries to detect invalidly reported hours. The error checks include making sure that no incorrect tag combination has been filled in, that a proper description has been made, and so on. If an error has been detected, the faulty cell is marked in red, and if you click on the cell, the exact problem is described in the popup window.
</p>
");
                       
        $this->add("Speed", "
<p>
TimeSafe is very JavaScript intensive. It requires a standards compliant browser to function at all. Because it performs a lot of table manipulation in JavaScript, it will be extremely slow on browsers where such operations are slow, typically on older browsers. If TimeSafe is running slowly, consider updating to the latest browser version.
</p>
");

        $this->add("Administrating TimeSafe", "
<p>
There are only two types of administrative action possible in TimeSafe - changing tag groups and changing tags. The project list is imported from Egs and can not be edited here.
</p>
<p>
A tag can be visible for all project, all external projects, all internal projects, or only one specific project. Each tag has a dropdown where you can chose tag visibility.
</p>
<p>
Some tags are almost always used. These can be made recommended, in which case TimeSafe will show a warning if the user has not chosen the tag. This can be used e.g. to create a tag to mark external hours as billable.
</p>
<p>
Tags can be put into groups where at most one tag may be chosen. It can be made mandatory to pick one tag in a group, or it can be optional. Toggle the 'required' checkbox of a tag group to choose this. 
</p>

");
        

        $this->add("Time report integration", "
<p>
TimeSafe integration with time report is currently rather kludgy. A future version of the time reporting tool will be significantly more powerful and extensible, but as of yet, time report integration relies on naming tags and tag groups according to specific patterns.

<ul>
<li>
40 % overtime tags must match the perl regexp /^40 *% *overtime/i.
</li>
<li>
100 % overtime tags must match the perl regexp /^100 *% *overtime/i.
</li>
<li>
Work leave must match the perl regexp /^(work *leave|avspasering)$/i.
</li>
<li>
Vacation must match the perl regexp /^vacation$/i.
</li>
<li>
The group name for all types of illness must match the perl regexp /^(illness|sick.*)$/i.
</li>
<li>
The group name for all types of paying non-work must match the perl regexp /^paying non[- ]?work$/i.
</ul>
Note that all these matches are case insensitive.
</p>
");
        
        $this->add("Future features", "
<p>
There are loads of features in the TimeSafe pipeline. Before requesting a feature, please make sure that this issue is not reported by checking <a href='https://projects.freecode.no/projects/timesafe/issues'>the issue tracker</a>.
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