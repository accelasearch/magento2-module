<?php
namespace AccelaSearch\Search\Model\Config\Backend;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use AccelaSearch\Search\Logger\Logger;
use \Magento\Framework\Exception\LocalizedException;

/**
 * Class CategoriesExcluded
 * @package AccelaSearch\Search\Model\Config\Backend
 */
class CategoriesExcluded implements ArrayInterface
{
    private $_rootCategoryId;

    /**
     * @var CategoryFactory
     */
    private $_categoryFactory;
    /**
     * @var CollectionFactory
     */
    private $_categoryCollectionFactory;
    /**
     * @var Http
     */
    private $_request;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var Logger
     */
    private $_logger;

    /**
     * CategoriesExcluded constructor.
     *
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param Http $request
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CollectionFactory $categoryCollectionFactory,
        Http $request,
        StoreManagerInterface $storeManager,
        Logger $logger
    )
    {
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_request = $request;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->_toArray();
        $ret = [];
        foreach ($arr as $key => $value)
        {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }
        return $ret;
    }

    /**
     * @return array
     */
    private function _toArray()
    {
        $categories = $this->_getCategoryCollection(false, false, false, false);
        $catagoryList = array();
        $catagoryList[''] = __('-- NO CATEGORY --');
        foreach ($categories as $category)
        {
            $catagoryList[$category->getEntityId()] =
                __($this->_getParentName($category->getPath()) . $category->getName());
        }
        return $catagoryList;
    }

    /**
     * Get the category collection
     *
     * @param bool $isActive
     * @param bool $level
     * @param bool $sortBy
     * @param bool $pageSize
     * @return mixed
     */
    protected function _getCategoryCollection(
        $isActive = true,
        $level = false,
        $sortBy = false,
        $pageSize = false
    )
    {
        $collection = array();

        try {
            $storeId = (int) $this->_request->getParam('store', 0);
            $store = $this->_storeManager->getStore($storeId);
            $storeGroupId = $store->getStoreGroupId();
            $this->_rootCategoryId = $this->_storeManager->getGroup($storeGroupId)->getRootCategoryId();
            //$this->_logger->debug("Root Category:" . $this->_rootCategoryId);
            $collection = $this->_categoryCollectionFactory->create();
            $collection->addFieldToSelect('entity_id');
            $collection->addFieldToSelect('path');
            $collection->addFieldToSelect('name');
            $collection->addFieldToFilter('path', array('like'=> "1/$this->_rootCategoryId/%"));

            // select only active categories
            if ($isActive) {
                $collection->addIsActiveFilter();
            }
            // select categories of certain level
            if ($level) {
                $collection->addLevelFilter($level);
            }
            // sort categories by some value
            if ($sortBy) {
                $collection->addOrderField($sortBy);
            }
            // select certain number of categories
            if ($pageSize) {
                $collection->setPageSize($pageSize);
            }

        }
        catch (LocalizedException $localizedException) {
            $this->_logger->warning(
                __("Category Excluded Collection error: ") . $localizedException->getMessage()
            );
            return $collection;
        }

        return $collection;
    }

    /**
     * @param string $path
     * @return string
     */
    private function _getParentName($path = '')
    {
        $parentName = '';
        // Default Category Root
        $rootCats = array(1);
        // Adding specific store category root
        $rootCats[] = $this->_rootCategoryId;
        $catTree = explode("/", $path);
        // Deleting category itself
        array_pop($catTree);
        if ($catTree && (count($catTree) > count($rootCats)))
        {
            foreach ($catTree as $catId)
            {
                if (!in_array($catId, $rootCats))
                {
                    $category = $this->_categoryFactory->create()->load($catId);
                    $categoryName = $category->getName();
                    $parentName .= $categoryName . ' -> ';
                }
            }
        }
        return $parentName;
    }
}
