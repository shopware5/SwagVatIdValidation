(function($) {

    $.plugin('swagVatIdValidation', {
        defaults: {
            countryIsoIdList: [],

            vatIdFieldSelector: 'input[name="register[billing][vatId]"]',

            countryFieldSelector: '#country',
        },

        init: function() {
            this.applyDataAttributes();
            this.initializeInputFields();
            this.initializeEventListener();
        },

        convertIsoIdList: function() {
            this.europeCountryIds = JSON.parse(this.opts.countryIsoIdList);
        },

        initializeInputFields: function() {
            this.vatIdField = $(this.opts.vatIdFieldSelector);
            this.countryField = $(this.opts.countryFieldSelector);
        },

        initializeEventListener: function() {
            this.countryField.on('change', $.proxy(this.onChangeCountry, this))
            this.vatIdField.on('keyup', $.proxy(this.onChangeVatId, this));
        },

        onChangeCountry: function() {
            if (this.opts.countryIsoIdList.indexOf(this.countryField.val()) === -1) {
                this.vatIdField.removeAttr('required');
            } else {
                this.vatIdField.attr('required', 'required');
            }
        },

        onChangeVatId: function() {
            this.vatIdField.val(this.vatIdField.val().toUpperCase().replace(/\s|\W/g, ''));
        },
    });

    $(function() {
        $('*[data-SwagVatIdValidationPlugin="true"]').swagVatIdValidation();
    });

})(jQuery);