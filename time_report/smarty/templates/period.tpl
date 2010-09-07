{include file="file:$TEMPLATE_DIR/header.tpl"}

<table width="100%">
  <tr>
    <td width="10%" class="prev">{if $link_prev != ""}<a href="{$link_prev}">&lt; Previous</a>{/if}</td>
    <td width="80%">&nbsp;</th>
    <td width="10%" class="next">{if $link_next != ""}<a href="{$link_next}" >Next &gt;</a>{/if}</td>
  </tr>
</table>

{foreach from=$hours item=user}
    {include file="period_user.tpl" dates=$dates user=$user}
{/foreach}

{include file="footer.tpl"}
