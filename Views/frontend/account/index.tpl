{extends file="parent:frontend/account/index.tpl"}

{block name="frontend_account_index_error_messages"}
    {include file="frontend/swag_vat_id_validation/account_error_message.tpl"}
{/block}
