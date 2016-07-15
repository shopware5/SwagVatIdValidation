{if $sErrorMessages || $vatIdCheck.errorMessages|count > 0 || $vatIdCheck.requiredButEmpty}
    <div class="account--error">
        {if $sErrorMessages}
            {include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
        {/if}
        {if $vatIdCheck.errorMessages|count > 0 || $vatIdCheck.requiredButEmpty}
            {capture name="vatIdError"}
                {if $vatIdCheck.errorMessages|count > 0}
                    <p>{s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRemoved"}{/s}</p>
                    <p>{$vatIdCheck.errorMessages|implode:'<br>'}</p>
                {/if}

                {if $vatIdCheck.requiredButEmpty}
                    {s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRequired"}{/s}
                {else}
                    {s namespace="frontend/swag_vat_id_validation/main" name="messages/editYourBilling"}{/s}
                {/if}
            {/capture}

            {include file="frontend/_includes/messages.tpl" type="error" content=$smarty.capture.vatIdError}
        {/if}
    </div>
{/if}
