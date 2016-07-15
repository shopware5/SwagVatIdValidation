{block name="frontend_register_vatid"}
    <div class="register--ustid">
        {block name="frontend_register_vatid_message"}
            {if $displayMessage}
                <div class="alert is--warning is--rounded">
                    <div class="alert--icon">
                        <i class="icon--element icon--warning"></i>
                    </div>
                    <div class="alert--content">
                        {block name="frontend_register_vatid_message_content"}
                            <div data-targetSelector="a" data-modalbox="true"
                                 data-title="{s namespace="frontend/swag_vat_id_validation/main" name="messages/modal/title"}Exclusions{/s}"
                                 data-mode="ajax"
                                 data-sizing="content"
                                 data-content="">
                                {s namespace="frontend/swag_vat_id_validation/main" name="messages/disabledGeneral"}For some countries, this field is not required.{/s}
                                <a href="{url module=widgets controller=SwagVatIdValidation action=modalInfoContent forceSecure}">
                                    {s namespace="frontend/swag_vat_id_validation/main" name="messages/disabled/moreInfo"}More information{/s}
                                </a>
                            </div>
                        {/block}
                    </div>
                </div>
            {/if}
        {/block}
    </div>
{/block}
