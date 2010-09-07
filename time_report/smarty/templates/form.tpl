<form method='GET' action='' id="report_form">

<table class="form">
  <tr>
    <td>

      <table width="100%">
	<tr>
	  <td>Type</td>
	  <td align="left">

	    <select name="type" onchange="timeReportSubmit();">
	    {foreach from=$page_type_list key=type_key item=type_value}
	      <option value='{$type_key}' {if $type_key == $page_type}selected="1"{/if}>{$type_value}</option>
	    {/foreach}
	    </select>
	  </td>
	  <td valign="top" rowspan="5">
	    <select name="users[]" multiple="true" size="10" onchange="timeReportSubmit();">
	    {foreach from=$all_users key=username item=name}
	      <option value='{$username}' {if array_search($username, $selected_users) !== FALSE}selected="1"{/if}>{$name}</option>
	    {/foreach}
	    </select>
	  </td>

	  <td valign="top" rowspan="5">
	    <select name="projectids[]" multiple="true" size="10"  onchange="timeReportSubmit();">
	    {foreach from=$all_projectids key=projectid item=projectname}
	      <option value='{$projectid}' {if array_search($projectid, $selected_projects) !== FALSE}selected="1"{/if}>{$projectname}</option>
	    {/foreach}
	    </select>
	  </td>
	</tr>
	<tr>
	  <td width="33%">Date from</td>
	  <td valign="top" align="center">
	    <input name="from" id="from" value="{$date_from}" autocomplete="off" size="10" maxlength="12"  width="100%"  onchange="timeReportSubmit();" />
	     {literal}
	     <script type="text/javascript">
	       Calendar.setup(
		 {
		   inputField  : "from",
		   displayArea : "from",
		   ifFormat    : "%Y-%m-%d",
		   daFormat    : "%Y-%m-%d",
		   button      : "from"
		 }
	       );
	     </script>
	     {/literal}
	  </td>
	</tr>

	<tr>
	  <td>Date to</td>
	  <td>
	    <input name="to" id="to" value="{$date_to}" autocomplete="off" size="10" maxlength="12" onchange="timeReportSubmit();" />
	     {literal}
	     <script type="text/javascript">
	       Calendar.setup(
		 {
		   inputField  : "to",
		   displayArea : "to",
		   ifFormat    : "%Y-%m-%d",
		   daFormat    : "%Y-%m-%d",
		   button      : "to"
		 }
	       );
	     </script>
	     {/literal}
	  </td>
	</tr>

	<tr>
	  <td colspan="2"><button type='button' onclick='prevPeriod();'>« Previous period</button></td>
	</tr>

	<tr>
	  <td colspan="2"><button type='button' onclick='nextPeriod();'>Next period »</button></td>
	</tr>

      </table>

    </td>
  </tr>
</table>


<div id='url_direct'></div>

</form>

<div id='spinner'></div>

