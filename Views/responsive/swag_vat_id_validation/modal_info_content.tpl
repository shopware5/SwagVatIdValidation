{block name="frontend_register_vatid_modal"}
	{block name="frontend_register_vatid_modal_content"}
		<div class="modal--box">
			{block name="frontend_register_vatid_modal_disabled_EU"}
				{if $disabledOutsideEU}
					<div class="alert is--info">
						<div class="alert--icon">
							<i class="icon--element icon--info"></i>
						</div>
						<div class="alert--content">
							{block name="frontend_register_vatid_modal_alert_disabled_outside_eu_content"}
								<b>{s namespace="frontend/swag_vat_id_validation/main" name="messages/disabledOutsideEU"}The VAT-ID field is not required for countries outside the EU.{/s}</b>
							{/block}
						</div>
					</div>
				{/if}
			{/block}

			{block name="frontend_register_vatid_modal_disabled_countries"}
				{if !empty($disabledCountries)}
					<div class="alert is--info">
						<div class="alert--icon">
							{if !$disabledOutsideEU}
								<i class="icon--element icon--info"></i>
							{/if}
						</div>
						<div class="alert--content">
							<b>
								{block name="fronted_register_vatid_modal_disabled_countries_content"}
									{if $disabledOutsideEU}
										{s namespace="frontend/swag_vat_id_validation/main" name="messages/modal/disabledCountries/withEU"}Beside that, the following countries are not affected from VAT-ID validation:{/s}
									{else}
										{s namespace="frontend/swag_vat_id_validation/main" name="messages/modal/disabledCountries/withoutEU"}The following countries are not affected from VAT-ID validation:{/s}
									{/if}
								{/block}
							</b>
							<br>
							{block name="frontend_register_vatid_modal_country_iteration_parent"}
								{foreach from=$disabledCountries item=country}
									{block name="frontend_register_vatid_modal_country_iteration_item"}
										- {$country}
										<br>
									{/block}
								{/foreach}
							{/block}
						</div>
					</div>
				{/if}

			{/block}
		</div>
	{/block}
{/block}