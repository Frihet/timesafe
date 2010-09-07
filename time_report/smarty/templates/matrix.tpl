    <p>
      <table class="fctable">
	<thead>
        <tr>
	  <th class="top_head"></th>
	  {foreach from=$type key=name item=value}
          <th class="top_head">{$value}</th>
          {/foreach}
	  <th class="top_head">Total</th>
        </tr>
	</thead>
	<tbody>
	  {foreach from=$matrix key=name item=value}
	  <tr>

	  <th class="left_head {$value.1->class}">{$name}</th>
            {foreach from=$value key=name2 item=value2}
            <{if $name=='Total'}th{else}td{/if} class="{$value2->class}">{$value2->str}</td>
            {/foreach}
	  </tr>
	  {/foreach}
          </td>
	</tbody>
      </table>
    </p>