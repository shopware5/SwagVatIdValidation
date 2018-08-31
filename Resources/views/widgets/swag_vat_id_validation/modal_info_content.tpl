{block name="frontend_register_vatid_modal"}
    {block name="frontend_register_vatid_modal_content"}
        <div class="modal--box">
            {block name="frontend_register_vatid_modal_disabled_countries"}
                {if !empty($disabledCountries)}
                    <div class="alert is--info">
                        <div class="alert--icon">
                            <i class="icon--element icon--info"></i>
                        </div>
                        <div class="alert--content">
                            <b>
                                {block name="fronted_register_vatid_modal_disabled_countries_content"}
                                    {s namespace="frontend/swag_vat_id_validation/main" name="messages/modal/disabledCountries/withoutEU"}The following countries are not affected from VAT-ID validation:{/s}
                                {/block}
                            </b>
                            <ul style="list-style-type: none;">
                                {block name="frontend_register_vatid_modal_country_iteration_parent"}
                                    {foreach from=$disabledCountries item=country}
                                        {block name="frontend_register_vatid_modal_country_iteration_item"}
                                            <li>- {$country}</li>
                                        {/block}
                                    {/foreach}
                                {/block}
                            </ul>
                        </div>
                    </div>
                {/if}
            {/block}
        </div>
    {/block}
{/block}
