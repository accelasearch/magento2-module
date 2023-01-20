<?php
namespace AccelaSearch\Search\Controller\Adminhtml\System\Config;

use AccelaSearch\Search\Logger\Verbose;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Backend\App\Action\Context;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Cron\Model\Schedule;

/**
 * Class Feed
 * @package AccelaSearch\Search\Controller\Adminhtml\System\Config
 */
class Feed extends Action
{
    /**
     * @var JsonFactory
     */
    protected $_jsonFactory;
    /**
     * @var ScheduleFactory
     */
    protected $_scheduleFactory;
    /**
     * @var DateTime
     */
    protected $_dateTime;
    /**
     * @var Verbose
     */
    protected $_verbose;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ScheduleFactory $scheduleFactory
     * @param DateTime $dateTime
     * @param Verbose $verbose
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ScheduleFactory $scheduleFactory,
        DateTime $dateTime,
        Verbose $verbose
    )
    {
        $this->_jsonFactory = $jsonFactory;
        $this->_scheduleFactory = $scheduleFactory;
        $this->_dateTime = $dateTime;
        $this->_verbose = $verbose;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->_setSchedule('accelasearch_search_feedgeneration');
        if ($result["success"]) {
            $final_result['messages'] = __('Feed Generation scheduled!');
        }
        else {
            $final_result['messages'] = __('Feed Generation not scheduled ' . $result["message"]);
        }

        $this->_verbose->debug("Final Result: " . $final_result['messages']);
        $response = $this->_jsonFactory->create();
        $response->setData($final_result);

        return $response;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('AccelaSearch_Search::config_googleshopping');
    }

    /**
     * Schedule the specific cronjob
     *
     * @param $jobCode
     * @return array
     */
    private function _setSchedule($jobCode)
    {
        if (!$jobCode) {
            return array("success" => false, "message" => __("jobCode not set!"));
        }

        $createdAt = strftime('%Y-%m-%d %H:%M:%S', $this->_dateTime->gmtTimestamp());
        $scheduledAt = strftime('%Y-%m-%d %H:%M:%S', $this->_dateTime->gmtTimestamp() + 60);

        try {
            $this->_scheduleFactory->create()
                ->setJobCode($jobCode)
                ->setStatus(Schedule::STATUS_PENDING)
                ->setCreatedAt($createdAt)
                ->setScheduledAt($scheduledAt)
                ->save();
        } catch (\Exception $e) {
            return array("success" => false, "message" => __("Impossible to save the cronjob $jobCode"));
        }

        return array("success" => true);
    }
}
