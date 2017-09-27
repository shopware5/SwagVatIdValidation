{block name="frontend_error_messages_vat_id"}

    {if $vatIdCheck.errorMessages|count > 0 || $vatIdCheck.requiredButEmpty}

        {$message = ""}
        {if $vatIdCheck.errorMessages|count > 0}
            {$message = "<p>{s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRemoved"}{/s}</p>"}

            {foreach $vatIdCheck.errorMessages as $errorMessage}
                {$message = "{$message} {$errorMessage}<br>"}
            {/foreach}
        {/if}

        {if $vatIdCheck.requiredButEmpty}
            {$message = "{$message}<p>{s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRequired"}{/s}</p>"}

        {else}
            {$message = "{$message}<p>{s namespace="frontend/swag_vat_id_validation/main" name="messages/editYourBilling"}{/s}</p>"}
        {/if}

        {include file="frontend/_includes/messages.tpl" type="error" content=$message}
        
    {/if}

{/block}