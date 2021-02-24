<?php
namespace DigitalPenguin\MyCloudFulfillment\Fields;
use modmore\Commerce\Exceptions\ViewException;
use modmore\Commerce\Order\Field\AbstractField;

/**
 * Class MailChimpSubscriptionField
 *
 * Renders a view of the subscribed MailChimp user.
 * @package modmore\Commerce_MailChimp\Fields
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