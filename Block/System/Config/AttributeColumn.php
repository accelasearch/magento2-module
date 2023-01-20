<?php
declare(strict_types=1);

namespace AccelaSearch\Search\Block\System\Config;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class AttributeColumn extends Select
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @param Context $context
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param array $data
     */
    public function __construct(
        Context                      $context,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        SortOrderBuilder             $sortOrderBuilder,
        array                        $data = []
    )
    {
        parent::__construct($context, $data);
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @param $value
     * @return WebsiteColumn
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * @return array
     */
    private function getSourceOptions(): array
    {
        $options = array();

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->setSortOrders([$this->sortOrderBuilder->create()->setField('frontend_label')->setDirection(SortOrder::SORT_ASC)]);
        $attributes = $this->attributeRepository->getList(ProductAttributeInterface::ENTITY_TYPE_CODE, $searchCriteria);

        foreach ($attributes->getItems() as $attribute) {
            $options[] = array(
                'label' => $attribute->getData("frontend_label"),
                'value' => $attribute->getAttributeCode()
            );
        }

        return $options;
    }
}
