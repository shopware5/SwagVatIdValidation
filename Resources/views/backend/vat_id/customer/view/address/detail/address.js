// {block name="backend/customer/view/address/detail/address"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.VatId.view.address.detail.Address', {
    override: 'Shopware.apps.Customer.view.address.detail.Address',

    configure: function () {
        var me = this,
            fields = me.callParent(arguments);

        fields.fieldSets[0].fields.vatId.listeners = {
            change: me.onChangeVatId,
            scope: me
        };

        return fields;
    },

    onChangeVatId: function (textBox, newValue) {
        var me = this;

        newValue = newValue.replace(/\s|\W/g, '');

        textBox.setValue(newValue);
        me.record.set('vatId', newValue)
    },
});
// {/block}
