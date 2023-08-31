<?php

namespace AccelaSearch\Search\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ConfigurableProducts implements OptionSourceInterface
{
    const CHILDREN_ONLY = 1;
    const CONFIGURABLES_ONLY = 2;
    const CONFIGURABLES_AND_CHILDREN = 3;

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
                'value' => self::CHILDREN_ONLY,
                'label' => __('Only simple children')
            ],
            [
                'value' => self::CONFIGURABLES_ONLY,
                'label' => __('Only configurables')
            ],
            [
                'value' => self::CONFIGURABLES_AND_CHILDREN,
                'label' => __('Configurables + children')
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
            self::CHILDREN_ONLY => __('Only children'),
            self::CONFIGURABLES_ONLY => __('Only configurables'),
            self::CONFIGURABLES_AND_CHILDREN => __('Configurables + children')
        ];
    }
}
