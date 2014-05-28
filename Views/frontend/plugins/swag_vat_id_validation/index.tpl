{extends file='frontend/account/index.tpl'}

{block name='frontend_index_content' prepend}
    {if !$sErrorMessages && $vatIdCheck}
        {$sFormData.ustid = $vatIdCheck.vatId}
        {foreach $vatIdCheck.errors.messages as $message}
            {$sErrorMessages[] = $message}
        {/foreach}
        {foreach $vatIdCheck.errors.flags as $key => $flag}
            {$sErrorFlag.$key = true}
        {/foreach}
    {/if}
{/block}

{* Success messages *}
{block name="frontend_account_index_success_messages" append}
    {if $vatIdCheck.success}
        <div class="success bold center grid_16">
            {se namespace="frontend/swag_vat_id_validation/main" name="messages/validationSuccessful"}{/se}
        </div>
    {/if}
{/block}