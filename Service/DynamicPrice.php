<?php

namespace AccelaSearch\Search\Service;

use AccelaSearch\Search\Api\DynamicPriceInterface;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Logger;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogRule\Model\Rule;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Catalog\Helper\Data as TaxHelper;

class DynamicPrice implements DynamicPriceInterface
{
    const LISTING_PRICE_PATH = 'accelasearch_search/dynamicprice/listing_price';
    const CACHE_LIFETIME_PATH = 'accelasearch_search/dynamicprice/cache_lifetime';

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var Rule
     */
    private Rule $rule;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @var Emulation
     */
    private Emulation $emulation;
    /**
     * @var \AccelaSearch\Search\Model\Cache\Type\DynamicPrice
     */
    private \AccelaSearch\Search\Model\Cache\Type\DynamicPrice $dynamicPriceCache;
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param Logger $logger
     * @param Data $data
     * @param Emulation $emulation
     * @param Rule $rule
     */
    public function __construct(
        ProductRepositoryInterface                         $productRepository,
        PriceCurrencyInterface                             $priceCurrency,
        Logger                                             $logger,
        Data                                               $data,
        Emulation                                          $emulation,
        Rule                                               $rule,
        \AccelaSearch\Search\Model\Cache\Type\DynamicPrice $dynamicPriceCache,
        SerializerInterface                                $serializer,
        TaxHelper                                          $taxHelper
    )
    {
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->data = $data;
        $this->emulation = $emulation;
        $this->rule = $rule;
        $this->dynamicPriceCache = $dynamicPriceCache;
        $this->serializer = $serializer;
        $this->taxHelper = $taxHelper;
    }

    /**
     * @param string[] $ids
     * @param string|null $visitorType
     * @param string|null $currencyCode
     * @return mixed
     */
    public function getPrices(array $ids, string $visitorType = null, string $currencyCode = null)
    {
        $return = [];
        foreach ($ids as $id) {
            $cacheKey = $this->dynamicPriceCache->getCacheKey($id, $visitorType, $currencyCode);
            $cachedValue = $this->dynamicPriceCache->load($cacheKey);
            if ($cachedValue) {
                $cachedValue = $this->serializer->unserialize($this->dynamicPriceCache->load($cacheKey));
            }
            if (!$cachedValue) {
                try {
                    $product = $this->productRepository->get($id);
                    if ($product->getStatus() == Status::STATUS_ENABLED) {
                        $listingPrice = (float)$product->getData($this->data->getConfig(self::LISTING_PRICE_PATH));
                        $listingPrice = $this->taxHelper->getTaxPrice($product, $product->getData($this->data->getConfig(self::LISTING_PRICE_PATH)));
                        $sellingPrice = $this->taxHelper->getTaxPrice($product, $product->getFinalPrice());
                        if ($sellingPrice === 0.0) {
                            $sellingPrice = (float)$product->getData('price');
                            if ($sellingPrice === 0.0 && $product->getTypeId() === Configurable::TYPE_CODE) {
                                $productTypeInstance = $product->getTypeInstance();
                                $minPrice = 0;
                                foreach ($productTypeInstance->getUsedProducts($product) as $child) {
                                    $childPrice = $this->taxHelper->getTaxPrice($child, $child->getData('price'));
                                    $childFinal = $this->taxHelper->getTaxPrice($child, $child->getFinalPrice());
                                    if ($childFinal < $childPrice) {
                                        $childPrice = (float)$childFinal;
                                    }
                                    if ($child->isSaleable() && ($minPrice === 0 || $minPrice > $childPrice)) {
                                        $minPrice = $childPrice;
                                    }
                                }
                                $sellingPrice = $minPrice;
                            }
                        }
                        if ($product->getTypeId() === Configurable::TYPE_CODE) {
                            $productTypeInstance = $product->getTypeInstance();
                            $minPriceListing = 0;
                            $minPriceSelling = 0;
                            foreach ($productTypeInstance->getUsedProducts($product) as $child) {
                                $childPrice = $this->taxHelper->getTaxPrice($child, $child->getPrice());
                                $childSellingPrice = $this->taxHelper->getTaxPrice($child, $child->getFinalPrice());
                                if ($child->isSaleable() && ($minPriceSelling === 0 || $minPriceSelling > $childSellingPrice)) {
                                    $minPriceListing = $childPrice;
                                    $minPriceSelling = $childSellingPrice;
                                }
                            }
                            $listingPrice = $minPriceListing;
                            $sellingPrice = $minPriceSelling;
                        }
                        if (!empty($visitorType) && is_numeric($visitorType)) {
                            $product->setCustomerGroupId($visitorType);
                        }
                        if (!empty($visitorType) && is_numeric($visitorType)) {
                            foreach ($product->getTierPrices() as $tierPrice) {
                                if ($tierPrice->getQty() == 1 && $visitorType == $tierPrice->getCustomerGroupId() && ($sellingPrice == 0 || $sellingPrice > (float)$tierPrice->getValue())) {
                                    $sellingPrice = (float)$tierPrice->getValue();
                                }
                            }
                        }
                        $calculatedPrice = [
                            "id" => $id,
                            "listingPrice" => $this->priceCurrency->convert((float)$listingPrice, null, $currencyCode),
                            "sellingPrice" => $this->priceCurrency->convert((float)$sellingPrice, null, $currencyCode)
                        ];
                        $this->dynamicPriceCache->save($this->serializer->serialize($calculatedPrice), $cacheKey,
                            [\AccelaSearch\Search\Model\Cache\Type\DynamicPrice::CACHE_TAG],
                            $this->data->getConfig(self::CACHE_LIFETIME_PATH) ?: 3600);
                        $return[] = $calculatedPrice;
                    }
                } catch (Exception $exception) {
                    $this->logger->error("DynamicPrice getPrices - error retrieving prices for product with id " . $id);
                }
            } else {
                $return[] = $cachedValue;
            }
        }
        return $return;
    }
}
