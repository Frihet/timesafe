FreeCode PHP toolkit

The PHP toolkit contains many useful functions for template generation, input parsing, error handling, and extracting information from the EGS database.

It contains the following files and directories:

* FCConfig.php	configuration options for the toolkit. Note that some of these options are system specific. Please take a look at this file and adjust it to your needs.

* FCToolkit.php	contains many useful utilities for handling input, sending mail, and handling errors.

* Egs.php	contains a class Egs, with many methods for extracting information from EGS. 

* FCInput.php	contains a general input validation and parsing class. 

* static/	contains css, graphics and JavaScript    

* templates/	contains general FreeCode templates, headers, footers, etc.


The toolkit is dependent on the following being installed:

PEAR DB
smarty

# apt-get install php-pear smarty 