{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_error_messages' append}
    {include file="swag_vat_id_validation/checkout_confirm_error_message.tpl"}
{/block}