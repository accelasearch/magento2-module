<?php

namespace AccelaSearch\Search\ViewModel;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class UserHelper implements ArgumentInterface
{
    private StoreManagerInterface $storeManager;
    private CustomerSession $customerSession;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerSession       $customerSession
    )
    {
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @return int|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerGroup()
    {
        if ($this->isLoggedIn()) {
            return $this->customerSession->getCustomerGroupId();
        }
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrencyCode(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }
}
