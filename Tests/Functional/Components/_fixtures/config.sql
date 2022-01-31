SET @formID = (SELECT id FROM s_core_config_forms WHERE name LIKE 'SwagVatIdValidation');

INSERT INTO `s_core_config_values` (`id`, `element_id`, `shop_id`, `value`) VALUES
(51, (SELECT id FROM s_core_config_elements WHERE form_id = @formID AND name = 'vatId'), 1, 's:3:\"ASD\";'),
(52, (SELECT id FROM s_core_config_elements WHERE form_id = @formID AND name = 'shopEmailNotification'), 2, 'i:1;'),
(53, (SELECT id FROM s_core_config_elements WHERE form_id = @formID AND name = 'apiValidationType'), 2, 'i:2;'),
(54, (SELECT id FROM s_core_config_elements WHERE form_id = @formID AND name = 'confirmation'), 1, 'b:0;'),
(55, (SELECT id FROM s_core_config_elements WHERE form_id = @formID AND name = 'disabledCountryISOs'), 1, 'a:1:{i:0;s:2:"AT";}'),
(56, (SELECT id FROM s_core_config_elements WHERE form_id = @formID AND name = 'vatId_is_required'), 2, 'b:1;');
