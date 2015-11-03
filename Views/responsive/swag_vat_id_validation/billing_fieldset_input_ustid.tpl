{block name="frontend_register_vatid"}
<div class="register--ustid">
{block name="frontend_register_vatid_message"}
	{if $displayMessage}
		<div class="alert is--warning is--rounded">
			<div class="alert--icon">
				<i class="icon--element icon--warning"></i>
			</div>
			<div class="alert--content">
				{block name="frontend_register_vatid_message_content"}
					<div data-targetSelector="a" data-modalbox="true"
						 data-title="{s namespace="frontend/swag_vat_id_validation/main" name="messages/modal/title"}Exclusions{/s}"
						 data-mode="ajax"
						 data-sizing="content"
						 data-content="">
						{s namespace="frontend/swag_vat_id_validation/main" name="messages/disabledGeneral"}For some countries, this field is not required.{/s}
						<a href="{url controller=SwagVatIdValidation action=getModal module=frontend}">
							{s namespace="frontend/swag_vat_id_validation/main" name="messages/disabled/moreInfo"}More information{/s}
						</a>
					</div>
				{/block}
			</div>
		</div>
	{/if}
{/block}
	{block name="frontend_register_vatid_field"}
		<input autocomplete="section-billing billing organization-vat-id" name="register[billing][ustid]" type="text"
			   placeholder="{s namespace="frontend/register/billing_fieldset" name='RegisterLabelTaxId'}{/s}{if $vatIdCheck.required}*{/if}"
			   id="register_billing_ustid" value="{$form_data.ustid|escape}"
			   class="register--field{if $error_flags.ustid} has--error{/if}"/>
	{/block}
</div>
{/block}