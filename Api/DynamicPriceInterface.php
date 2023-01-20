<?php

namespace AccelaSearch\Search\Api;

interface DynamicPriceInterface
{
    /**
     * @param string[] $ids
     * @param string|null $visitorType
     * @param string|null $currencyCode
     * @return mixed
     */
    public function getPrices(array $ids, string $visitorType = null, string $currencyCode = null);
}
