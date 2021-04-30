(function($) {

    $.plugin('swagVatIdValidation', {
        defaults: {
            countryIsoIdList: [],

            vatIdIsRequired: false,

            vatIdFieldSelector: 'input[name="register[billing][vatId]"]',

            countryFieldSelector: '#country'
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
            this.vatIdField.on('keyup', $.proxy(this.onChangeVatId, this));

            if (this.opts.vatIdIsRequired === false) {
                this.removeVatIdRequirement();
                return;
            }

            this.countryField.on('change', $.proxy(this.onChangeCountry, this))
        },

        onChangeCountry: function() {
            if (this.opts.countryIsoIdList.indexOf(this.countryField.val()) === -1) {
                this.removeVatIdRequirement();
            } else {
                this.vatIdField.attr('required', 'required');
            }
        },

        onChangeVatId: function() {
            this.vatIdField.val(this.vatIdField.val().toUpperCase().replace(/\s|\W/g, ''));
        },

        removeVatIdRequirement: function() {
            this.vatIdField.removeAttr('required');
        },
    });

    $(function() {
        $('*[data-SwagVatIdValidationPlugin="true"]').swagVatIdValidation();
    });

})(jQuery);
