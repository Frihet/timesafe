<?php

function makeDateSelector($name, $value, $id=null, $class=null, $attributes = array())
{
    $attr = '';
    foreach($attributes as $key => $val) {
        $val = htmlEncode($val);
        $attr .= "$key='$val' ";
    }
    $id_str = $id?'id="'.htmlEncode($id).'"':'';
    if ($class == null) $class = "";
    $class_str = 'class="datepickerinput '.htmlEncode($class).'"';
    return "<input type='text' $id_str $class_str size='16' name='".htmlEncode($name)."' value='".htmlEncode($value)."' {$attr} />\n";
}

function makeHidden($name, $value, $id=null, $class=null, $attributes = array())
{
    $attr = '';
    foreach($attributes as $key => $val) {
        $val = htmlEncode($val);
        $attr .= "$key='$val' ";
    }
    $id_str = $id?'id="'.htmlEncode($id).'"':'';
    if ($class == null) $class = "";
    $class_str = 'class="datepickerinput '.htmlEncode($class).'"';

    $res = '';
    if (is_array($value)) {
        foreach ($value as $part) {
            $res .= "<input type='hidden' $id_str $class_str name='".htmlEncode($name)."[]' value='".htmlEncode($part)."' {$attr} />\n";
        }
    } else {
        $res .= "<input type='hidden' $id_str $class_str name='".htmlEncode($name)."' value='".htmlEncode($value)."' {$attr} />\n";
    }
    return $res;
}

function formatDate($tm)
{
    return date('Y-m-d', $tm);
}

function today()
{
    $now = time();
    $year = date('Y', $now);
    $month = date('m', $now);
    $day = date('d', $now);
    return mktime(12, 0, 0, $month, $day, $year);
}

function parseDate($date)
{
    list($year, $month, $day) = explode('-',$date);
    return mktime(12, 0, 0, $month, $day, $year);
}
