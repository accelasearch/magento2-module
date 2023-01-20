<?php
namespace AccelaSearch\Search\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @api
 * @since 100.0.2
 */
class ExcludedIncluded implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('Included')],
            ['value' => 0, 'label' => __('Excluded')],
            ['value' => '', 'label' => ' ']];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['' => ' ' , 0 => __('Excluded'), 1 => __('Included')];
    }
}
