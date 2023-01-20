<?php
declare(strict_types=1);

namespace AccelaSearch\Search\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigHelper implements ArgumentInterface
{
    const ACCELASEARCH_URL = 'accelasearch_search/accelasearch/accelasearch_url';
    const ACCELASEARCH_CSS_URL = 'accelasearch_search/accelasearch/accelasearch_css_url';
    const IS_PUBLISHED_VISITOR_TYPE = 'accelasearch_search/dynamicprice/publish_visitor_type';
    const IS_PUBLISHED_CURRENCY_CODE = 'accelasearch_search/dynamicprice/publish_currency_code';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getAccelasearchUrl(): string
    {
        return (string)$this->scopeConfig->getValue(self::ACCELASEARCH_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getCssUrl()
    {
        return $this->scopeConfig->getValue(self::ACCELASEARCH_CSS_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isPublishedVisitorType(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::IS_PUBLISHED_VISITOR_TYPE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isPublishedCurrencyCode(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::IS_PUBLISHED_CURRENCY_CODE, ScopeInterface::SCOPE_STORE);
    }

}
