{if $dom eq "full"}
{include file="file:$TEMPLATE_DIR/header.tpl"}
{include file="file:form.tpl"}
<div id='content'>
{/if}

{include file="$mode.tpl"}

{if $dom eq "full"}
</div>
{include file="footer.tpl"}
{/if}
