{if $sErrorMessages || $vatIdCheck.errorMessages|count > 0 || $vatIdCheck.requiredButEmpty}
    <div class="account--error">
        {if $sErrorMessages}
            {include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
        {/if}

        {include file="frontend/swag_vat_id_validation/error_template.tpl"}
    </div>
{/if}
