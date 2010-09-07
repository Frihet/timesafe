<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <!-- Include stylesheets. -->
    {foreach key=css item=media from=$INCLUDE_CSS}
      <link rel="stylesheet" href="{$css}" type="text/css" media="{$media}" />
    {/foreach}    

    <!-- Include javascript. -->
    {foreach item=js from=$INCLUDE_JS}
      <script type="text/javascript" src="{$js}"></script>
    {/foreach}

    <title>{$TITLE}</title>
  </head>

<body {if isset($BODY_ONLOAD)}onLoad="{$BODY_ONLOAD}" {/if}>

