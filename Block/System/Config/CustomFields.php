<?php

namespace AccelaSearch\Search\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class CustomFields extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        // Adding the columns
        $this->addColumn('node_name',
            array(
                'label' => __('Node name')
            )
        );
        $this->addColumn('attribute_code', [
            'label' => __('Attribute'),
            'class' => 'required-entry',
            'renderer' => $this->getAttributeRenderer()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add attribute');
    }

    /**
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $website = $row->getWebsite();
        if ($website !== null) {
            $options['option_' . $this->getWebsiteRenderer()->calcOptionHash($website)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return AttributeColumn|AttributeColumn&BlockInterface|BlockInterface
     * @throws LocalizedException
     */
    private function getAttributeRenderer()
    {
        return $this->getLayout()->createBlock(
            AttributeColumn::class,
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );
    }
}
