<?php

namespace AccelaSearch\Search\Model;

use AccelaSearch\Search\Constants;
use Exception;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Model\ScopeInterface;
use AccelaSearch\Search\Model\Shipping\ShippingCost;
use Magento\Framework\Exception\NoSuchEntityException;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Logger;
use AccelaSearch\Search\Logger\Verbose;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Class FeedFields
 * @package AccelaSearch\Search\Model
 */
class FeedFields
{
    private $productAttributes = [];
    // product
    protected $_product;
    // store Id
    protected $_storeId;
    // product
    protected $_productSku;
    protected $_baseUrl;
    protected $_vsfResizeX;
    protected $_vsfResizeY;
    //category
    protected $_category_entity_type;
    protected $_category_name_attribute;
    // secure base url
    protected $_secureBaseUrl = array();
    // array of simple parent products
    private $_parentsArray = array();

    /**
     * @var CategoryRepository
     */
    protected $_categoryRepository;

    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistry;
    /**
     * @var ShippingCost
     */
    protected $_shippingCost;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var Verbose
     */
    protected $_verboseLogger;
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /**
     * @var Configurable
     */
    protected $_catalogProductTypeConfigurable;
    /**
     * @var Grouped
     */
    protected $_catalogProductTypeGrouped;
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * FeedFields constructor.
     *
     * @param StockRegistryInterface $stockRegistry
     * @param ShippingCost $shippingCost
     * @param Data $helper
     * @param Logger $logger
     * @param Verbose $verboseLogger
     * @param ProductRepository $productRepository
     * @param Configurable $catalogProductTypeConfigurable
     * @param Grouped $catalogProductTypeGrouped
     * @param CategoryRepository $categoryRepository
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        StockRegistryInterface              $stockRegistry,
        ShippingCost                        $shippingCost,
        Data                                $helper,
        Logger                              $logger,
        Verbose                             $verboseLogger,
        ProductRepository                   $productRepository,
        Configurable                        $catalogProductTypeConfigurable,
        Grouped                             $catalogProductTypeGrouped,
        CategoryRepository                  $categoryRepository,
        ProductAttributeRepositoryInterface $attributeRepository
    )
    {
        $this->_stockRegistry = $stockRegistry;
        $this->_shippingCost = $shippingCost;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_verboseLogger = $verboseLogger;
        $this->_productRepository = $productRepository;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_catalogProductTypeGrouped = $catalogProductTypeGrouped;
        $this->_categoryRepository = $categoryRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Sets some local variables
     *
     * @param $product
     * @param $productSku
     * @param int $storeId
     */
    public function setup($product, $productSku, $storeId = 0)
    {
        $this->_product = $product;
        $this->_productSku = $productSku;
        $this->_storeId = $storeId;
        // Secure Base Url
        $this->_baseUrl = $this->_getSecureBaseUrl($storeId);

        $customBaseUrl = $this->_helper->getConfig(
            Constants::PATH_FEED_CUSTOM_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($customBaseUrl) {
            $this->_baseUrl = $customBaseUrl;
        }

        $this->_vsfResizeX = $this->_helper->getConfig(
            Constants::PATH_FEED_VSF_RESIZE_X,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $this->_vsfResizeY = $this->_helper->getConfig(
            Constants::PATH_FEED_VSF_RESIZE_Y,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the product name
     *
     * @return mixed|null
     */
    public function getName()
    {
        $productName = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productName;
        }

        if ($this->_product->getData('name')
            && $this->_product->getData('name') != '') {
            $productName = str_replace(
                chr(9),
                '',
                str_replace(
                    chr(13),
                    '',
                    str_replace(
                        chr(10),
                        '',
                        $this->_product->getData('name')
                    )
                )
            );
        } else {
            $this->_verboseLogger->error("No Name found for product:" . $this->_productSku);
        }

        return $productName;
    }


    /**
     * Categories tree building
     *
     * @param $productId
     * @param $storeView
     * @return array
     * @throws NoSuchEntityException
     */
    public function _buildCategoriesTree($productId, $storeView = 0)
    {

        // category entity type id
        $tableName = $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_EAV_ENTITY_TYPE);
        $sqlCategoryEntityTypeIdSel = "SELECT entity_type_id FROM $tableName WHERE entity_type_code = 'catalog_category' LIMIT 0, 1";
        try {
            $stmCategoryEntityTypeIdSel = $this->_helper->dbmage_read->fetchAll($sqlCategoryEntityTypeIdSel);
            if ($stmCategoryEntityTypeIdSel) {
                $this->_category_entity_type = $stmCategoryEntityTypeIdSel[0]['entity_type_id'];
            }
        } catch (Exception $exception) {
            return array("success" => false, "message" => "Category entity type error: " . $exception->getMessage());
        }

        // category name attribute
        $tableName = $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_EAV_ATTRIBUTE);
        $sqlCategoryNameAttributeSel = "SELECT attribute_id FROM $tableName WHERE attribute_code = 'name' AND entity_type_id = $this->_category_entity_type LIMIT 0, 1";
        try {
            $stmCategoryNameAttributeSel = $this->_helper->dbmage_read->fetchAll($sqlCategoryNameAttributeSel);
            if ($stmCategoryNameAttributeSel) {
                $this->_category_name_attribute = $stmCategoryNameAttributeSel[0]['attribute_id'];
            }
        } catch (Exception $exception) {
            return array("success" => false, "message" => "Category name attribute error: " . $exception->getMessage());
        }

        $_categories = '';
        $parent = " > ";
        $separator = ";";

        $categories = $this->_helper->getConfig(
            Constants::PATH_CATEGORIES_EXCLUDED,
            ScopeInterface::SCOPE_STORE,
            $storeView->getId()
        );
        $categoriesArray = explode(",", $categories);

        $tableCatalogCategoryProduct = $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_CATALOG_CATEGORY_PRODUCT);
        $tableCatalogCategoryEntity = $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_CATALOG_CATEGORY_ENTITY);
        $tableCatalogCategoryEntityVarchar = $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_CATALOG_CATEGORY_ENTITY_VARCHAR);

        $sqlBuildCategoriesTreeSel = "SELECT ccp.category_id, cce.parent_id, ccev.value as name
                    FROM $tableCatalogCategoryProduct as ccp
                    JOIN $tableCatalogCategoryEntity as cce ON ccp.category_id = cce.entity_id
                    JOIN $tableCatalogCategoryEntityVarchar as ccev ON ccp.category_id = ccev.entity_id AND ccev.attribute_id = " . $this->_category_name_attribute . " AND ccev.store_id = 0
                    WHERE ccp.product_id = " . $productId . "
                    ORDER BY ccp.position, cce.position ";

        $this->_logger->info("Query Category: " . $sqlBuildCategoriesTreeSel);
        // query execution
        try {
            $elements = $this->_helper->dbmage_read->fetchAll($sqlBuildCategoriesTreeSel);
        } catch (Exception $exception) {
            return array("success" => false, "categories" => '', "message" => "Build categories tree error: " . $exception->getMessage());
        }

        // for each product categories
        foreach ($elements as $element) {
            // loading category
            if (in_array($element['category_id'], $categoriesArray)) {
                continue;
            }
            $category = $this->_categoryRepository->get($element['category_id'], $storeView->getId());
            // get category path (ex. 1/2/3/4)
            $categoryPath = $category->getPath();
            // category path element (ex. array(1,2,3,4))
            $categoryPathArray = str_replace("/", ",", $categoryPath);

//            $categoryPathArray = explode(',',$categoryPathArray);
//            if (($key = array_search(634, $categoryPathArray)) !== false) {
//                unset($categoryPathArray[$key]);
//                $categoryPathArray = array_values($categoryPathArray);
//            }
//            $categoryPathArray = implode(',',$categoryPathArray);


            // get categories collection
            $sqlGetCategoryPathNamesSel = "SELECT coalesce(ccev_lang.value,ccev.value) as name
                    FROM $tableCatalogCategoryEntity as cce
                    JOIN $tableCatalogCategoryEntityVarchar as ccev
                    ON cce.entity_id = ccev.entity_id AND ccev.attribute_id = " . $this->_category_name_attribute . " AND ccev.store_id = 0
                    left JOIN $tableCatalogCategoryEntityVarchar as ccev_lang
                    ON cce.entity_id = ccev_lang.entity_id AND ccev_lang.attribute_id = " . $this->_category_name_attribute . " AND ccev_lang.store_id = " . $storeView->getStoreId() . "
                    WHERE level >= 2
                    AND ccev.entity_id IN($categoryPathArray)";
            $this->_logger->info("Query categories collection " . $sqlGetCategoryPathNamesSel);

            // query execution
            try {
                $collection = $this->_helper->dbmage_read->fetchAll($sqlGetCategoryPathNamesSel);
            } catch (Exception $exception) {
                return array("success" => false, "categories" => '', "message" => "Get category path names error: " . $exception->getMessage());
            }

            $catName = '';
            // get the all path category names
            foreach ($collection as $cat) {
                if ($cat['name'] == 'trovaprezzi') continue;
                $catName = $cat['name'];
                // add category name replacing ';' character with space
                $_categories .= str_replace($separator, ' ', $catName);
                // add parent separator
                $_categories .= $parent;
            }
            // to delete '-' character after the last category
//            $_categories = substr($_categories,0,-1);
            // add final ';' character at the end
            if ($catName != '') {
                $_categories .= $separator;
            }
        }

        $categories = explode(';', $_categories);
        $finalCategory = '';
        $max = 0;
        $i = 0;
        foreach ($categories as $element) {
            $length = strlen($element);
            if (strlen($element) > $max) {
                $max = $length;
                $finalCategory = $element;
            }
            $i++;
        }

        $finalCategory = substr($finalCategory, 0, -3);
        $_categories = $finalCategory;
        return array("success" => true, "categories" => $_categories);
    }

    /**
     * Retrieves the product description
     *
     * @param int $storeId
     * @return bool|mixed|null|string
     */
    public function getDescription($storeId = 0)
    {
        $productDescription = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productDescription;
        }

        // set the store Id
        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // Product Description attribute
        $productDescriptionAttribute = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $productDescription = substr(
            html_entity_decode(
                (string)$this->_product->getData($productDescriptionAttribute),
                ENT_QUOTES
            ),
            0,
            5000
        ); // characters truncate
        $productDescription = strip_tags($productDescription);
        $productDescription = str_replace(
            ';',
            ',',
            str_replace(
                '|',
                ' ',
                str_replace(
                    '#',
                    ' ',
                    str_replace(
                        chr(9),
                        '',
                        str_replace(
                            chr(13),
                            '',
                            str_replace(
                                chr(10),
                                '',
                                $productDescription
                            )
                        )
                    )
                )
            )
        ); // deleting special characters
        $productDescription = mb_convert_encoding($productDescription, 'UTF-8', 'UTF-8');

        return $productDescription;
    }

    /**
     * Retrieves the product link
     *
     * @param int $storeId
     * @return null|string
     */
    public function getLink($storeId = 0)
    {
        $productUrlKey = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productUrlKey;
        }

        // set the store Id
        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // get, if it exists, parent Id
        $parentId = $this->_checkChildGetParent();

        // product
        if ($parentId === 0) {
            $_urlKey = ($this->_product->getData('url_key')) ? $this->_product->getData('url_key') : null;
        } // product parent
        else {
            // new parent
            if (!array_key_exists($parentId, $this->_parentsArray)) {
                $this->_saveParentValues($parentId, $storeId);
            }
            // getting Url Key from parentsArray
            $_urlKey = $this->_parentsArray[$parentId]['url_key'];
        }

        if ($_urlKey) {
            // Secure Base Url
            $base_url = $this->_getSecureBaseUrl($storeId);

            $customBaseUrl = $this->_helper->getConfig(
                Constants::PATH_FEED_CUSTOM_BASE_URL,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($customBaseUrl) {
                $base_url = $customBaseUrl;
            }


            // Seo Product Url Suffix
            $product_url_suffix = $this->_helper->getConfig(
                Constants::PATH_SEO_PRODUCTURLSUFFIX,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            // Product Url Key
            $productUrlKey = $base_url . $_urlKey . $product_url_suffix;
        } else {
            $this->_verboseLogger->error("No Url Key found for product:" . $this->_productSku);
        }

        return $productUrlKey;
    }

    /**
     * Retrieves the product image link
     *
     * @param int $storeId
     * @return null|string
     */
    public function getImageLink($storeId = 0)
    {
        $productImage = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productImage;
        }

        // set the store Id
        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // product image
        if ($this->_product->getData('image')
            && $this->_product->getData('image') != ''
            && $this->_product->getData('image') != Constants::NO_SELECTION) {
            // Product Url Key
            $productImage = $this->_product->getData('image');
        } // parent image
        else {
            // get, if it exists, parent Id
            $parentId = $this->_checkChildGetParent();
            // product parent
            if ($parentId !== 0) {
                // new parent
                if (!array_key_exists($parentId, $this->_parentsArray)) {
                    $this->_saveParentValues($parentId, $storeId);
                }
                // getting Url Key from parentsArray
                $productImage = $this->_parentsArray[$parentId]['image'];
            } else {
                $productImage = $this->_getImagePlaceholder();
                $this->_verboseLogger->warning("No Image found for product:" . $this->_productSku);
            }
        }

        if ($productImage) {
            if (strpos($productImage, "/") !== 0) {
                $productImage = "/" . $productImage;
            }
            if ($this->_vsfResizeX && $this->_vsfResizeY) {
                return $this->_baseUrl . "img/" . $this->_vsfResizeX . "/" . $this->_vsfResizeY . "/resize" . $productImage;
            }
            $productImage =
                $this->_baseUrl
                . Constants::DIR_MEDIA_CATALOG_PRODUCT
                . $productImage;
        }

        return $productImage;
    }

    /**
     * Retrieves the product availability
     *
     * @return null|string
     */
    public function getAvailability()
    {
        $productAvailability = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productAvailability;
        }

        try {

            $productIsInStock = $this->_stockRegistry->getStockItem($this->_product->getId())->getIsInStock();
            $this->_verboseLogger->debug("product " . $this->_product->getSku() . " productIsInStock  " . var_export($productIsInStock, true));

            /* For now the products are always 'in stock' because of the products query filter */
            // TODO: product availability query filter configurable
            if ($productIsInStock) {
                $productAvailability = Constants::AVAILABILITY_INSTOCK;
            } else {
                $productAvailability = Constants::AVAILABILITY_OUTOFSTOCK;
            }
        } catch (NoSuchEntityException $exception) {
            $this->_verboseLogger->error("No Stock found for product:" . $this->_productSku);
            $this->_verboseLogger->error($exception->getMessage());
        }
        $this->_verboseLogger->debug("product " . $this->_product->getSku() . " productAvailability  " . var_export($productAvailability, true));

        return $productAvailability;
    }

    /**
     * Retrieves the product price
     *
     * @return null
     */
    public function getPrice()
    {
        $productPrice = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return null;
        }

        if ($this->_product->getData('price')
            && $this->_product->getData('price') != '') {
            $productPrice = $this->_product->getData('price');
        } else {
            $this->_verboseLogger->error("No Price found for product:" . $this->_productSku);
        }

        return $productPrice;
    }

    /**
     * @param $attributeCode
     * @return null
     * @throws NoSuchEntityException
     */
    public function getCustomAttribute($attributeCode , $singleValue = false)
    {
        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return null;
        }

        if ($result = $this->_product->getData($attributeCode)) {
            try {
                if (!array_key_exists($attributeCode, $this->productAttributes)) {
                    $this->productAttributes[$attributeCode] = $this->attributeRepository->get($attributeCode);
                }
                if ($this->productAttributes[$attributeCode]->getFrontendInput() === "select" || $this->productAttributes[$attributeCode]->getFrontendInput() === "multiselect") {
                    //$result = $this->_helper->getAttributeText($attributeCode, $this->_product);
                    $result = $this->_product->getResource()
                        ->getAttribute($attributeCode)
                        ->setStoreId($this->_storeId)
                        ->getFrontend()
                        ->getValue($this->_product);

                    $isMultiple = false;
                    if (strpos($result, ',') !== false && !$singleValue) {
                        $isMultiple = true;
                        $result = explode(',', $result);
                    }

                    if ($isMultiple) {
                        $values = [];
                        foreach ($result as $item) {
                            $values[] = trim($item);
                        }
                        $result = $values;
                    }
                }
            } catch (Exception $exception) {
                $this->_verboseLogger->error("Error loading attribute with code :" . $attributeCode);
            }
        }

        return $result;
    }

    /**
     * @return |null
     */
    public function getSpecialPrice()
    {
        $productSpecialPrice = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return null;
        }


        if ($this->_product->getData('special_price') > 0 &&
            $this->_product->getData('special_from_date') <= date('Y-m-d H:i:s')
        ) {
            if (empty($this->_product->getData('special_to_date'))) {
                $productSpecialPrice = $this->_product->getData('special_price');
            } elseif (($this->_product->getData('special_to_date') >= date('Y-m-d H:i:s'))) {
                $productSpecialPrice = $this->_product->getData('special_price');
            }
        } else {
            $this->_verboseLogger->error("No Special Price found for product:" . $this->_productSku);
        }

        return $productSpecialPrice;
    }

    /**
     * Retrieves the product price currency
     *
     * @param int $storeId
     * @return mixed|null
     */
    public function getCurrency($storeId = 0)
    {
        $productCurrency = null;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productCurrency;
        }

        // set the store Id
        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // Product Description attribute
        $productCurrency = $this->_helper->getConfig(
            Constants::PATH_CURRENCY_BASE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$productCurrency) {
            $this->_verboseLogger->error("No Currency found for product:" . $this->_productSku);
        }

        return $productCurrency;
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getBrand($storeId = 0)
    {
        $productBrand = '';

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productBrand;
        }

        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // Product Brand attribute
        $productBrandAttribute = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_BRAND,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // if Magento Manufacturer
        if (Constants::BRAND_ATTRIBUTE_DEFAULT === $productBrandAttribute) {
            if ($this->_helper->getAttributeText('manufacturer', $this->_product)) {
                $productBrand = $this->_helper->getAttributeText('manufacturer', $this->_product);
            } else {
                $this->_verboseLogger->warning("No Manufacturer or Brand found for product:" . $this->_productSku);
            }
        } // if free text
        else if (Constants::BRAND_ATTRIBUTE_BRAND === $productBrandAttribute) {
            if ($this->_helper->getAttributeText('brand', $this->_product)) {
                $productBrand = $this->_helper->getAttributeText('brand', $this->_product);
            } else {
                $this->_verboseLogger->warning("No Manufacturer or Brand found for product:" . $this->_productSku);
            }
        } else {
            $productBrand = $this->_helper->getConfig(
                Constants::PATH_BRAND_NAME,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $productBrand;
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getGtin($storeId = 0)
    {
        $productGtin = '';

        if (!$this->_product) {
            $this->_logger->error("No product found!");
            return $productGtin;
        }

        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // Product GTIN attribute
        $productGtinAttribute = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_GTIN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($this->_product->getData($productGtinAttribute) && !is_array($this->_product->getData($productGtinAttribute))) {
            $productGtin = $this->_product->getData($productGtinAttribute);
        } else {
            $this->_verboseLogger->error("No GTIN found for product:" . $this->_productSku);
        }

        return $productGtin;
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getShippingCountry($storeId = 0)
    {
        return
            $this->_helper->getConfig(
                Constants::PATH_PRODUCT_SHIPPING_COUNTRY,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
    }

    /**
     * @param int $storeId
     * @return string
     */
    public function getMpn($storeId = 0)
    {
        $productMpn = '';

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productMpn;
        }

        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // Product MPN attribute
        $productMpnAttribute = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_MPN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($this->_product->getData($productMpnAttribute) && !is_array($this->_product->getData($productMpnAttribute))) {
            $productMpn = $this->_product->getData($productMpnAttribute);
        } else {
            $this->_verboseLogger->warning("No MPN found for product:" . $this->_productSku);
        }

        return $productMpn;
    }

    /**
     * @param int $storeId
     * @return int
     */
    public function getShippingCost($storeId = 0)
    {
        $productShippingCost = 0;

        if (!$this->_product) {
            $this->_verboseLogger->error("No product found!");
            return $productShippingCost;
        }

        $storeId = ($this->_storeId) ? $this->_storeId : $storeId;

        // Product Shipping Cost By attribute
        $productShippingCostByAttribute = $this->_helper->getConfig(
            Constants::PATH_SHIPPING_COST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // Shipping Cost
        if ($productShippingCostByAttribute == Constants::SHIPPING_PRICE_BY_PRICE) {
            $productShippingCost = $this->_shippingCost->getShippingCostByPrice(
                $this->_product->getFinalPrice(),
                $storeId
            );
        } else if ($productShippingCostByAttribute == Constants::SHIPPING_PRICE_BY_WEIGHT) {
            $productShippingCost = $this->_shippingCost->getShippingCostByWeight(
                (int)$this->_product->getData('weight'),
                $storeId
            );
        } else if ($productShippingCostByAttribute == Constants::SHIPPING_PRICE_BY_ATTRIBUTE) {
            // Product Shipping attribute
            $productShippingAttribute = $this->_helper->getConfig(
                Constants::PATH_SHIPPING_ATTRIBUTE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $productShippingCost = (int)$this->_product->getData($productShippingAttribute);
        }

        // Se non sono state calcolate le spese si spedizione, le setto a 0
        if (!$productShippingCost) {
            $productShippingCost = 0;
        }

        return $productShippingCost;
    }

    /**
     * Retrieves the image placeholder
     *
     * @param int $storeId
     * @return null|string
     */
    private function _getImagePlaceholder($storeId = 0)
    {
        $imagePlaceholder = null;

        // Product Image Placeholder
        $placeholder = $this->_helper->getConfig(
            Constants::PATH_PLACEHOLDER_IMAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($placeholder && $placeholder != '') {
            $imagePlaceholder =
                DIRECTORY_SEPARATOR
                . Constants::IMAGE_PLACEHOLDER
                . DIRECTORY_SEPARATOR
                . $placeholder;
        }

        return $imagePlaceholder;
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    private function _getSecureBaseUrl($storeId = 0)
    {
        if (!array_key_exists($storeId, $this->_secureBaseUrl)) {
            $this->_secureBaseUrl[$storeId] = $this->_helper->getConfig(
                Constants::PATH_SECURE_BASEURL,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $this->_secureBaseUrl[$storeId];
    }

    /**
     * Check if the product is child;
     * in that case return the parent Id
     *
     * @return int
     */
    protected function _checkChildGetParent()
    {
        $parentId = 0;

        if (Type::TYPE_SIMPLE == $this->_product->getTypeId()
            && Visibility::VISIBILITY_NOT_VISIBLE == $this->_product->getData('visibility')) {
            try {
                /* Get parent product */
                // Configurable?
                $parentByChild =
                    $this->_catalogProductTypeConfigurable->getParentIdsByChild(
                        $this->_product->getId()
                    );
                // Not Configurable
                if (!$parentByChild) {
                    // Grouped ?
                    $parentByChild =
                        $this->_catalogProductTypeGrouped->getParentIdsByChild(
                            $this->_product->getId()
                        );
                    // It's Grouped
                    if ($parentByChild) {
                        $parentId = $parentByChild[0];
                    }
                } // It's Configurable
                else {
                    $parentId = $parentByChild[0];
                }
            } catch (Exception $exception) {
                $this->_logger->error($exception->getMessage());
            }
        }

        return $parentId;
    }

    /**
     * Stores parent values to avoid to be loaded again
     *
     * @param $parentId
     * @param $storeId
     */
    private function _saveParentValues($parentId, $storeId)
    {
        try {
            $_product = $this->_productRepository->getById($parentId, false, $storeId);
            //$this->_parentsArray[$parentId]['status'] = $_product->getData('status');
            $this->_parentsArray[$parentId]['url_key'] =
                ($_product->getData('url_key')) ? $_product->getData('url_key') : null;
            $this->_parentsArray[$parentId]['image'] =
                ($_product->getData('image')) ? $_product->getData('image') : null;
        } catch (NoSuchEntityException $exception) {
            $this->_verboseLogger->warning("No Parent found for product:" . $this->_productSku);
            $this->_verboseLogger->error($exception->getMessage());
        }
    }
}
