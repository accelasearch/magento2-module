<?php
namespace AccelaSearch\Search\Block\System\Config;

use AccelaSearch\Search\Constants;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * Class Price
 * @package AccelaSearch\Search\Block\System\Config
 */
class Price extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        // Adding the columns
        $this->addColumn(Constants::COLUMN_NAME_FROM_PRICE,
            array(
                'label' => __('From Price'),
                'size' => 28
            )
        );
        $this->addColumn(Constants::COLUMN_NAME_COST,
            array(
                'label' => __('Shipping Cost'),
                'size' => 28
            )
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Price Range');
    }

    /**
     * @param DataObject $row
     */
    protected function _prepareArrayRow(DataObject $row) {
        $options = [];
        $row->setData('option_extra_attrs', $options);
    }
}
