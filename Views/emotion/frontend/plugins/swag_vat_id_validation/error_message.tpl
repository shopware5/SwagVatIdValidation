{if $vatIdCheck.errorMessages|count > 0 || $vatIdCheck.requiredButEmpty}
    <div class="error center bold">
        {if $vatIdCheck.errorMessages|count > 0}
            <p>{s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRemoved"}{/s}</p>
            <p>{$vatIdCheck.errorMessages|implode:'<br>'}</p>
        {/if}

        {if $vatIdCheck.requiredButEmpty}
            {s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRequired"}{/s}
        {else}
            {s namespace="frontend/swag_vat_id_validation/main" name="messages/editYourBilling"}{/s}
        {/if}
    </div>
{/if}