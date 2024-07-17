<?php

namespace AccelaSearch\Search\Block\System\Config;

class PriceType implements \Magento\Framework\Option\ArrayInterface
{

    public const VAT_EXCLUDE = 0;
    public const VAT_INCLUDE = 1;

    public const VAT_EXCLUDE_LABEL = "Vat Excluded";
    public const VAT_INCLUDE_LABEL = "Vat Included";

    public function toOptionArray()
    {
        return [
            ['value' => self::VAT_INCLUDE, 'label' => __(self::VAT_INCLUDE_LABEL)],
            ['value' => self::VAT_EXCLUDE, 'label' => __(self::VAT_EXCLUDE_LABEL)]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::VAT_INCLUDE => __(self::VAT_INCLUDE_LABEL),
            self::VAT_EXCLUDE => __(self::VAT_EXCLUDE_LABEL)
        ];
    }

}
