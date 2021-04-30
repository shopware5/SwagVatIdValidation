{extends file="parent:frontend/register/billing_fieldset.tpl"}

{block name='frontend_register_billing_fieldset_input_vatId'}
    <div class="register-vatId--validationContainer"
         data-SwagVatIdValidationPlugin="true"
         data-countryIsoIdList='{$countryIsoIdList}'
         data-vatIdIsRequired="{$vatIdIsRequired}">
        {$smarty.block.parent}
        <div class="vatId-validationContainer--vatId-hint required_fields">
            {s namespace="frontend/swag_vat_id_validation/main" name="hint/vatIdHint"}
                Please ensure that you enter your VAT ID correctly. Country code must be in capital letters and without spaces. e.g. GB1234578 / DE123456789 / BE9999999999
            {/s}
        </div>
    </div>
{/block}

{block name='frontend_register_billing_fieldset_input_vatId'}
    {include file="frontend/swag_vat_id_validation/billing_fieldset_input_vatid.tpl"}
    {$smarty.block.parent}
{/block}
