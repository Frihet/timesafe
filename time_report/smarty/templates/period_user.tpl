<h2>{$user.fullname}</h2>

<table class='hour_list'>
  <tr>
    <th class="hour_list" width="30%">Task</th>
{foreach from=$dates key=date item=day}
    <th class="hour_list" width="{$cell_width}%">{$day}</th>
{/foreach}
  </tr>
{foreach from=$user.tasks key=task item=hours name=hours}
  <tr class="period{cycle values="_odd,"}">
      <td>{$task}</td>
      {foreach from=$hours item=hour}
          <td>{$hour}</td>
      {/foreach}
  </tr>
{/foreach}

</table>
