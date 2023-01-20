<?php
namespace AccelaSearch\Search\Cron;

use AccelaSearch\Search\Constants;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreRepository;
use AccelaSearch\Search\Model\FeedProducts;
use AccelaSearch\Search\Model\FeedFile;
use AccelaSearch\Search\Model\Notifications;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Logger;

/**
 * Class FeedGeneration
 * @package AccelaSearch\Search\Cron
 */
class FeedGeneration
{
    private $_notificationsList = array();

    /**
     * @var StoreRepository
     */
    protected $_storeRepository;

    /**
     * @var FeedProducts
     */
    protected $_feedProducts;

    /**
     * @var FeedFile
     */
    protected $_feedFile;

    /**
     * @var Notifications
     */
    protected $_notifications;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * FeedGeneration constructor.
     *
     * @param StoreRepository $storeRepository
     * @param FeedProducts $feedProducts
     * @param FeedFile $feedFile
     * @param Notifications $notifications
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        StoreRepository $storeRepository,
        FeedProducts $feedProducts,
        FeedFile $feedFile,
        Notifications $notifications,
        Data $helper,
        Logger $logger
    )
    {
        $this->_storeRepository = $storeRepository;
        $this->_feedProducts = $feedProducts;
        $this->_feedFile = $feedFile;
        $this->_notifications = $notifications;
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * Generates feed files
     *
     * @return bool
     */
    public function generateFeed()
    {
        // check extension script execution
        $result = $this->_helper->fileLockCheck();
        if (!$result["success"]) {
            $this->_logger->error($result["message"]);
            $this->_notificationsList[] = $result["message"];
            return false;
        }

        // feed directory
        $feedDirectory = $this->_helper->getConfig(Constants::PATH_FEED_DIRECTORY);
        if (!$feedDirectory) {
            // deleting lock file
            $this->_helper->fileLockDelete();
            $msg = "Feed Directory not configured";
            $this->_logger->warning($msg);
            $this->_notificationsList[] = $msg;
            return false;
        }

        // getting all store view
        $stores = $this->_storeRepository->getList();

        foreach ($stores as $store) {
            $storeDebug = $store->getCode() . ' - ' . $store->getName();

            $moduleStatus =
                $this->_helper->isSetConfig(
                    Constants::PATH_EXTENSION_STATUS,
                    ScopeInterface::SCOPE_STORE,
                    $store->getCode()
                );
            if (!$moduleStatus) {
                $msg = "Extension disabled for Store View: $storeDebug";
                $this->_logger->warning($msg);
                $this->_notificationsList[] = $msg;
                continue;
            }

            $msg = "Feed Generation :: Start for Store View: $storeDebug";
            $this->_logger->debug($msg);
            $this->_notificationsList[] = $msg;

            /* get feed products */
            $ret = $this->_feedProducts->getFeedProducts($store);
            if (!$ret["success"]) {
                // deleting lock file
                $this->_helper->fileLockDelete();
                $this->_logger->error($ret["message"]);
                $this->_notificationsList[] = $ret["message"];
                return false;
            }
            // feed products
            $_feedProducts = $ret["feedProducts"];

            /* write feed file */
            $ret = $this->_feedFile->writeFeedFile($feedDirectory, $store, $_feedProducts);
            if (!$ret["success"]) {
                // deleting lock file
                $this->_helper->fileLockDelete();
                $this->_logger->error($ret["message"]);
                $this->_notificationsList[] = $ret["message"];
                return false;
            }

            $msg = "Feed Generation :: End for Store View: $storeDebug";
            $this->_logger->debug($msg);
            $this->_notificationsList[] = $msg;

            // notifications
            if (!empty($this->_notificationsList))
                $ret = $this->_notifications->sendNotifications($this->_notificationsList, $store);
                if (!$ret["success"]) {
                    // deleting lock file
                    $this->_helper->fileLockDelete();
                    $this->_logger->error($ret["message"]);
                    return false;
                }
        }

        // deleting lock file
        $this->_helper->fileLockDelete();

        return true;
    }
}