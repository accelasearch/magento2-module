<?php

namespace AccelaSearch\Search\Model;

use AccelaSearch\Search\Constants;
use AccelaSearch\Search\Helper\Data;
use Exception;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\FileSystemException;
use AccelaSearch\Search\Logger\Logger;
use Magento\Store\Model\ScopeInterface;
use AccelaSearch\Search\Model\Config\Source\ConfigurableProducts as ProductTypeToExport;

/**
 * Class FeedFile
 * @package AccelaSearch\Search\Model
 */
class FeedFile
{
    protected $_product;
    protected $_finalFeedFile;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * @var File
     */
    protected $_io;
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /**
     * @var FeedFields
     */
    private $_feedFields;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * FeedFile constructor.
     *
     * @param DirectoryList $directoryList
     * @param File $io
     * @param ProductRepository $productRepository
     * @param FeedFields $feedFields
     * @param Logger $logger
     * @param Data $dataHelper
     */
    public function __construct(
        DirectoryList $directoryList,
        File $io,
        ProductRepository $productRepository,
        FeedFields $feedFields,
        Logger $logger,
        Data $dataHelper
    ) {
        $this->_directoryList = $directoryList;
        $this->_io = $io;
        $this->_productRepository = $productRepository;
        $this->_feedFields = $feedFields;
        $this->_logger = $logger;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Write the feed file
     *
     * @param $feedDirectory
     * @param $store
     * @param $feedProducts
     * @return array
     */
    public function writeFeedFile($feedDirectory, $store, $feedProducts)
    {
        // directory check and creation
        $ret = $this->_checkAndCreateDir($feedDirectory);
        if (!$ret["success"]) {
            return $ret;
        }
        $feedDir = $ret["feedDir"];

        // Feed File
        $feedFilename = Constants::FEED_FILE_NAME . '_' . $store->getCode() . '.xml';
        // Feed Complete File
        $feedCompleteFilename = $feedDir . $feedFilename;

        // opening file in writable mode
        $this->_finalFeedFile = fopen($feedCompleteFilename, 'w');
        if ($this->_finalFeedFile === false) {
            return array("success" => false, "message" => "Opening file $feedCompleteFilename error!");
        }

        $ret = $this->_writeXMLFeedFile($feedProducts, $store);

        // closing file
        fclose($this->_finalFeedFile);

        if (!$ret["success"]) {
            return $ret;
        }

        return array("success" => true);
    }

    /**
     * @param $feedDirectory
     * @return array
     */
    private function _checkAndCreateDir($feedDirectory)
    {
        try {
            // Feed Directory
            $feedDir = $this->_directoryList->getPath(DirectoryList::ROOT)
                . DIRECTORY_SEPARATOR
                . $feedDirectory
                . DIRECTORY_SEPARATOR;
            $this->_io->checkAndCreateFolder($feedDir, 0775);

        } catch (FileSystemException $exception) {
            return array(
                "success" => false,
                "message" => "Directory $feedDir creation error: " . $exception->getMessage()
            );
        } catch (Exception $exception) {
            return array(
                "success" => false,
                "message" => "Directory check and creation error: " . $exception->getMessage()
            );
        }

        return array("success" => true, "feedDir" => $feedDir);
    }

    /**
     * Writing feed file in XML format
     *
     * @param $feedProducts
     * @param $store
     * @return array
     */
    private function _writeXMLFeedFile($feedProducts, $store)
    {
        try {
            fwrite($this->_finalFeedFile, '<?xml version="1.0" encoding="utf-8" ?>' . "\n");
            fwrite($this->_finalFeedFile, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n");
            fwrite($this->_finalFeedFile, "<channel>\n"); //Tag inizio file
            fwrite($this->_finalFeedFile, "<title><![CDATA[Data feed]]></title>\n");
            fwrite($this->_finalFeedFile, "<link><![CDATA[]]></link>\n");
            fwrite($this->_finalFeedFile, "<description><![CDATA[Data feed description.]]></description>\n");

            $productsBehavior = $this->dataHelper->getConfig(
                'accelasearch_search/feed/products_behavior',
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );

            // PRODUCTS ITERATION
            foreach ($feedProducts as $feedProduct) {
                try {



                    if (!$this->isProductEnabled($feedProduct, $store)) {
                        continue;
                    }

                    $isProductVisible = $this->isProductVisible($feedProduct);

                    if (!$isProductVisible) {
                        // all simple and configurable must be visible in order to export
                        if ($productsBehavior == ProductTypeToExport::CONFIGURABLE_AND_SIMPLE ||
                            $productsBehavior == ProductTypeToExport::SIMPLE_ONLY) {
                            continue;
                        }
                    }

                    if($isProductVisible && $productsBehavior == ProductTypeToExport::CONFIGURABLE_AND_SIMPLE) {
                        // child product but visible on catalog
                        if(isset($feedProduct['parent_id'])) {
                            unset($feedProduct['parent_id']);
                        }
                    }


                    if (!$this->isParentEnable($store, $feedProducts, $feedProduct)) {
                        continue;
                    }

                    $productId = $feedProduct['entity_id'];
                    /* id (required) */
                    $productSku = $feedProduct['sku'];
                    if (!$productId || !$productSku) {
                        continue;
                    }
                    $this->_product = $this->_productRepository->getById($productId, false, $store->getId(), true);
                    $this->_feedFields->setup($this->_product, $productSku, $store->getId());

                    $superAttributeList = [];
                    $children = null;
                    if ($feedProduct['type_id'] == 'configurable') {
                        $typeInstance = $this->_product->getTypeInstance()->setStoreFilter($store->getId(), $this->_product);

                        $children = $typeInstance->getUsedProducts($this->_product);

                        $attributes = $typeInstance->getConfigurableAttributes($this->_product);
                        foreach ($attributes as $attribute) {
                            $attributeCode = $attribute->getProductAttribute()->getAttributeCode();;
                            $superAttributeList[$attribute->getAttributeId()] = $attributeCode;
                        }
                    }

                    if ($feedProduct['type_id'] == 'bundle' || $feedProduct['type_id'] == 'grouped') {
                        $children = $this->_product->getTypeInstance()->getAssociatedProducts($this->_product);
                    }

                    $configurableOptions = [];
                    $minPrice = 0;
                    $minSpecial = 0;

                    if (isset($children)) {
                        foreach ($children as $child) {

                            $childListingPrice = $this->dataHelper->getListingPrice($child, $store);
                            $childSellingPrice = $this->dataHelper->getSellingPrice($child, $store);

                            $minPrice = $minPrice == 0 || $childListingPrice < $minPrice ? $childListingPrice : $minPrice;
                            $minSpecial = $minSpecial == 0 || $childSellingPrice < $minSpecial ? $childSellingPrice : $minSpecial;

                            if ($productsBehavior != ProductTypeToExport::CONFIGURABLE_AND_CHILDREN_AND_SIMPLE) {
                                continue;
                            }

                            foreach ($superAttributeList as $attributeCode) {
                                $configurableOptions[$child->getSku()][$attributeCode] = $this->dataHelper->getAttributeText($attributeCode, $child);
                            }
                        }
                    }

                    // getting required product data
                    /* title (required) */
                    $productName = $this->_feedFields->getName();
                    if (!$productName) {
                        $this->_logger->warning("product $productSku skipped Name missing " . $productName);
                        continue;
                    }
                    /* Categories (optional) */
                    $result = $this->_feedFields->_buildCategoriesTree($productId, $store);
                    if (!$result["success"] || $result["categories"] === '') {
                        $this->_logger->error("No Categories found for product:" . $productSku);
                        $categories = '';
                    } else {
                        $categories = strip_tags(strtolower($result["categories"]));
                        $this->_logger->info("Categories: " . $categories);
                    }
                    /* description (required) */
                    $productDescription = $this->_feedFields->getDescription();
                    /* link (required) */
                    $productLink = $this->_feedFields->getLink();
                    if (!$productLink) {
                        $this->_logger->warning("product $productSku skipped Link missing " . $productLink);
                        continue;
                    }
                    /* image_link (required) */
                    $productImageLink = $this->_feedFields->getImageLink();
                    if (!$productImageLink) {
                        $this->_logger->warning("product $productSku skipped Image Link missing " . $productImageLink);
                        continue;
                    }
                    /* availability (required) */
                    $productAvailability = $this->_feedFields->getAvailability();
                    if (!$productAvailability) {
                        $this->_logger->warning("product $productSku skipped Availability missing " . $productAvailability);
                        continue;
                    }
                    /* price (required) */
                    $productPrice = ($minPrice > 0) ? $minPrice : $this->dataHelper->getListingPrice($this->_product, $store);
                    if (!$productPrice) {
                        $this->_logger->warning("product $productSku skipped Price missing " . $productPrice);
                        continue;
                    }

                    $productSpecialPrice = ($minSpecial > 0) ? $minSpecial : $this->dataHelper->getSellingPrice($this->_product, $store);
                    if ($productSpecialPrice <= 0) {
                        $productSpecialPrice = $productPrice;
                    }

                    $productPrice = number_format($productPrice, 2, '.', '');
                    $productSpecialPrice = number_format($productSpecialPrice, 2, '.', '');
                    $scontoPercentuale = '';
                    if ($productSpecialPrice && $productSpecialPrice < $productPrice) {
                        $scontoPercentuale = round((($productPrice - $productSpecialPrice) * 100) / $productPrice);
                        $scontoPercentuale = number_format($scontoPercentuale, 0, '.', '') . '%';
                    }
                    /* currency (price required) */
                    $productCurrency = $this->_feedFields->getCurrency();
                    if (!$productCurrency) {
                        $this->_logger->warning("product $productSku skipped Currency missing " . $productCurrency);
                        continue;
                    }
                    fwrite($this->_finalFeedFile, "<item>");
                    /* id (required) */
                    fwrite($this->_finalFeedFile, "<g:id><![CDATA[" . $productSku . "]]></g:id>");
                    /* title (required) */
                    fwrite($this->_finalFeedFile, "<title><![CDATA[" . $productName . "]]></title>");
                    /* category (optional) */
                    fwrite($this->_finalFeedFile, "<g:product_type><![CDATA[" . $categories . "]]></g:product_type>");
                    /* description (required) */
                    fwrite($this->_finalFeedFile,
                        "<g:description><![CDATA[" . $productDescription . "]]></g:description>");
                    /* link (required) */
                    fwrite($this->_finalFeedFile, "<link><![CDATA[" . $productLink . "]]></link>");
                    /* image_link (required) */
                    fwrite($this->_finalFeedFile, "<g:image_link><![CDATA[" . $productImageLink . "]]></g:image_link>");
                    /* additional_image_link */
                    fwrite($this->_finalFeedFile,
                        "<g:additional_image_link><![CDATA[" . "]]></g:additional_image_link>");
                    /* availability (required) */
                    fwrite($this->_finalFeedFile,
                        "<g:availability><![CDATA[" . $productAvailability . "]]></g:availability>");
                    /* Price (required) with currency */
                    fwrite($this->_finalFeedFile,
                        "<g:price><![CDATA[" . $productPrice . " " . $productCurrency . "]]></g:price>");
                    /* Special (required) with currency */
                    fwrite($this->_finalFeedFile,
                        "<g:sale_price><![CDATA[" . $productSpecialPrice . " " . $productCurrency . "]]></g:sale_price>");
                    /* Brand */
                    fwrite($this->_finalFeedFile,
                        "<g:brand><![CDATA[" . $this->_feedFields->getBrand() . "]]></g:brand>");
                    /* GTIN */
                    $gTins = $this->_feedFields->getGtin();
                    if ($gTins || $gTins === '') {
                        $gTins = explode(",", $gTins);
                        foreach ($gTins as $gTin) {
                            fwrite($this->_finalFeedFile, "<g:gtin><![CDATA[" . $gTin . "]]></g:gtin>");
                        }
                    }
                    /* GTIN */
                    $mpns = $this->_feedFields->getMpn();
                    if ($mpns || $mpns === '') {
                        $mpns = explode(",", $mpns);
                        foreach ($mpns as $mpn) {
                            fwrite($this->_finalFeedFile, "<g:mpn><![CDATA[" . $mpn . "]]></g:mpn>");
                        }
                    }
                    /* Condition */
                    fwrite($this->_finalFeedFile, "<g:condition><![CDATA[new]]></g:condition>");

                    // if product is simple with parent, get parent sku
                    if (!empty($feedProduct['parent_id']) && ($feedProduct['type_id'] == 'simple' || $feedProduct['type_id'] == 'virtual' || $feedProduct['type_id'] == 'downloadable')) {
                        foreach ($feedProducts as $checkSkuParent) {
                            if ($checkSkuParent['entity_id'] == $feedProduct['parent_id']) {
                                fwrite($this->_finalFeedFile,
                                    "<g:item_group_id><![CDATA[" . $checkSkuParent['sku'] . "]]></g:item_group_id>");
                            }
                        }
                    }

                    if (!empty($configurableOptions)) {
                        foreach ($configurableOptions as $childSku => $options) {
                            fwrite($this->_finalFeedFile,
                                "<child_sku><![CDATA[" . $childSku . "]]></child_sku>");

                            foreach ($options as $attributeCode => $value) {
                                fwrite($this->_finalFeedFile,
                                    "<" . $attributeCode . "><![CDATA[" . $value . "]]></" . $attributeCode . ">");
                            }
                        }
                    }

                    /* Shipping OPEN */
                    fwrite($this->_finalFeedFile, "<g:shipping>");
                    /* Shipping Country */
                    fwrite($this->_finalFeedFile,
                        "<g:country><![CDATA[" . $this->_feedFields->getShippingCountry($store) . "]]></g:country>");
                    /* Shipping Region */
                    fwrite($this->_finalFeedFile, "<g:region><![CDATA[]]></g:region>");
                    /* Shipping Service */
                    fwrite($this->_finalFeedFile, "<g:service><![CDATA[]]></g:service>");
                    /* Shipping Price */
                    fwrite($this->_finalFeedFile,
                        "<g:price><![CDATA[" . $this->_feedFields->getShippingCost() . " " . $productCurrency . "]]></g:price>");
                    /* Shipping CLOSE */
                    fwrite($this->_finalFeedFile, "</g:shipping>");
                    $customMultipleFields = $this->dataHelper->getCustomMultipleFields();
                    foreach ($customMultipleFields as $customField) {
                        if (!empty($customField["node_name"]) && !empty($customField["attribute_code"])) {
                            $attributeValues = $this->_feedFields->getCustomAttribute($customField["attribute_code"]);
                            if (is_array($attributeValues)) {
                                foreach ($attributeValues as $value) {
                                    fwrite($this->_finalFeedFile,
                                        "<" . $customField["node_name"] . "><![CDATA[" . $value . "]]></" . $customField["node_name"] . ">");
                                }
                            } elseif (!empty($attributeValues)) {
                                fwrite($this->_finalFeedFile,
                                    "<" . $customField["node_name"] . "><![CDATA[" . $attributeValues . "]]></" . $customField["node_name"] . ">");
                            }
                        }
                    }
                    /* AS */
                    $customFields = $this->dataHelper->getCustomFields();
                    foreach ($customFields as $customField) {
                        if (!empty($customField["node_name"]) && !empty($customField["attribute_code"])) {
                            fwrite($this->_finalFeedFile,
                                "<" . $customField["node_name"] . "><![CDATA[" . $this->_feedFields->getCustomAttribute($customField["attribute_code"], true) . "]]></" . $customField["node_name"] . ">");
                        }
                    }
                    fwrite($this->_finalFeedFile, "<sconto><![CDATA[" . $scontoPercentuale . "]]></sconto>");
                    fwrite($this->_finalFeedFile, "</item>\n");
                } catch (Exception $exception) {
                    $this->_logger->warning("product $productSku skipped, error: " . $exception->getMessage());
                }
            }

            fwrite($this->_finalFeedFile, "</channel>\n"); //Tag fine file
            fwrite($this->_finalFeedFile, "</rss>"); //Tag fine file

        } catch (Exception $exception) {
            return array("success" => false, "message" => "XML Feed File creation error: " . $exception->getMessage());
        }

        return array("success" => true);
    }

    protected function isParentEnable($store, $feedProducts, $feedProduct)
    {
        // if parent id is empty, the product is a configurable or a simple directly saleable
        if (empty($feedProduct['parent_id'])) {
            return true;
        }

        $productsBehavior = $this->dataHelper->getConfig(
            'accelasearch_search/feed/products_behavior',
            ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );

        if ($productsBehavior == ProductTypeToExport::CONFIGURABLE_AND_CHILDREN_AND_SIMPLE) {
            foreach ($feedProducts as $checkStatusParent) {
                if ($checkStatusParent['entity_id'] == $feedProduct['parent_id']) {
                    if($this->isProductEnabled($checkStatusParent,$store)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param $feedProduct
     * @param $store
     * @return bool
     */
    private function isProductEnabled($feedProduct, $store) {

        if(isset($feedProduct["cpestatus_value_".$store->getId()])) {
            $storeEnabled = $feedProduct["cpestatus_value_".$store->getId()] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            if($storeEnabled) {
                return true;
            }
        } elseif(isset($feedProduct["cpestatus_value_0"])) {
            $defaultEnabled = $feedProduct["cpestatus_value_0"] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            if($defaultEnabled) {
                return true;
            }
        }

        return false;
    }

    private function isProductVisible($feedProduct)
    {

        $checkVisibility = [
            Visibility::VISIBILITY_BOTH,
            Visibility::VISIBILITY_IN_SEARCH,
            Visibility::VISIBILITY_IN_CATALOG
        ];
        if(in_array($feedProduct['visibility'], $checkVisibility)) {
            return true;
        }

        return false;

    }
}
