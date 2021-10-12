// {block name="backend/customer/view/address/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.VatId.view.address.detail.Window', {
    override: 'Shopware.apps.Customer.view.address.detail.Window',

    onSave: function() {
        var me = this;

        me.setLoading(true);
        me.validateVatId();
    },

    validateVatId: function() {
        var me = this;

        me.formPanel.getForm().updateRecord(me.record);

        Ext.Ajax.request({
            url: '{url controller="SwagVatIdValidation" action="validateVatId"}',
            params: me.record.data,
            success: function(response) {
                me.handleResponse(Ext.JSON.decode(response.responseText, true));
            }
        });
    },

    handleResponse: function(response) {
        var me = this;

        if (response.success === true) {
            me.fireSaveEvent();
            return;
        }

        if (response.success === false) {
            me.setLoading(false);
            me.showErrorMessageBox(response);
        }
    },

    fireSaveEvent: function() {
        var me = this;

        me.fireEvent(
            me.getEventName('save'), me, me.record
        );
    },

    showErrorMessageBox: function(response) {
        var me = this,
            errors = Object.values(response.errors).join('<br />');

        me.formPanel.getForm().findField('vatId').markInvalid(errors);

        Ext.Msg.show({
            title: 'Error',
            msg: errors,
            buttons: Ext.Msg.OK,
            icon: Ext.Msg.ERROR
        });
    },
});
// {/block}
