<?php

use DigitalPenguin\MyCloudFulfillment\Modules\MyCloudFulfillment;

/**
 * MyCloudFulfillment for Commerce.
 *
 * Copyright 2021 by Murray Wood at Digital Penguin - https://www.digitalpenguin.hk
 *
 * This file is meant to be used with Commerce by modmore. A valid Commerce license is required.
 *
 * @package commerce_mycloudfulfillment
 * @license See core/components/commerce_mycloudfulfillment/docs/license.txt
 */
class MyCloudFulfillmentOrderShipment extends comOrderShipment
{

    /**
     * Return an array of arrays with keys "label" and "value" to add to the order items grid in the shipment column.
     *
     * This should only be the most important at-a-glance information - consider making all values available via
     * getModelFields() in the modal or a custom shipment action.
     *
     * @return array[]
     */
    public function getShipmentDetails(): array
    {
        return [
            [
                'label' =>  'MyCloudFulfillment Number',
                'value' =>  $this->getProperty('mc_number')
            ],
            [
                'label' =>  'Tracking Link',
                'value' =>  $this->getProperty('tracking_url')
            ]
        ];
    }
}
