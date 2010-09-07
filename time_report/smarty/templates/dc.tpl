<div style='float: left'>
      <table class="fctable">
	  <caption>Work, divided per billing divisions and subdivisions.<caption>
	  <tr>
            <th class="top_head">Subdivision</th>
            <th class="top_head">Internal time</th>
            <th class="top_head">External, nonbillable</th>
            <th class="top_head">External, billable</th>
            <th class="top_head">VSD</th>
	  </tr>

	  {foreach from=$data key=subdivision_name item=value}
 	  <tr>
      	  {if $subdivision_name eq "Total"}
	    <th class='bottom_sum '>{$subdivision_name}</th>
	    <th class="bottom_sum number">{$value->internal_nonbillable}</th>
	    <th class="bottom_sum number">{$value->external_nonbillable}</th>
	    <th class="bottom_sum number">{$value->external_billable}</th>
	    <th class="bottom_sum number">{$value->vsd}</th>
	  {else}
	    <th>{$subdivision_name}</th>
	    <td class="number">{$value->internal_nonbillable}</td>
	    <td class="number">{$value->external_nonbillable}</td>
	    <td class="number">{$value->external_billable}</td>
	    <td class="number">{$value->vsd}</td>
	  {/if}	  
	  </tr>
	  {/foreach}
          </td>
      </table>
</div>

<div style='clear: both; margin-bottom: 25px;'></div>
