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
	<div class="notice bold center">
		{s namespace="frontend/swag_vat_id_validation/main" name="messages/disabledOutsideEU"}This field is not required for countries outside the bounds of the EU{/s}
	</div>
	<div>
		<label for="register_billing_ustid"
			   class="normal">{s namespace='frontend/register/billing_fieldset' name='RegisterLabelTaxId'}{/s}{if $vatIdCheck.required}*{/if}
			:
		</label>
		<input name="register[billing][ustid]" type="text" id="register_billing_ustid" value="{$form_data.ustid|escape}"
			   class="text{if $error_flags.ustid} instyle_error{/if}"/>
	</div>
{/block}