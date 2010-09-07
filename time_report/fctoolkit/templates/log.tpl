{if isset($LOG_ERROR) && count($LOG_ERROR) > 0}
  <div class="log_error">
    <span class='error_head'>Error:</span><br/>
    {foreach item=error from=$LOG_ERROR}
      <p class='error'>{$error.title}{if isset($error.message)}<br />{$error.message}{/if}</p>
    {/foreach}
  <!-- End error -->
</div>
{/if}
{if isset($LOG_INFO) && count($LOG_INFO) > 0}
  <div class="log_info">
    {foreach item=info from=$LOG_INFO}
      <p class='log_item'>{$info.title}{if isset($info.message)}<br />{$info.message}{/if}</p>
    {/foreach}
  </div>
  <!-- End info -->
{/if}