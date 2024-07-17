<?php

namespace AccelaSearch\Search\Block\System\Config;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ListingPrice implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(

        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder        $searchCriteriaBuilder
    )
    {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = array();

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributes = $this->attributeRepository->getList(ProductAttributeInterface::ENTITY_TYPE_CODE, $searchCriteria);

        foreach ($attributes->getItems() as $attribute) {
            $options[] = array(
                'label' => $attribute->getData("frontend_label"),
                'value' => $attribute->getAttributeCode()
            );
        }

        $options[] = array(
            'label' => __("Final Price (calculated by Magento)"),
            'value' => "final_price"
        );

        return $options;
    }
}
