<?php
namespace AccelaSearch\Search\Block\System\Config;

use AccelaSearch\Search\Constants;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * Class Recipient
 * @package AccelaSearch\Search\Block\System\Config
 */
class Recipient extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        // Adding columns
        $this->addColumn(Constants::COLUMN_NAME_RECIPIENT,
            array(
                'label' => __('Recipient(s)'),
                'size' => 28,
                'class' => 'required-entry validate-email accelasearch-googleshopping-recipient'
            )
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Recipient');
    }

    /**
     * @param DataObject $row
     */
    protected function _prepareArrayRow(DataObject $row) {
        $options = [];
        $row->setData('option_extra_attrs', $options);
    }
}
