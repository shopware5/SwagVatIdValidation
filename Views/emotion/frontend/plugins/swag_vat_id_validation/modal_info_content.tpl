{block name="frontend_register_vatid_modal"}
	{block name="frontend_register_vatid_modal_content"}
		{block name="frontend_register_vatid_modal_disabled_countries"}
			{if !empty($disabledCountries)}
                <div class="ajax_modal_custom">
                    <div class="heading" style="height: 44px;">
                        <h2>{s namespace="frontend/swag_vat_id_validation/main" name="messages/modal/title"}Exclusions{/s}</h2>
                        <a href="#" class="modal_close" title=""></a>
                    </div>
                    <div class="inner_container">
                        <h2>
                            {block name="fronted_register_vatid_modal_disabled_countries_content"}
                                {s namespace="frontend/swag_vat_id_validation/main" name="messages/modal/disabledCountries/withoutEU"}For the following countries (in addition to countries outside the EU) the VAT-ID is not required:{/s}
                            {/block}
                        </h2>
                        <ul>
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
	{/block}
{/block}