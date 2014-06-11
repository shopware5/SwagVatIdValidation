{extends file='frontend/account/index.tpl'}

{block name='frontend_account_index_error_messages'}
    {if $sErrorMessages || $vatIdCheck.errorMessages|count > 0}
        <div class="grid_16 error_msg">
            {if $sErrorMessages}
                {include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
            {/if}
            {include file="frontend/plugins/swag_vat_id_validation/error_message.tpl"}
        </div>
    {/if}
{/block}

{block name='frontend_checkout_confirm_error_messages' append}
    {include file="frontend/plugins/swag_vat_id_validation/error_message.tpl"}
{/block}

{* UST Id *}
{block name='frontend_register_billing_fieldset_input_ustid'}
    <div>
        <label for="register_billing_ustid"{if !$vatIdCheck.required} class="normal"{/if}>{se namespace='frontend/register/billing_fieldset' name='RegisterLabelTaxId'}{/se}{if $vatIdCheck.required}*{/if}:</label>
        <input name="register[billing][ustid]" type="text"  id="register_billing_ustid" value="{$form_data.ustid|escape}" class="text{if $vatIdCheck.required} required{/if}{if $error_flags.ustid} instyle_error{/if}" />
    </div>
{/block}