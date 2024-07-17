<?php

namespace AccelaSearch\Search\Model;

use AccelaSearch\Search\Constants;
use Magento\Framework\App\Config\ScopeConfigInterface;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Logger;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\ScopeInterface;
use AccelaSearch\Search\Model\Config\Source\ConfigurableProducts as ProductTypeToExport;

/**
 * Class FeedProducts
 * @package AccelaSearch\Search\Model
 */
class FeedProducts
{
    private $_status;
    private $_visibility;
    private $childrenType;
    private $fatherType;

    protected $_product_entity_type;
    protected $_category_entity_type;
    protected $_status_attribute;
    protected $_status_backend_type;
    protected $_visibility_attribute;
    protected $_visibility_backend_type;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * FeedProducts constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Logger $logger
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * Environment initialization
     *
     * @return array
     */
    private function _setup()
    {
        // DB Magento connection
        if (!$this->_helper->dbmage_read) {
            $result = $this->_helper->getConnectionMagento();
            if (!$result["success"]) {
                return $result;
            }
        }

        // product entity type id
        if (!$this->_product_entity_type) {
            $tableName =
                $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_EAV_ENTITY_TYPE);
            $sqlProductEntityTypeSel =
                "SELECT entity_type_id FROM $tableName WHERE entity_type_code = 'catalog_product' LIMIT 0, 1";
            try {
                $stmProductEntityTypeSel = $this->_helper->dbmage_read->fetchAll($sqlProductEntityTypeSel);
                if ($stmProductEntityTypeSel) {
                    $this->_product_entity_type = $stmProductEntityTypeSel[0]['entity_type_id'];
                }
            } catch (\Exception $exception) {
                return array("success" => false, "message" => "Product entity type error: " . $exception->getMessage());
            }
        }

        // category entity type id
        if (!$this->_category_entity_type) {
            $tableName =
                $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_EAV_ENTITY_TYPE);
            $sqlCategoryEntityTypeIdSel =
                "SELECT entity_type_id FROM $tableName WHERE entity_type_code = 'catalog_category' LIMIT 0, 1";
            try {
                $stmCategoryEntityTypeIdSel = $this->_helper->dbmage_read->fetchAll($sqlCategoryEntityTypeIdSel);
                if ($stmCategoryEntityTypeIdSel) {
                    $this->_category_entity_type = $stmCategoryEntityTypeIdSel[0]['entity_type_id'];
                }
            } catch (\Exception $exception) {
                return array("success" => false, "message" => "Category entity type error: " . $exception->getMessage());
            }
        }

        // product 'status' attribute
        $tableName = $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_EAV_ATTRIBUTE);
        $sqlProductStatusSel = "SELECT attribute_id, backend_type FROM $tableName WHERE attribute_code = 'status' AND entity_type_id = " . $this->_product_entity_type . " LIMIT 0, 1";
        try {
            $stmProductStatusSel = $this->_helper->dbmage_read->fetchAll($sqlProductStatusSel);
            if ($stmProductStatusSel) {
                $this->_status_attribute = $stmProductStatusSel[0]["attribute_id"];
                $this->_status_backend_type = $stmProductStatusSel[0]["backend_type"];
            }
        } catch (\Exception $exception) {
            return array("success" => false, "message" => "Product status attribute error: " . $exception->getMessage());
        }

        // product 'visibility' attribute
        $tableName = $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_EAV_ATTRIBUTE);
        $sqlProductVisibilitySel = "SELECT attribute_id, backend_type FROM $tableName WHERE attribute_code = 'visibility' AND entity_type_id = " . $this->_product_entity_type . " LIMIT 0, 1";
        try {
            $stmProductVisibilitySel = $this->_helper->dbmage_read->fetchAll($sqlProductVisibilitySel);
            if ($stmProductVisibilitySel) {
                $this->_visibility_attribute = $stmProductVisibilitySel[0]["attribute_id"];
                $this->_visibility_backend_type = $stmProductVisibilitySel[0]["backend_type"];
            }
        } catch (\Exception $exception) {
            return array("success" => false, "message" => "Product visibility attribute error: " . $exception->getMessage());
        }

        // default product 'status'
        $this->_status = Status::STATUS_ENABLED;
        // default product 'visibility'
        $this->_visibility = implode(',', array(
            Visibility::VISIBILITY_BOTH,
            Visibility::VISIBILITY_IN_SEARCH,
            Visibility::VISIBILITY_IN_CATALOG,
            Visibility::VISIBILITY_NOT_VISIBLE
        ));

        $this->childrenType = '"simple","virtual","downloadable"';
        $this->fatherType = '"bundle","grouped","configurable"';

        return array("success" => true);
    }

    /**
     * Get feed products to put into the feed file
     *
     * @param $store
     * @return array
     */
    public function getFeedProducts($store)
    {
        // Setup
        $result = $this->_setup();
        if (!$result["success"]) {
            return $result;
        }

        $productsBehavior = $this->_helper->getConfig(
            'accelasearch_search/feed/products_behavior',
            ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );

        // the categories to exclude
        $categoriesBehavior =
            $this->_helper->getConfig(
                'accelasearch_search/feed/categories_behavior',
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );

        // the categories to exclude
        $categoriesSelected =
            $this->_helper->getConfig(
                'accelasearch_search/feed/categories_selected',
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );
        $categoriesSelected = trim($categoriesSelected);
        $categoriesSelected = trim($categoriesSelected, ',');

        $categoriesSelectedArray = explode(",", $categoriesSelected);

        $stockBehavior = $this->_helper->getConfig(
            Constants::PATH_STOCK_BEHAVIOR,
            ScopeInterface::SCOPE_STORE,
            $store->getCode()
        );

        $storeView = $store->getId();

        /*
         * product query with 'status' and 'visibility' filters
         */
        $tableCatalogProductEntity =
            $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_CATALOG_PRODUCT_ENTITY);
        $tableCatalogProductWebsite =
            $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_CATALOG_PRODUCT_WEBSITE);
        $tableStore =
            $this->_helper->getMagentoTableWithPrefix(Constants::TABLE_MAGENTO_STORE);
        $tableCatalogProductEntityStatusBackendtype =
            $this->_helper->getMagentoTableWithPrefix(
                Constants::TABLE_MAGENTO_CATALOG_PRODUCT_ENTITY . "_" . $this->_status_backend_type);
        $tableCatalogProductEntityVisibilityBackendtype =
            $this->_helper->getMagentoTableWithPrefix(
                Constants::TABLE_MAGENTO_CATALOG_PRODUCT_ENTITY . "_" . $this->_visibility_backend_type);
        $tableCatalogProductEntityDecimal =
            $this->_helper->getMagentoTableWithPrefix(
                Constants::TABLE_MAGENTO_CATALOG_PRODUCT_ENTITY_DECIMAL);
        $tableCataloginventoryStockItem =
            $this->_helper->getMagentoTableWithPrefix(
                Constants::TABLE_MAGENTO_CATALOGINVENTORY_STOCK_ITEM);
        $tableCatalogCategoryProduct =
            $this->_helper->getMagentoTableWithPrefix(
                Constants::TABLE_MAGENTO_CATALOG_CATEGORY_PRODUCT);
        $tableCatalogProductRelation = $this->_helper->getMagentoTableWithPrefix(
            Constants::TABLE_MAGENTO_CATALOG_PRODUCT_RELATION);

        $sqlFeedProductsSel = "SELECT DISTINCT cpe.entity_id, cpe.type_id, cpe.sku, csi.qty, csi.is_in_stock, cpr.parent_id, cpevisibility.value AS visibility,
            MAX(CASE WHEN cpestatus.store_id = 0 THEN cpestatus.value ELSE NULL END) AS cpestatus_value_0,
            MAX(CASE WHEN cpestatus.store_id = $storeView THEN cpestatus.value ELSE NULL END) AS cpestatus_value_$storeView
            FROM $tableCatalogProductEntity cpe "
            . // to get 'website_id'
            "JOIN $tableCatalogProductWebsite cpw
			ON cpw.product_id = cpe.entity_id "
            . // checking the store and if it is active on 'core_store' table
            "JOIN $tableStore cs
            ON cs.website_id = cpw.website_id
            AND cs.store_id = $storeView
            AND cs.is_active = 1 "

            . // checking 'status' on 'catalog_product_entity' table
            "LEFT JOIN $tableCatalogProductEntityStatusBackendtype cpestatus
            ON cpestatus.entity_id = cpe.entity_id
            AND cpestatus.attribute_id = $this->_status_attribute
            AND cpestatus.store_id IN (0, $storeView) "

            . // checking 'visibility' on 'catalog_product_entity' table
            "LEFT JOIN $tableCatalogProductEntityVisibilityBackendtype cpevisibility
            ON cpevisibility.entity_id = cpe.entity_id
            AND cpevisibility.attribute_id = $this->_visibility_attribute
            AND cpevisibility.store_id IN (0, $storeView) "

//            . // checking the product price on 'catalog_product_index_price' table
//            "LEFT JOIN $tableCatalogProductEntityDecimal AS cped
//            ON cpe.entity_id = cped.entity_id AND cped.attribute_id = {$this->_helper->getAttributeIdByCode('price')} "

            . // checking the product stock on 'cataloginventory_stock_item' table
            "LEFT JOIN $tableCataloginventoryStockItem csi
            ON csi.product_id = cpe.entity_id "

            .
            "LEFT JOIN $tableCatalogProductRelation cpr
            ON cpe.entity_id = cpr.child_id";

        /*
        . // checking the categories product on 'catalog_category_product' table
        "JOIN $tableCatalogCategoryProduct as ccp
        ON ccp.product_id = cpe.entity_id ";
        */

        // checking 'status' and 'visibility'
        $sqlFeedProductsSel .= "
            WHERE cpevisibility.value IN ($this->_visibility)
        ";

        switch ($productsBehavior) {
            case ProductTypeToExport::SIMPLE_ONLY:
                $sqlFeedProductsSel .= 'AND cpe.type_id IN (' . $this->childrenType . ') ';
                break;
//            case ProductTypeToExport::CONFIGURABLE_AND_SIMPLE:
//                $sqlFeedProductsSel .= 'AND cpe.type_id IN (' . $this->fatherType . ') ';
//                break;
//            case ProductTypeToExport::CONFIGURABLE_AND_CHILDREN_AND_SIMPLE:
            default:
                $sqlFeedProductsSel .= '';
                break;
        }

        // include or exclude categories
        if (0 == (int)$categoriesBehavior && (strlen($categoriesSelected) > 0) && !in_array("0", $categoriesSelectedArray)) {
            $sqlFeedProductsSel .= "AND cpe.entity_id IN (SELECT product_id FROM $tableCatalogCategoryProduct WHERE category_id NOT IN ($categoriesSelected)) ";
        } elseif (1 == (int)$categoriesBehavior) {
            $sqlFeedProductsSel .= "AND cpe.entity_id IN (SELECT product_id FROM $tableCatalogCategoryProduct WHERE category_id  IN ($categoriesSelected) ) ";
        }

        /***  DEV START ***/
        //$sqlFeedProductsSel .= "AND cpe.sku = '24-WG080' ";
        /***  DEV END ***/

        if (!empty($stockBehavior)) $sqlFeedProductsSel .= "AND csi.is_in_stock IN ($stockBehavior) ";

        // only products with price >= 0.01
//        $sqlFeedProductsSel .= "AND cped.value >= 0.01 ";
        // Order by
        $sqlFeedProductsSel .= "GROUP BY cpe.entity_id, cpe.type_id, cpe.sku, csi.qty, csi.is_in_stock, cpr.parent_id ";
        $sqlFeedProductsSel .= "ORDER BY cpe.entity_id";

        $this->_logger->debug("Query: $sqlFeedProductsSel");

        try {
            $collection = $this->_helper->dbmage_read->fetchAll($sqlFeedProductsSel);

            foreach ($collection as $k => $v) {
                $status = $v['cpestatus_value_' . $storeView] === null ? $v['cpestatus_value_0'] : $v['cpestatus_value_' . $storeView];

                if ($status != Status::STATUS_ENABLED) {
                    unset($collection[$k]);
                }
            }
        } catch (\Exception $exception) {
            return array(
                "success" => false,
                "message" => "Feed products collection error: " . $exception->getMessage()
            );
        }

        $this->_logger->debug(__("Number of products: ") . count($collection));

        return array("success" => true, "feedProducts" => $collection);
    }
}
