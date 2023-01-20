<?php
namespace AccelaSearch\Search\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Logger;

/**
 * Class FeedGenerationButton
 * @package AccelaSearch\Search\Block\System\Config
 */
class FeedGenerationButton extends Field
{
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var string
     */
    protected $_template = 'AccelaSearch_Search::system/config/generate_feed.phtml';

    /**
     *
     * @param Data $helper
     * @param Logger $logger
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        Context $context,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('accelasearch_feed/system_config/feed');
    }

    /**
     * Feed Generation Button
     *
     * @return mixed
     */
    public function getButtonHtml()
    {
        $params = array(
            'id' => 'generate_feed',
            'label' => __('Schedule Feed Generation'),
            'disabled' => false
        );
        try {
            // lockfile check
            // get lock file path
            $ret = $this->_helper->getLockFilePath();
            $lockfile = ($ret["success"]) ? $ret["lockFilePath"] : null;

            // if lock file exists
            if (file_exists($lockfile)) {
                $params['label'] = __('File lock exists');
                $params['disabled'] = true;
            }

            // button generation
            $button = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Widget\Button'
            )->setData($params);

            return $button->toHtml();
        }
        catch (\Exception $exception) {
            $this->_logger->error("Feed generation button error: " . $exception->getMessage());
        }
    }
}
