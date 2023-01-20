<?php
namespace AccelaSearch\Search\Model\Config\Backend;

use AccelaSearch\Search\Constants;
use Magento\Framework\Option\ArrayInterface;
use AccelaSearch\Search\Model\FeedFields;

/**
 * Class ShippingCost
 * @package AccelaSearch\Search\Model\Config\Backend
 */
class ShippingCost implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(
            array("value" => Constants::SHIPPING_PRICE_BY_ATTRIBUTE, "label" => __('by Magento attribute')),
            array("value" => Constants::SHIPPING_PRICE_BY_PRICE, "label" => __('by price')),
            array("value" => Constants::SHIPPING_PRICE_BY_WEIGHT, "label" => __('by weight'))
        );

        return $options;
    }
}
