      <table class="fctable">
        <thead>
	  <tr>
            <th class="top_head">Customer</th>
            <th class="top_head">Project</th>
	  {foreach from=$type key=name item=value}
            <th class="top_head">{$value}</th>
	  {/foreach}
            <th class="top_head">Bill number</th>
          </tr>
        </thead>
        <tbody>
{foreach from=$project item=project_item}
          <tr>
            <td>{$project_item->customer}</td>
            <td>{$project_item->project}</td>
  {foreach from=$project_item->summary item=data}
            <td class="number">{$data}</td>
  {/foreach}
            <td></td>
	  </tr>
{/foreach}
        </tbody>
        <tfoot>
	  <tr>
            <th class="bottom_sum"></td>
            <th class="bottom_sum"></td>
	  {foreach from=$total key=name item=value}
            <th class="number bottom_sum">{$value}</td>
	  {/foreach}
            <th class="bottom_sum"></td>
        </tfoot>

      </table>

{foreach from=$project item=project_item}

    <h1>{$project_item->customer} - {$project_item->project} invoice specification</h1>

	<p>
  	  Period: From {$date_from} to {$date_to}<br/>

  	  Consultant{if count($project_item->person) gt 1}s{/if}: {$project_item->person_names}
	</p>


    {include file='matrix.tpl' matrix=$project_item->total}

{if count($project_item->person) gt 1}
<h2>Per consultant summary</h2>

  <table class="fctable">
    <thead>
      <tr>
	<th class="super_head "></th>
	<th class="super_head border_left" colspan='4'>Billable</th>
	<th class="super_head border_left" colspan='4'>Nonbillable</th>
      </tr>
      <tr>
	<th class="top_head">Name</th>
	<th class="top_head border_left">Regular</th>
	<th class="top_head">OT 40&nbsp;%</th>
	<th class="top_head">OT 100&nbsp;%</th>
	<th class="top_head">Total</th>
	<th class="top_head border_left">Regular</th>
	<th class="top_head">OT 40&nbsp;%</th>
	<th class="top_head">OT 100&nbsp;%</th>
	<th class="top_head">Total</th>
      </tr>
    </thead>
    <tbody>
  {foreach from=$project_item->person key=name item=proj}  
      <tr>
        <td>{$name|replace:' ':'&nbsp;'}</td>
    {if isSet($proj.Billable)}
        <td class="number border_left">{$proj.Billable.1->str}</td>
        <td class="number">{$proj.Billable.2->str}</td>
        <td class="number">{$proj.Billable.3->str}</td>
        <td class="number right_sum">{$proj.Billable.4->str}</td>
    {else}
        <td class="number border_left">0.0</td>
        <td class="number">0.0</td>
        <td class="number">0.0</td>
        <td class="number right_sum">0.0</td>
    {/if} 
    {if isSet($proj.Nonbillable)} 
        <td class="number border_left">{$proj.Nonbillable.1->str}</td>
        <td class="number">{$proj.Nonbillable.2->str}</td>
        <td class="number">{$proj.Nonbillable.3->str}</td>
        <td class="number right_sum">{$proj.Nonbillable.4->str}</td>
    {else} 
        <td class="number border_left">0.0</td>
        <td class="number">0.0</td>
        <td class="number">0.0</td>
        <td class="number right_sum">0.0</td>
    {/if} 
      </tr>
  {/foreach}
    </tbody>
  </table>
{/if}

  <div class="page_break">
  {foreach from=$project_item->details key=name item=hours}
    {if count($hours->arr) gt 0}
     <h2>{$name} hours</h2>
     <table class="fctable">
       <thead>
         <tr>
	   <th class="top_head date_column">Date</th>
	   <th class="top_head hour_column">Hours</th>
	   <th class="top_head">Type</th>
	   <th class="top_head">Consultant</th>
	   <th class="top_head">Description</th>
         </tr>
       </thead>
       <tbody>
      {foreach from=$hours->arr item=el}
         <tr>
	   <td>{$el.date_html}</td>
	   <td class="number">{$el.hours_html}</td>
	   <td>{$el.typename|replace:' ':'&nbsp;'}</td>
	   <td>{$el.fullname|replace:' ':'&nbsp;'}</td>
	   <td>{$el.description}</td>
         </tr>
      {/foreach}
      {if count($hours->arr) gt 2}
         <tr>
	   <th class="bottom_sum"></th>
	   <th class="number bottom_sum">{$hours->total_str}</th>
	   <th class="bottom_sum"></th>
	   <th class="bottom_sum"></th>
	   <th class="bottom_sum"></th>
         </tr>
       {/if}
       </tbody>
     </table>
    {/if}
  {/foreach}

{/foreach}

