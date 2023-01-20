<?php
namespace AccelaSearch\Search\Model\Config\Backend;

use AccelaSearch\Search\Constants;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class BrandAttribute
 * @package AccelaSearch\Search\Model\Config\Backend
 */
class BrandAttribute implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array("value" => Constants::BRAND_ATTRIBUTE_DEFAULT, "label" => __("Manufacturer (Magento)")),
            array("value" => Constants::BRAND_ATTRIBUTE_BRAND, "label" => __("Brand (Custom attribute)")),
            array("value" => Constants::BRAND_ATTRIBUTE_FREETEXT, "label" => __("Free text"))
        );
    }
}
