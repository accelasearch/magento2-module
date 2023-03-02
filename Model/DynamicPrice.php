<?php

namespace AccelaSearch\Search\Model;

use AccelaSearch\Search\Api\DynamicPriceInterface;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Logger;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\App\Area;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\App\Emulation;

class DynamicPrice implements DynamicPriceInterface
{
    const LISTING_PRICE_PATH = 'accelasearch_search/dynamicprice/listing_price';

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
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param Logger $logger
     * @param Data $data
     * @param Emulation $emulation
     * @param Rule $rule
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface     $priceCurrency,
        Logger                     $logger,
        Data                       $data,
        Emulation                  $emulation,
        Rule                       $rule
    )
    {
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->data = $data;
        $this->emulation = $emulation;
        $this->rule = $rule;
    }

    /**
     * @inheritdoc
     */
    public function getPrices($ids, string $visitorType = null, string $currencyCode = null)
    {
        $return = [];
        foreach ($ids as $id) {
            try {
                $product = $this->productRepository->getById($id);
                $listingPrice = (float)$product->getData($this->data->getConfig(self::LISTING_PRICE_PATH));
                $sellingPrice = (float)$product->getSpecialPrice();
                if (!empty($visitorType)) {
                    $product->setCustomerGroupId($visitorType);
                }
                $this->emulation->startEnvironmentEmulation(1, Area::AREA_FRONTEND, true);
                $afterRulesPrice = $this->rule->calcProductPriceRule($product, $product->getPrice());
                if ($sellingPrice > $afterRulesPrice) {
                    $sellingPrice = $afterRulesPrice;
                }
                $this->emulation->stopEnvironmentEmulation();
                if (!empty($visitorType)) {
                    foreach ($product->getTierPrices() as $tierPrice) {
                        if ($tierPrice->getQty() == 1 && $visitorType == $tierPrice->getCustomerGroupId() && $sellingPrice > (float)$tierPrice->getValue()) {
                            $sellingPrice = (float)$tierPrice->getValue();
                        }
                    }
                }
                $return[] = [
                    "id" => $id,
                    "listingPrice" => $this->priceCurrency->convert((float)$listingPrice, null, $currencyCode),
                    "sellingPrice" => $this->priceCurrency->convert((float)$sellingPrice, null, $currencyCode)
                ];
            } catch (Exception $exception) {
                $this->logger->error("DynamicPrice getPrices - error retrieving prices for product with id " . $id);
            }
        }
        return $return;
    }
}
