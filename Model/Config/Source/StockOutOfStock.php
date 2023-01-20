<?php
namespace AccelaSearch\Search\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @api
 * @since 100.0.2
 */
class StockOutOfStock implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('In Stock')],
            ['value' => 0, 'label' => __('Out of Stock')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('Out of Stock'), 1 => __('In Stock')];
    }
}