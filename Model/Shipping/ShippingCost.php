<?php
namespace AccelaSearch\Search\Model\Shipping;

use AccelaSearch\Search\Constants;
use AccelaSearch\Search\Helper\Data;
use AccelaSearch\Search\Logger\Logger;

/**
 * Class ShippingCost
 * @package AccelaSearch\Search\Model\Shipping
 */
class ShippingCost
{
    /* Shipping ranges by Price */
    protected $_newShippingRangesByPrice;
    /* Shipping ranges by Weight */
    protected $_newShippingRangesByWeight;

    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * ShippingCost constructor.
     *
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        Data $helper,
        Logger $logger
    )
    {
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * @param int $storeId
     */
    private function _createShippingCostGridByPrice($storeId = 0)
    {
        if ($this->_newShippingRangesByPrice) {
            return;
        }

        // Price ranges
        $this->_newShippingRangesByPrice = array();
        // Adding the first range, mandatory
        $range = array();
        $range['from_price'] = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_SHIPPING_BY_PRICE_FIRST_PRICE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $range['cost'] = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_SHIPPING_BY_PRICE_FIRST_COST,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        // Adding the first range
        array_push($this->_newShippingRangesByPrice, $range);

        // Adding the further ranges, if they exist
        $shippingRanges = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_SHIPPING_BY_PRICE_RANGES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($shippingRanges) {
            $shippingRanges = json_decode($shippingRanges, true);
            if (is_array($shippingRanges)) {
                foreach ($shippingRanges as $key => $row)
                {
                    $range = array();
                    $range['from_price'] = $row['from_price'];
                    $range['cost'] = $row['cost'];
                    array_push($this->_newShippingRangesByPrice, $range);
                }
                // Ordering the price ranges
                foreach ($this->_newShippingRangesByPrice as $key => $row)
                {
                    $from_price[$key] = $row['from_price'];
                    $cost[$key] = $row['cost'];
                }
                array_multisort($from_price, SORT_ASC, $this->_newShippingRangesByPrice);
            }
        }

        return;
    }

    /**
     * @param $prodPrice
     * @param $storeId
     * @return float
     */
    public function getShippingCostByPrice($prodPrice, $storeId)
    {
        if (!$this->_newShippingRangesByPrice) {
            $this->_createShippingCostGridByPrice($storeId);
        }

        // Setting the product shipping cost
        $nRanges = count($this->_newShippingRangesByPrice);
        for ($k=0; $k<=$nRanges-1; $k++) {
            // Se prezzo prodotto > ultima soglia e ultima fascia disponibile
            if (
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $prodPrice
                    )
                )
                >=
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $this->_newShippingRangesByPrice[$k]['from_price']
                    )
                )
            )
            {
                if ($k == $nRanges-1) {
                    return floatval(
                        preg_replace(
                            "/[^-0-9\.]/",
                            ".",
                            $this->_newShippingRangesByPrice[$k]['cost']
                        )
                    );
                }
            }
            // Se prezzo prodotto <= ultima soglia e esiste una fascia precedente
            elseif (
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $prodPrice)
                )
                <
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $this->_newShippingRangesByPrice[$k]['from_price']
                    )
                )
            )
            {
                if ($k > 0) {
                    return floatval(
                        preg_replace(
                            "/[^-0-9\.]/",
                            ".",
                            $this->_newShippingRangesByPrice[$k-1]['cost']
                        )
                    );
                }
            }
        }

        // No price range found
        return floatval(0);
    }

    /**
     * @param int $storeId
     */
    private function _createShippingCostGridByWeight($storeId = 0)
    {
        if ($this->_newShippingRangesByWeight) {
            return;
        }

        // Price ranges
        $this->_newShippingRangesByWeight = array();
        // Adding the first range, mandatory
        $range = array();
        $range['from_weight'] = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_SHIPPING_BY_WEIGHT_FIRST_PRICE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $range['cost'] = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_SHIPPING_BY_WEIGHT_FIRST_COST,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        // Adding the first range
        array_push($this->_newShippingRangesByWeight, $range);

        // Adding the further ranges, if they exist
        $shippingRanges = $this->_helper->getConfig(
            Constants::PATH_PRODUCT_SHIPPING_BY_WEIGHT_RANGES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($shippingRanges) {
            $shippingRanges = json_decode($shippingRanges, true);
            if (is_array($shippingRanges)) {
                foreach ($shippingRanges as $key => $row)
                {
                    $range = array();
                    $range['from_weight'] = $row['from_weight'];
                    $range['cost'] = $row['cost'];
                    array_push($this->_newShippingRangesByWeight, $range);
                }
                // Ordering the weight ranges
                foreach ($this->_newShippingRangesByWeight as $key => $row)
                {
                    $from_weight[$key] = $row['from_weight'];
                    $cost[$key] = $row['cost'];
                }
                array_multisort($from_weight, SORT_ASC, $this->_newShippingRangesByWeight);
            }
        }

        return;
    }

    /**
     * @param $prodWeight
     * @param $storeId
     * @return float
     */
    public function getShippingCostByWeight($prodWeight, $storeId)
    {
        if (!$this->_newShippingRangesByWeight) {
            $this->_createShippingCostGridByWeight($storeId);
        }

        // Setting the product shipping cost
        $nRanges = count($this->_newShippingRangesByWeight);
        for ($k=0; $k<=$nRanges-1; $k++) {
            // Se prezzo prodotto > ultima soglia e ultima fascia disponibile
            if (
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $prodWeight
                    )
                )
                >=
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $this->_newShippingRangesByWeight[$k]['from_weight']
                    )
                )
            )
            {
                if ($k == $nRanges-1) {
                    return floatval(preg_replace("/[^-0-9\.]/",".", $this->_newShippingRangesByWeight[$k]['cost']));
                }
            }
            // Se prezzo prodotto <= ultima soglia e esiste una fascia precedente
            elseif (
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $prodWeight
                    )
                )
                <
                floatval(
                    preg_replace(
                        "/[^-0-9\.]/",
                        ".",
                        $this->_newShippingRangesByWeight[$k]['from_weight']
                    )
                )
            )
            {
                if ($k > 0) {
                    return floatval(
                        preg_replace(
                            "/[^-0-9\.]/",
                            ".",
                            $this->_newShippingRangesByWeight[$k-1]['cost']
                        )
                    );
                }
            }
        }

        // No weight range found
        return floatval(0);
    }
}
