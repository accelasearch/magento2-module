<?php
namespace AccelaSearch\Search\Model\Config\Backend;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * Class AttributePriceType
 * @package AccelaSearch\Search\Model\Config\Backend
 */
class AttributePriceType implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_productAttributeCollectionFactory;

    /**
     * AttributePriceType constructor.
     *
     * @param CollectionFactory $productAttributeCollectionFactory
     */
    public function __construct(
        CollectionFactory $productAttributeCollectionFactory
    )
    {
        $this->_productAttributeCollectionFactory = $productAttributeCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->_productAttributeCollectionFactory->create()->addVisibleFilter();

        $options = array();
        foreach ($attributes as $attribute) {
            if ($attribute->getData('frontend_input') == 'price') {
                $options[] = array(
                    'label' => $attribute->getData('frontend_label'),
                    'value' => $attribute->getData('attribute_code')
                );
            }
        }

        return $options;
    }
}