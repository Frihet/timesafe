<?php
  /**
   Helper functions for making forms.
   */

class form
{
    static $iframe_id=0;

    /**
     Create a select box with the specified values.
     */
    function makeSelect($name, $values, $selected=null, $id=null, $attributes=array()) {
        
        $id_str = $id?'id="'.htmlEncode($id).'"':'';
        $multiple_str = "";
        if( is_array($selected)) {
            $multiple_str = "multiple";
            $name = "{$name}[]";
        }
                
        $select='<select '.$multiple_str.' name="'.htmlEncode($name).'" '.$id_str. " ";

        foreach($attributes as $key => $value) {
            $val = htmlEncode($value);
            $select .= "$key='$val' ";
        }
        $select .= ">";
                
        if ($values!=null) {
            
            foreach($values as $num => $val) {
                if(is_object($val)) {
                    $id = $val->getId();
                    $name = $val->getDescription();
                } else {
                    $id = $num;
                    $name = $val;
                }
                
                $selected_str = "";
                
                if( is_array($selected)) {
                    if (in_array($id, $selected)) {
                        $selected_str = 'selected';
                    }
                } else {
                    if ($id == $selected) {
                        $selected_str = 'selected';
                    }
                }
                
                $select .= "<option $selected_str value='".htmlEncode($id)."'>". htmlEncode($name)."</option>\n";
            }
        }
        
        $select .= "</select>";
        return $select;
        
    }

    function makeColumnListEditor($column_id, $select_id, $table_id=null) 
    {
        static $column_list_counter=0;

        if($table_id === null) {
            $table_id = 'column_list_input_' . ($column_list_counter++);
        }
        
        $res = "<table class='striped' id='$table_id'>
<tr>
<th>Name</th>
<th></th>
</tr>
";
        
        foreach(ciColumnList::getItems($column_id) as $id => $name) {
            $item_id = $table_id . "_" . ($column_list_counter++);
            $remove = "
<button type='button' onclick='submitAndReloadColumnList(\"removeColumnListItem\",\"$column_id\", $id, \"$item_id\", \"$table_id\", \"$select_id\")'>Remove</button>
";
            $update = "
<button type='button' onclick='submitAndReloadColumnList(\"updateColumnListItem\",\"$column_id\", $id, \"$item_id\", \"$table_id\", \"$select_id\")'>Update</button>
";
            $res .= "<tr><td>".form::makeInput($item_id, $name, null, false, $item_id). "</td><td>$update $remove</td></tr>";
        }

        $item_id = $table_id . "_" . ($column_list_counter++);
        
        $add = "
<button type='button' onclick='submitAndReloadColumnList(\"addColumnListItem\",\"$column_id\", null, \"$item_id\", \"$table_id\", \"$select_id\")'>Add</button>
";
        $res .= "<tr><td>".form::makeInput($item_id, '', null, false, $item_id )."</td><td>" . $add . "</td></tr>";
        
        $res .= "</table>";
        return $res;
        
    }
    
    function makeText($name, $value, $id=null, $class=null) 
	{
            $id_str = $id?'id="'.htmlEncode($id).'"':'';
            $class_str = $class?'class="'.htmlEncode($class).'"':'';
            return "<input $id_str $class_str size='32' name='".htmlEncode($name)."' value='".htmlEncode($value)."'>\n";		
	}
	
    
    function makeInput($name, $value, $column_id, $read_only=false, $id=null) 
    {
        $type = ciColumnType::getType($column_id);
        
        if ($read_only) {
            
            switch($type) {
		case CI_COLUMN_TEXT_FORMATED:
		    $res = $value; /* We should plug in a filter to disallow weird html here, but it's not a top priority item. */
		    break;
		    
		case CI_COLUMN_IFRAME:
		    if ($value == null || $value == '') 
		    {
			break;
		    }
		    
		    if ($id == null) 
		    {
			$id = "form_iframe_" . form::$iframe_id;
			form::$iframe_id++;
		    }
		    $res = "
<iframe
    id='".htmlEncode($id)."' name='".htmlEncode($id)."'
    src='".htmlEncode($value)."'
    onload='dynamicIFrame.resize(\"".htmlEncode($id)."\")'
    scrolling='no'>
Iframes not supported by this browser.
</iframe>";
		    
		    break;
		    
		case CI_COLUMN_LIST:
		    $res = ciColumnList::getName($value);
		    
		    if ($res == null) {
			$res = htmlEncode("<invalid value>");
		    }
		    break;
		    
		case CI_COLUMN_EMAIL:
		    $ev = htmlEncode($value);
		    $res = "<a href='mailto:$ev'>$ev</a>";
		    break;
		
		default:
		    $res = htmlEncode($value);
		    break;
            }
        }
        else {
            
            $id_str = $id?'id="'.htmlEncode($id).'"':'';
            
            switch($type) {
            case CI_COLUMN_IFRAME:
            case CI_COLUMN_TEXT:
            case CI_COLUMN_EMAIL:
                $res = form::makeText($name, $value, $id);
                break;
                
            case CI_COLUMN_DATE:
                $res = form::makeText($name, $value, $id);
                $res .= '<script type="text/javascript">
$(function()
        {
                $("#'.htmlEncode($id).'").datePicker(
                        {
                                startDate: "1970-01-01"
                        }
                );
        }
);
</script>';
                break;
                
            case CI_COLUMN_TEXT_FORMATED:
                /*
                 Put our editor in a div with a hard coded width, and hard code the width here, not in the css. 
                */
                $res = "\n<textarea style='height: 250pt; width: 400pt;' class='rich_edit' cols='64' rows='16' $id_str name='".htmlEncode($name)."'>".htmlEncode($value)."</textarea>";
                break;
            case CI_COLUMN_LIST:
                /*
                 We _need_ an id here to update the select in the ajax code. Create one if not provided.
                */
                if (!$id) {
                    static $temp_id=0;
                    $id = "temp_id_" . ($temp_id++);
                }
                
                $res = form::makeSelect($name, ciColumnList::getItems($column_id), $value, $id);
                $res .= makePopup("Edit item list", "More...", form::makeColumnListEditor($column_id, $id), "edit" );
                
                break;
                
            default:
                $res = htmlEncode($value);
                break;
                
            }
        }
        
        return $res;
    }

    function makeForm($content, $hidden=array(),$method='post', $file_upload=false)
    {
        $enc = $file_upload?"enctype='multipart/form-data'":"";
        
        $form = "<form accept-charset='utf-8' method='$method' action='' $enc>\n";
        foreach($hidden as $name => $value) {
            $form .= "<input type='hidden' name='".htmlEncode($name)."' value='".htmlEncode($value)."'>\n";
        }
        
        $form .= $content;
        $form .= "</form>\n";
        return $form;
    }

}

?>