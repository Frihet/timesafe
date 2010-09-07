<div style='float: left'>
      <table class="fctable">
        <thead>
	  <tr>
            <th class="top_head">Name</th>
            <th class="top_head">Position</th>
            <th class="top_head">Total<br>hours</th>
            <th class="top_head">Billable<br>hours</th>
            <th class="top_head" title='Billable % of total number of hours worked in period'>Billable</th>
            <th class="top_head" title='Billable % of standard number of work hours in period, compensated for non-standard target work hours. Gustavo, this one is for you.'>Target</th>
	  </tr>
        </thead>
        <tfoot>
	  <tr>
            <th class="bottom_sum"></th>
            <th class="bottom_sum"></th>
            <th class="bottom_sum number">{$time_total}</th>
            <th class="bottom_sum number">{$billable_time_total}</th>
            <th class="bottom_sum number">{$billable_percentage_total_avg} %</th>
            <th class="bottom_sum number">{$billable_percentage_target_avg} %</th>
	  </tr>
        </tfoot>
        <tbody>	
	  {foreach from=$persons key=person_id item=person}
          <tr>
              <td class="text">{$person.name}</td>
              <td class='text'>{$person.jobtitle}</td>
              <td class='number'>{$person.time}</td>
              <td class='number'>{$person.billable_time}</td>
              <td class='number'>{$person.billable_percentage_total} %</td>
              <td class='number'>{$person.billable_percentage_target} %</td>
          </tr>
          {/foreach}
        </tbody>	
      </table>
</div>

<div class='figure'>
<img src='{$vsd_graph_1_url}'>
<em class='caption'>Figure 1: Hours of work performed by each selected employee during the specified period</em>
</div>

<div class='figure'>
<img src='{$vsd_graph_2_url}'>
<em class='caption'>Figure 2: VSD Div4</em>
</div>


<div class='figure'>
<img src='{$vsd_graph_3_url}'>
<em class='caption'>Figure 3: Hours of work performed by each selected employee during the year so far</em>
</div>

<div class='figure'>
<img src='{$vsd_graph_4_url}'>
<em class='caption'>Figure 4: Billable target per employee during the year so far</em>
</div>

<div class='figure'>
<img src='{$vsd_graph_5_url}'>
<em class='caption'>Figure 5: Billable target during the year so far</em>
</div>

<div style='clear: both; margin-bottom: 25px;'></div>
