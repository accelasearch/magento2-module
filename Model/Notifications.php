<?php
namespace AccelaSearch\Search\Model;

use AccelaSearch\Search\Constants;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Verbose;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Notifications
 * @package AccelaSearch\Search\Model
 */
class Notifications
{
    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var StateInterface
     */
    protected $_inlineTranslation;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Verbose
     */
    protected $_logger;
    private State $state;

    /**
     * Notifications constructor.
     *
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param Data $helper
     * @param Verbose $logger
     * @param State $state
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        Data $helper,
        Verbose $logger,
        State $state
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->state = $state;
    }

    /**
     * Send email with result notifications
     *
     * @param $notifications
     * @param $store
     * @return array
     */
    public function sendNotifications($notifications, $store)
    {
        try {
            $this->state->setAreaCode(Area::AREA_CRONTAB);
        } catch (\Exception $exception){
            //LEFT EMPTY FOR CRONJOBS
        }
        $notifierStatus = $this->_helper->isSetConfig(Constants::PATH_NOTIFIER_STATUS);

        if ($notifierStatus) {
            // getting recipients email
            $notifierRecipients =
                $this->_helper->getConfig(Constants::PATH_NOTIFIER_RECIPIENTS);
            if ($notifierRecipients) {
                $recipients = array();
                $recipientsArray = json_decode($notifierRecipients, true);
                foreach ($recipientsArray as $recipientArray) {
                    $recipients[] = $recipientArray[Constants::COLUMN_NAME_RECIPIENT];
                }

                // vars
                $ret = $this->_helper->getLogFilePath();
                if (!$ret["success"]) {
                    return $ret;
                }
                $logFile = $ret["logFilePath"];
                $storeId = $store->getId();
                $vars = [
                    'notifications' => $notifications,
                    'logfile' => $logFile,
                    'store' => $store
                ];

                $this->_inlineTranslation->suspend();

                // setting Sender
                // email Sender
                $sender['email'] = $this->_helper->getConfig(
                    Constants::SENDER_EMAIL,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
                // name Sender
                $sender['name'] = $this->_helper->getConfig(
                    Constants::SENDER_NAME,
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );

                try {
                    // preparing email
                    $transport = $this->_transportBuilder
                    ->setTemplateIdentifier(
                        'accelasearch_feed_notifications'
                    )
                    ->setTemplateOptions(
                        [
                            'area' => Area::AREA_ADMINHTML,
                            'store' => $storeId
                        ]
                    )
                    ->setTemplateVars($vars)
                    ->setFromByScope($sender)
                    ->addTo($recipients)
                    ->getTransport();

                    // sending email
                    $transport->sendMessage();
                }
                catch (\Exception $exception) {
                    $this->_logger->error($exception->getMessage());
                }

                $this->_inlineTranslation->resume();
            }
        }

        return array("success" => true);
    }
}
