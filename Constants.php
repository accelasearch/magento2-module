<?php
/**
 * AccelaSearch
 *
 * @category   AccelaSearch
 * @package    AccelaSearch_Search
 * @author     Michele Fantetti
 * @copyright  Copyright (c) 2020 AccelaSearch
 */

namespace AccelaSearch\Search;


class Constants
{
    /* Extension Configuration Paths */
    const PATH_EXTENSION_STATUS = 'accelasearch_search/feed/status';
    const PATH_FEED_DIRECTORY = 'accelasearch_search/feed/feed_directory';
    const PATH_FEED_CUSTOM_BASE_URL = 'accelasearch_search/feed/custom_base_url';
    const PATH_FEED_VSF_RESIZE_X = 'accelasearch_search/feed/vsf_resize_x';
    const PATH_FEED_VSF_RESIZE_Y = 'accelasearch_search/feed/vsf_resize_y';
    const PATH_NOTIFIER_STATUS = 'accelasearch_search/notifications/notifier_status';
    const PATH_NOTIFIER_RECIPIENTS = 'accelasearch_search/notifications/notifier_recipients';
    const PATH_STORES_ENABLED = 'accelasearch_search/filters/stores_enabled';
    const PATH_PRODUCT_DESCRIPTION = 'accelasearch_search/fields/product_description';
    const PATH_PRODUCT_BRAND = 'accelasearch_search/fields/product_brand';
    const PATH_BRAND_NAME = 'accelasearch_search/fields/brand_name';
    const PATH_PRODUCT_GTIN = 'accelasearch_search/fields/product_gtin';
    const PATH_PRODUCT_MPN = 'accelasearch_search/fields/product_mpn';
    const PATH_SHIPPING_COST = 'accelasearch_search/fields/shipping_cost';
    const PATH_SHIPPING_ATTRIBUTE = 'accelasearch_search/fields/shipping_attribute';
    const PATH_CATEGORIES_EXCLUDED = 'accelasearch_search/feed/categories_excluded';
    const PATH_STOCK_BEHAVIOR = 'accelasearch_search/filters/stock_behavior';
    const PATH_PRODUCT_SHIPPING_BY_PRICE_FIRST_PRICE = 'accelasearch_search/fields/shipping_by_price_first_price';
    const PATH_PRODUCT_SHIPPING_BY_PRICE_FIRST_COST = 'accelasearch_search/fields/shipping_by_price_first_cost';
    const PATH_PRODUCT_SHIPPING_BY_PRICE_RANGES = 'accelasearch_search/fields/shipping_by_price_ranges';
    const PATH_PRODUCT_SHIPPING_BY_WEIGHT_FIRST_PRICE = 'accelasearch_search/fields/shipping_by_weight_first_price';
    const PATH_PRODUCT_SHIPPING_BY_WEIGHT_FIRST_COST = 'accelasearch_search/fields/shipping_by_weight_first_cost';
    const PATH_PRODUCT_SHIPPING_BY_WEIGHT_RANGES = 'accelasearch_search/fields/shipping_by_weight_ranges';
    const PATH_PRODUCT_SHIPPING_COUNTRY = 'accelasearch_search/fields/shipping_country';
    const PATH_SYNC_FEED_GENERATION_FREQUENCY = 'groups/sync/fields/feed_generation_frequency/value';
    const PATH_CRONTAB_CRON_EXPR = 'accelasearch_search/sync/feed_generation_frequency';
    /* System Configuration Paths */
    const PATH_SECURE_BASEURL = 'web/secure/base_url';
    const PATH_SEO_PRODUCTURLSUFFIX = 'catalog/seo/product_url_suffix';
    const PATH_PLACEHOLDER_IMAGE = 'catalog/placeholder/image_placeholder';
    const PATH_CURRENCY_BASE = 'currency/options/base'; // the base currency [website]
    const SENDER_EMAIL = 'trans_email/ident_general/email';
    const SENDER_NAME = 'trans_email/ident_general/name';
    /* Magento Tables */
    const TABLE_MAGENTO_EAV_ATTRIBUTE = 'eav_attribute';
    const TABLE_MAGENTO_EAV_ENTITY_TYPE = 'eav_entity_type';
    const TABLE_MAGENTO_EAV_OPTION_VALUE = 'eav_attribute_option_value';
    const TABLE_MAGENTO_CATALOG_PRODUCT_ENTITY = 'catalog_product_entity';
    const TABLE_MAGENTO_CATALOG_PRODUCT_WEBSITE = 'catalog_product_website';
    const TABLE_MAGENTO_STORE = 'store';
    const TABLE_MAGENTO_CATALOG_PRODUCT_INDEX_PRICE = 'catalog_product_index_price';
    const TABLE_MAGENTO_CATALOG_PRODUCT_ENTITY_DECIMAL = 'catalog_product_entity_decimal';
    const TABLE_MAGENTO_CATALOGINVENTORY_STOCK_ITEM = 'cataloginventory_stock_item';
    const TABLE_MAGENTO_CATALOG_CATEGORY_PRODUCT = 'catalog_category_product';
    const TABLE_MAGENTO_CATALOG_CATEGORY_ENTITY = 'catalog_category_entity';
    const TABLE_MAGENTO_CATALOG_CATEGORY_ENTITY_VARCHAR = 'catalog_category_entity_varchar';
    const TABLE_MAGENTO_CATALOG_PRODUCT_RELATION = 'catalog_product_relation';
    /* Extension Configuration Values */
    // Brand attribute values
    const BRAND_ATTRIBUTE_DEFAULT = 'default';
    const BRAND_ATTRIBUTE_BRAND = 'brand';
    const BRAND_ATTRIBUTE_FREETEXT = 'free_text';
    // Shipping Price By values
    const SHIPPING_PRICE_BY_PRICE = 'by_price';
    const SHIPPING_PRICE_BY_WEIGHT = 'by_weight';
    const SHIPPING_PRICE_BY_ATTRIBUTE = 'by_attribute';
    /* Labels */
    const AVAILABILITY_INSTOCK = "in stock";
    const AVAILABILITY_OUTOFSTOCK = "out of stock";
    const IMAGE_PLACEHOLDER = "placeholder";
    const DIR_MEDIA_CATALOG_PRODUCT = "media/catalog/product";
    const NO_SELECTION = "no_selection";
    /* Dirs & Files*/
    const FEED_FILE_NAME = 'accelasearch';
    const FILE_LOCKS_DIR = 'locks';
    const FILE_LOG_DIR = 'log';
    const FILE_LOCK = 'accelasearch_feed.lock';
    const FILE_LOG = 'accelasearch_feed.log';
    const FILE_LOG_VERBOSE = 'accelasearch_feed.log';
    const FILE_LOCK_EXPIRE = 4 * 60 * 60;
    /* Blocks Columns */
    const COLUMN_NAME_FROM_WEIGHT = 'from_weight';
    const COLUMN_NAME_FROM_PRICE = 'from_price';
    const COLUMN_NAME_COST = 'cost';
    const COLUMN_NAME_RECIPIENT = 'recipient';
}
