{if $vatIdCheck.errorMessages|count > 0}
    <div class="error center bold">
        <p>{se namespace="frontend/swag_vat_id_validation/main" name="messages/vatIdRemoved"}{/se}</p>
        <p>{$vatIdCheck.errorMessages|implode:'<br>'}</p>
        {se namespace="frontend/swag_vat_id_validation/main" name="messages/editYourBilling"}{/se}
    </div>
{/if}