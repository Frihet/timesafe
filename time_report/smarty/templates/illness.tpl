    <table class="fctable">

      <tr>
        <th class="top_head">Person</th>
        <th class="top_head">Event</th>
        <th class="top_head">Date</th>
        <th class="top_head">Description</th>
        <th class="top_head">Hours</th>
      </tr>

{assign var=prev_person value=''}
{foreach from=$person item=person_item}
  {assign var=prev_type value=''}
  {foreach from=$person_item->type key=type_name item=type_item}
    {foreach from=$type_item->dates item=hour}
      <tr>
        <td>
      {if $person_item->name neq $prev_person}
        {assign var=prev_person value=$person_item->name}
        {$person_item->name}
      {/if}
        </td>
        <td>
      {if $type_name neq $prev_type}
        {assign var=prev_type value=$type_name}
        {$type_name|regex_replace:"|^[^/]*/(.*)/$|":'\1'}
      {/if}
        </td>
        <td>{$hour.date}</td>
        <td>{$hour.description}</td>
        <td class="number">{$hour.hours}</td>
      </tr>
    {/foreach}
      <tr>
        <td></td>
        <td></td>
        <th class="illness_sum" colspan="2">
          Total
        </th>
        <th class="number right_sum illness_sum">
          {$type_item->total_str}
        </td>
      </tr>
  {/foreach}
      <tr>
        <td class="separator" colspan="4"></td>
      </tr>
{/foreach}
    </table>
