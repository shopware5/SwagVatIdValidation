UPDATE s_core_config_values SET `value` = "a:0:{}" WHERE id = 55;
UPDATE s_core_config_elements SET `value` =" a:0:{}" WHERE form_id = (SELECT id FROM s_core_config_forms WHERE `name` LIKE 'SwagVatIdValidation') AND `name` = 'disabledCountryISOs';


