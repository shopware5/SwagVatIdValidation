{if $vatIdCheck.errorMessages|count > 0}
    <div class="error center bold">
        <p>{s namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRemoved"}{/s}</p>
        <p>{$vatIdCheck.errorMessages|implode:'<br>'}</p>
        {s namespace="frontend/swag_vat_id_validation/main" name="messages/editYourBilling"}{/s}
    </div>
{/if}