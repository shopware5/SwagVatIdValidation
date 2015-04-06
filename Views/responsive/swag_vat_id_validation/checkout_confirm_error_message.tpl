{block name='frontend_checkout_error_messages_vat_id_error'}
    {if $vatIdCheck.errorMessages|count > 0}
        {capture name="vatIdError"}
            <p>{s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRemoved"}{/s}</p>
            <p>{$vatIdCheck.errorMessages|implode:'<br>'}</p>
            {s namespace="frontend/swag_vat_id_validation/main" name="messages/editYourBilling"}{/s}
        {/capture}

        {include file="frontend/_includes/messages.tpl" type="error" content=$smarty.capture.vatIdError}
    {/if}
{/block}