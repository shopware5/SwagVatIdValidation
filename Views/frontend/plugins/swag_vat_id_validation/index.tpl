{extends file='frontend/account/index.tpl'}

{block name='frontend_index_content' prepend}
    {if $vatIdCheck}
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

{block name='frontend_checkout_confirm_error_messages' prepend}
    {if $vatIdCheck.success}
        <div class="success bold center">
            {se namespace="frontend/swag_vat_id_validation/main" name="messages/validationSuccessful"}{/se}
        </div>
    {/if}
{/block}

{block name='frontend_checkout_confirm_error_messages' append}
    {if $vatIdCheck.errors.messages|count > 0}
        <div class="error center bold">
            <p>{se namespace="frontend/swag_vat_id_validation/main" name="messages/notUsedForOrder"}{/se}</p>
            <p>{$vatIdCheck.errors.messages|implode:'<br>'}</p>
            {se namespace="frontend/swag_vat_id_validation/main" name="messages/editYourBilling"}{/se}
        </div>
    {/if}
{/block}

{* UST Id *}
{block name='frontend_register_billing_fieldset_input_ustid'}
    <div>
        <label for="register_billing_ustid" class="normal">{se namespace='frontend/register/billing_fieldset' name='RegisterLabelTaxId'}{/se}:</label>
        <input name="register[billing][ustid]" type="text"  id="register_billing_ustid" value="{$form_data.ustid|escape}" class="text required {if $error_flags.ustid}instyle_error{/if}" />
    </div>
{/block}