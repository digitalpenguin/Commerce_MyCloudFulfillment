<?php
/**
 * MyCloudFulfillment for Commerce.
 *
 * Copyright 2020 by Your Name <your@email.com>
 *
 * This file is meant to be used with Commerce by modmore. A valid Commerce license is required.
 *
 * @package commerce_mycloudfulfillment
 * @license See core/components/commerce_mycloudfulfillment/docs/license.txt
 */
class MyCloudFulfillmentShippingMethod extends comShippingMethod
{
    public function getModelFields()
    {
        $fields = [];
        $fields[] = new \modmore\Commerce\Admin\Widgets\Form\TextField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_mycloudfulfillment.shipping_id'),
            'description' => $this->adapter->lexicon('commerce_mycloudfulfillment.shipping_id_desc'),
            'name' => 'properties[mcfshippingid]',
            'value' => $this->getProperty('mcfshippingid'),
        ]);
        return $fields;
    }
}