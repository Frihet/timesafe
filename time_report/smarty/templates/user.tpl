<!--
<div style='clear: both'>&nbsp;</div>
-->
<div style='float: left'>
      <table class="fctable">
	  <caption>Table 1: Hour summary<caption>
	  <tr>
            <th class="top_head"></th>
	  {foreach from=$hour_type key=name item=value}
            <th class="top_head">{$value}</th>
          {/foreach}
            <th class="top_head">Total</th>
	  </tr>

	  {foreach from=$total key=name item=value}
	  <tr>
	  {if $name eq "Total"}<th class="bottom_sum text">{else}<td class="text">{/if}
            {$name}
	  {if $name eq "Total"}</th>{else}</td>{/if}
	  {foreach from=$value key=name2 item=value2}
	  {if $name eq "Total"}<th class="bottom_sum number">{else}<td class="number">{/if}
            {$value2->str}
	  {if $name eq "Total"}</th>{else}</td>{/if}
	  {/foreach}
	  </tr>
	  {/foreach}
          </td>

      </table>
Target number of work hours for this period: {$regular_hours}.0

</div>



<!--
<div style='clear: both; margin-top: 3em;'>
-->
<div style='float: left'>
      <table class="fctable">
	  <caption>Table 2: Work leave<caption>

	  <tr>
            <th class="top_head"></th>
            <th class="top_head">Hours</th>
          </tr>

	  <tr>
            <td class="text"> Earned </td>
            <td class="number"> {$work_leave_ok_tot} </td>
          </tr>

	  <tr>
            <td class="text"> Spent </td>
            <td class="number"> {$work_leave_done_tot} </td>
          </tr>

	  <tr>
            <th class="bottom_sum text"> Remaining </th>
            <th class="bottom_sum number">{$work_leave_remaining_tot}</th>
          </tr>

      </table>

</div>

<div style='float: left'>
  <img src="{$histogram_url}" />
</div>

<div style='clear: both; margin-bottom: 25px;'></div>

{if $has_bad}
<h2>Possible errors</h2>
<table class="fctable hour_list">

  <thead>
    <tr>
      {foreach from=$displayfields_bad key=display item=field}
        <th {if isSet($field.width)}style='width: {$field.width};'{/if} class="text">
          {if $field.sortby}<a href="{$curr_url}&sortby={$field.sortby}{if $field.sortby == $sortby}&sortdirection={$sortdirection}{/if}">{$display}</a>{else}{$display}{/if}</th>
      {/foreach}

    </tr>
  </thead>
  <tfoot>
    <tr>
      <td colspan="{php}echo count($this->get_template_vars('displayfields'));{/php}">&nbsp;</td>
    </tr>
  </tfoot>

{foreach from=$hours_bad key=sortby item=sortby_hours}
  {foreach from=$sortby_hours item=info}
    <tr class="hour{if $info.billable == 't'}_Billable{else}_Nonbillable{/if}_{$info.typename|replace:' ':'_'|replace:'%':'p'}{cycle values="_odd,"}">
      <td>{$info.fullname_html}</td>
      <td>{$info.date_html}</td>
      <td>{$info.fptt_html}</td>
      <td class="number">{$info.hours_html}</td>
      <td>{$info.description_html}</td>
      <td>{$info.badness}</td>
    </tr>
  {/foreach}
{/foreach}
  </tbody>
</table>
{else}
<p>
Awesome! You have no detected errors during this period. You are a time reporting ninja.
</p>
{/if}



<table class="fctable hour_list">
  <thead>
    <tr>
      {foreach from=$displayfields key=display item=field}
        <th {if isSet($field.width)}style='width: {$field.width};'{/if} class="top_head text">
          {if $field.sortby}<a href="{$curr_url}&sortby={$field.sortby}{if $field.sortby == $sortby}&sortdirection={$sortdirection}{/if}">{$display}</a>{else}{$display}{/if}</th>
      {/foreach}
    </tr>
  </thead>
  <tfoot>
    <tr>
      <td colspan="{php}echo count($this->get_template_vars('displayfields'));{/php}">&nbsp;</td>
    </tr>
  </tfoot>

  <tbody>
{foreach from=$hours key=sortby item=sortby_hours}
  {foreach from=$sortby_hours item=info}
    <tr class="hour{if $info.billable == 't'}_Billable{else}_Nonbillable{/if}_{$info.typename|replace:' ':'_'|replace:'%':'p'}{cycle values="_odd,"}">
      <td>{$info.fullname_html}</td>
      <td>{$info.date_html}</td>
      <td>{$info.fptt_html}</td>
      <td class="number">{$info.hours_html}</td>
      <td>{$info.description_html}</td>
    </tr>
  {/foreach}
{/foreach}
  </tbody>
</table>

