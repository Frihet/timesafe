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
The easiest way to navigate in TimeSafe is usually to use the keyboard. When a cell is selected, use the arrow keys to move around between the different cells. When a cell is selected, a special window (called the sidebar) is opened, allowing you to tag your entry and write a description for it. You can use the tab key or the mouse to move from the focused cell to the sidebar. Press the escape key to close the sidebar.
</p>
<p>

If you need to report more than one activity belonging to the same project on the same day, e.g.  you need to report sick leave and attending a staff meeting on the same day, you can create an extra line by clicking the «+» right next to the project name. This will create a new, empty row in the form.
</p>
");

        $this->add("Copying or moving reported hours", "
<p>
TimeSafe supports simple drag-and-drop in order to allow you to move or copy reported entries. To copy the contents of a cell, press the control key and drag the hours from one cell to another. To move the hours, hold down the shift key instead while performing the operation. When copying or moving the hours, the description and tag selection is transferred over to the new cell.
</p>
<p>
Note that some browsers (including FireFox) automatically perform drag-and-drop when used on a selection without holding any modifier key. This only moves the hours, and not the tags or description. This is unfortunate, but the feature is outside the control of TimeSafe.
</p>
");
        

        $this->add("Project visibility", "
<p>
By default, only newly created projects and projects where you have recently filed hours are visible on the page. To show all open projects, click the «Show all projects» checkbox at the top of the registration page. TimeSafe never shows closed or archived projects. You will need to reopen them in egs in order to use them in TimeSafe.
</p>
");
        
        $this->add("More on tags", "
<p>
Tags are central to the reporting capabilities of TimeSafe. They make it possible to e.g. locate all billable hours performed in any project and other cross-project groupings of hours. Tags can be divided into tag groups, where at most one tag from a specified group can be selected. These tag groups can optionally require that a tag in the group must be selected. If an illegal combination of tags has been entered, an error message will be shown, and the cell will be marked in red.
</p>
");
        
        $this->add("Error checking", "
<p>
TimeSafe tries to detect invalidly reported hours. The error checks include making sure that no incorrect tag combination has been filled in, that a proper description has been made, and so on. If an error has been detected, the faulty cell is marked in red, and if you click on the cell, the exact problem is described in the popup window.
</p>
");
                       
        $this->add("Speed", "
<p>
TimeSafe is very JavaScript intensive. It requires a modern, standards compliant browser (FireFox 3.0 and 3.5 have both been tested) to function at all. Because it performs a lot of table manipulation in JavaScript, it will be extremely slow on browsers where such operations are slow. If TimeSafe is running slowly, consider updating to the latest browser version.
</p>
");

        $this->add("TimeSafe Administration", "
<p>
There are only two types of administrative actions possible in TimeSafe - changing tag groups and changing tags. The project list is imported from egs and can not be edited here. This will change in a future version of the product.
</p>
<p>
A tag can be visible for all projects, a group of projects, or only one specific project. The project groups are created by the egs importing tool, and can not be edited in the administrative interface. Use the project and project group dropdowns to set the tag visibility.
</p>
<p>
Some tags are almost always used. These can be made recommended, in which case TimeSafe will show a warning if the user has not chosen the tag. This can be used e.g. to create a tag to mark external hours as billable. 
</p>
<p>
Tags can be put into groups where at most one tag may be chosen. It can be made mandatory to pick one tag in a group, or it can be optional. Toggle the 'required' checkbox of a tag group to choose this. 
</p>
<p>
Please not the difference between recommending tags and requiring them. Id a recommended tag is not chosen, a warning will be displayed. If a tag group is required, one of the tags in that group must be chosen. 
</p>
");
        

        $this->add("Time report integration", "
<p>
TimeSafe integration with time report is currently rather kludgy. A future version of the time reporting tool will be significantly more powerful and extensible, but as of yet, time report integration relies on naming tags and tag groups according to specific regexp patterns. If that sentence did not make sense to you, you might want to ask for help before updating the tag and tag group list.

<ul>
<li>
40 % overtime tags must match the perl regexp /^40 *% *overtime/i. For example «40 % overtime» would match.
</li>
<li>
100 % overtime tags must match the perl regexp /^100 *% *overtime/i. For example «100 % overtime» would match.
</li>
<li>
Work leave tags must match the perl regexp /^(work *leave|avspasering)$/i. For example «Work leave» would match.
</li>
<li>
Vacation tags must match the perl regexp /^vacation$/i. For example «Vacation» would match.
</li>
<li>
The <i>group name</i> for all types of illness must match the perl regexp /^(illness|sick.*)$/i. For example «Illness» would match.
</li>
<li>
The <i>group name</i> for all types of paying non-work must match the perl regexp /^paying non[- ]?work$/i. For example «Paying non-work» would match.
</li>
</ul>
Note that some of these tags match against tag group name, and others against tag name. All the matches are case insensitive.
</p>
");
        
        $this->add("Future features", "
<p>
There are loads of features in the TimeSafe pipeline. Before requesting a feature, please make sure that this issue is not reported by checking <a href='https://projects.freecode.no/projects/timesafe/issues'>the issue tracker</a>. New suggestions are welcome, especially those that involve pie.
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