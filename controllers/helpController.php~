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
		util::setTitle("FreeCMDB help");
		
            $this->add("Introduction", "
<p> FreeCMDB is a Configuration Managment DataBase (CMDB), which is a
tool for tracking important assets in an IT environment and the
relationships between them. It is intended to be used to track such
things as servers and software services running on various servers,
but can also be used for other tasks such as general purpose asset
managment. The tracking of dependencies in a CMDB allows the user to
answer such questions as «If Router X goes down, which services will go
down?» </p>

<p> The basic elements of a CMDB are called Configuration Items
(CI). A CI can be a physical server, a software service, or even a
person. Any item that you want to track using the CMDB is a CI. Every
CI has various properties, such as name, URI, description and service
owner. The exact list of such properties (called columns in FreeCMDB)
can be changed in the administration menu of FreeCMDB (see
below). </p>
");

            $this->add("Obtaining the FreeCMDB source code","
<p> FreeCMDB is open source. Since the project is only just started,
there still aren't anu stable releases. To obtain the latest version
of the source, make sure the git command is installed on your system (on ubuntu, run <code>sudo apt-get install git-core</code>), and then run the command <code>git pull
https://projects.freecode.no/git/freecmdb.git </code>. This should download the latest version of FreeCMDB into your current working  
</p>
");
            
            
            $this->add("Installation","
<p> FreeCMDB requires a php enabled web server and a PostgreSQL
database to run. The following php packages are also needed: GraphViz,
PDO, PDO_PGSQL and json. The GraphViz package is not part of the PHP
standard release, the other packages should already be included in
your php installation if it is sufficiently new. To install these
packages on an older php version, you will have to use either the pecl
or the pear commands. You may need to install these commands as well
before using them. You will also mostl likely have to update your
php.ini file to include the correct .so files as extensions. Please
refer to the PHP documentation and any operating system specific
documentation for specific instructions on how to use and install
these commands. </p>

<p> After you have a correctly configured php server with the right
extensions installed, installing FreeCMDB should be as simple as
fetching the source code (see above), giving the web server write
permissions on all files in the source folder and finally accessing
the FreeCMDB software through your web browser. At that point, you
will be asked to provide database credentials for the database server,
and the software will install itself. </p>
<p>
Good luck!
</p>
");
            
            $this->add("Page layout", "
<p>
Each FreeCMDB page consists of the same parts. These are:

<div class='figure'>
<img src='static/freecmdb_screen.png' alt='The different elements of the FreeCMDB user interface'/>
<span class='caption'>The different elements of the FreeCMDB user interface.</span>
</div>

<ul>

<li>The top menu, located at the very top. The items in this menu are always the same, and they take you to the different overall sections of FreeCMDB. These sections are:
<ul>
<li>Items, the main function of the program, where you view and edit your CIs.</li>
<li>Administration, where you can define new CI types, add or remove columns from CIs, etc.</li>
<li>Help, the FreeCMDB documentstion</li>
</ul>
</li>

<li>Messages, located just below the top menu. If there are any
messages from FreeCMDB, such as error reports or information on
actions taken, they are diplayed here. If there are no messages to
show, this section will be invisible. </li>

<li>Action bar, located on the left hand side of the screen. This bar
will contain the action menu, which is a menu which will contain
different items depending on which page you are viewing. The action
bar may also contain other items, such as a list of recently performed
actions.</li>

<li>Main content area, which is the main part of the application.</li>

</ul>
</p>
");

            $this->add("Handling CIs","
<p> To start using FreeCMDB, first install the software (see
installation instructions), then go to the web page you have
configured for FreeCMDB. Click the 'Administration' link at the top of
the page, and then use the 'Edit CI types' and 'Edit CI columns' to
configure what types of configuration items you want, and what type of
data should be stored for your CIs.  </p>

<p> The next step is to start adding your CIs. Click on the 'Items'
link at the top left of the page. Then click on the 'Create new item'
link in the action menu. Enter information about your CI, and click
'Save'. Repeat these steps for every CI you want to add. Once you have
several CIs, you can also start creating dependencies between CIs in
order to see the full dependency tree of your CMDB.  </p>
");

            $this->add("Limitations", "
<p> 

<ul>

<li>
FreeCMDBuses creates graphs in the SVG format to show all
dependencies in the system. The SVG file format is not currently
supported by Internet Explorer. FreeCMDB falls back to rendering the
grapgs in the PNG format on browsers that dfo not support
SVG. Unfortunalt,y the SVG format does not have support for links in
the graph, and rendering quality is overall lower. It is therefor
strongly suggeted that you use a better browser, such as 
<a href='http://mozilla.org'>Mozilla Firefox</a>, when using
FreeCMDB. 
</li>

<li> FreeCMDB does not currently support user managment. To restrict
access to it, use a .htaccess file to set a password.  </li>

</ul>
</p>
");
            $this->add("Notes", "
<p> Whenever you perform an action that changes data in FreeCMDB, such
as updating a CI, FreeCMDB will perform a redirect afterwards. Because
of this, the reload button as well as the back/forward buttons will
always work, and you never risk double posts. It also means that it is
always safe to bookmark URLs, and they should always take you back to
the same page. The drawback of using this technique is that all page
views that update information must perform two roundtrips to the
server instead of one. If you're on a connection with very high
latency, this might be noticable. </p>


");
            
            $this->add("Performance", "
<p> FreeCMDB is designed with speed in mind. To increase speed,
FreeCMDB by default uses compression to decrease the bandwidth used by
sending html to the browser. FreeCMDB also uses the extremely
efficient SVG file format for sending images to the
browser. Performance information, such as server side rendering time
and number of SQL queries performed during a page view can be seen by
hovering the cursor over the copyright statement at the bottom of the
page. Note that the rendering time does not include transfer time to
the browser or the time used by the browser to render the page. In the
case of pages that caused redirects (see above), the rendering time of
both pages will be included, but the time taken to send the redirect
request to the browser is not.</p>

");
            
            $this->add("Support","
FreeCMDB is free software, released under the GPLv3 license. This
means that anybody is allowed to modify and support it. Because
FreeCMDB is based on common components such as PHP and PostgreSQL,
there are companies and individuals located all over the world with
the competence needed to support FreeCMDB. FreeCode AS; the company
that created FreeCMDB would be more than happy to help you out with
support. You can contact us at <a href='mailto:sales@freecode.no'>sales@freecode.no</a>.
");
            
                       
            $this->show($this->getActionMenu(),$this->get());
		
	}
	

	function isHelp()
	{
		return true;
		
	}
	
}


?>