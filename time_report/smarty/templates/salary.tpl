      <table class="fctable">
        <thead>
          <tr>
            <th class="top_head">Consultant</th>
            {assign var='first' value='t'}

	    {foreach from=$type key=name item=value2}

            <th class="top_head {if $first eq 't'}border_left{/if}">
{$value2|replace:' %':'&nbsp;%'|replace:'overtime':'OT'}</th>
              {assign var='first' value='f'}

            {/foreach}
	    <th class="top_head border_left">Unpaid non-work</th>
	    <th class="top_head border_left">Sum</th>
	    <th class="top_head border_left">Paid non-work</th>
	    <th class="top_head border_left">Sum</th>
          </tr>
        </thead>
        <tbody>
{foreach from=$person item=person_item}
          <tr>
            <td>{$person_item->name}</td>
  {foreach from=$person_item->summary item=data}
            <td class="{$data->class}">{$data->str}</td>
  {/foreach}
	  </tr>
{/foreach}
	  <tr>
        </tbody>
        <tfoot>
            <th class="bottom_sum"></th>
  {foreach from=$summary item=data}
            <th class="bottom_sum number {$data->class} ">{$data->str}</th>
  {/foreach}
        </tfoot>

      </table>


{foreach from=$person item=person_item}

    <h1>Salary specification for {$person_item->name}</h1>

        <span class="small_print">
	<p>
  	  Period: From {$date_from} to {$date_to}.
	</p>

	<p>
	  Work leave: {$person_item->work_leave_done_str} hour(s) of work leave have been taken, out of a possible {$person_item->work_leave_ok_str} hour(s). <span{if $person_item->work_leave_remaining < 0} class="error"{/if}>{$person_item->work_leave_remaining_str}</span> hour(s) remain.
	</p>
  {include file='matrix.tpl' matrix=$person_item->total}
<h2>Per project summary</h2>

  {foreach from=$person_item->project key=name item=proj}
    <h3>Summary for {$name}</h3>
    {include file='matrix.tpl' matrix=$proj}
  {/foreach}


  {foreach from=$person_item->details key=name item=hours}
    {if count($hours->arr) gt 0}
     <h2>{$name} hours</h2>
     <table class="fctable hour_list">
       <thead>
         <tr>
	   <th width="12%" class="top_head">Date</th>
	   <th width="4%"  class="top_head">Hours</th>
	   <th width="10%" class="top_head">Type</th>
	   <th width="25%" class="top_head">Project</th>
	   <th width="54%" class="top_head">Description</th>
         </tr>
       </thead>
       <tbody>
      {foreach from=$hours->arr item=el}
         <tr>
	  <td>{$el.date_html}</td>
	  <td class="number">{$el.hours_html}</td>
	  <td>{$el.typename}</td>
	  <td>{$el.fptt_html}</td>
	  <td>{$el.description_html}</td>
         </tr>
      {/foreach}
      {if count($hours->arr) gt 2}
         <tr>
	   <th class="bottom_sum"></td>
	   <th class="number bottom_sum">{$hours->total_str}</td>
	   <th class="bottom_sum"></td>
	   <th class="bottom_sum"></td>
	   <th class="bottom_sum"></td>
         </tr>
       {/if}
       </tbody>

     </table>
    {/if}
  {/foreach}
  </span>
{/foreach}

