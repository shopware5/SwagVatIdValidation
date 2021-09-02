{extends file="parent:frontend/address/form.tpl"}

{block name='frontend_address_form_input_vatid'}
    <div class="register-vatId--validationContainer"
         data-SwagVatIdValidationPlugin="true"
         data-countryIsoIdList='{$countryIsoIdList}'
         data-vatIdIsRequired="{$vatIdIsRequired}">
        {$smarty.block.parent}
        <div class="vatId-validationContainer--vatId-hint vat_id_hint required_fields">
            {s namespace="frontend/swag_vat_id_validation/main" name="hint/vatIdHint"}{/s}
        </div>
    </div>
{/block}

{block name='frontend_register_billing_fieldset_input_vatId'}
    {include file="frontend/swag_vat_id_validation/billing_fieldset_input_vatid.tpl"}
    {$smarty.block.parent}
{/block}
