<?php

namespace AccelaSearch\Search\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ConfigurableProducts implements OptionSourceInterface
{
    const SIMPLE_ONLY = 1;
    const CONFIGURABLE_AND_SIMPLE = 2;
    const CONFIGURABLE_AND_CHILDREN_AND_SIMPLE = 3;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => ''
            ],
            [
                'value' => self::SIMPLE_ONLY,
                'label' => __('Simple products only')
            ],
            [
                'value' => self::CONFIGURABLE_AND_SIMPLE,
                'label' => __('Configurable products and Simple products')
            ],
            [
                'value' => self::CONFIGURABLE_AND_CHILDREN_AND_SIMPLE,
                'label' => __('Configurable products + children + Simple products')
            ]
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            '' => '',
            self::SIMPLE_ONLY => __('Simple products only'),
            self::CONFIGURABLE_AND_SIMPLE => __('Configurable products and Simple products'),
            self::CONFIGURABLE_AND_CHILDREN_AND_SIMPLE => __('Configurable products + children + Simple products')
        ];
    }
}
