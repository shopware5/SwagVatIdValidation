{extends file="parent:frontend/register/billing_fieldset.tpl"}

{block name='frontend_register_billing_fieldset_input_vatId'}
    <div class="register-vatId--validationContainer" data-SwagVatIdValidationPlugin="true", data-countryIsoIdList='{$countryIsoIdList}'>
        {$smarty.block.parent}
    </div>
{/block}

{block name='frontend_register_billing_fieldset_input_vatId'}
    {include file="frontend/swag_vat_id_validation/billing_fieldset_input_vatid.tpl"}
    {$smarty.block.parent}
{/block}
