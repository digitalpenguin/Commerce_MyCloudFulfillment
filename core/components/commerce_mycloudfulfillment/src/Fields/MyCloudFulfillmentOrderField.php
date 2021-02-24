<?php
namespace DigitalPenguin\MyCloudFulfillment\Fields;
use modmore\Commerce\Exceptions\ViewException;
use modmore\Commerce\Order\Field\AbstractField;

/**
 * Class MyCloudFulfillmentOrderField
 *
 * Renders a view of the MyCloudFulfillment OrderField
 * @package DigitalPenguin\MyCloudFulfillment\Fields
 */
class MyCloudFulfillmentOrderField extends AbstractField {

    /**
     * Function: renderForAdmin
     *
     * @return string
     */
    public function renderForAdmin() {
        try {
            return $this->commerce->view()->render('mycloudfulfillment/fields/orderfield.twig', [
                'name' => $this->name,
                'value' => $this->value,
            ]);
        } catch (ViewException $e) {
            $this->commerce->adapter->log(1, '[' . __CLASS__ . '] ViewException rendering mycloudfulfillment/fields/orderfield.twig: ' . $e->getMessage());
            return 'Error rendering field.';
        }
    }
}