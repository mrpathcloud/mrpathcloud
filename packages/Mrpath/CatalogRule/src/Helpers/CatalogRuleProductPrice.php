<?php

namespace Mrpath\CatalogRule\Helpers;

use Carbon\Carbon;
use Mrpath\Customer\Repositories\CustomerGroupRepository;
use Mrpath\CatalogRule\Repositories\CatalogRuleProductPriceRepository;

class CatalogRuleProductPrice
{
    /**
     * CatalogRuleProductPriceRepository object
     *
     * @var \Mrpath\CatalogRule\Repositories\CatalogRuleProductPriceRepository
     */
    protected $catalogRuleProductPriceRepository;

    /**
     * CatalogRuleProduct object
     *
     * @var \Mrpath\CatalogRule\Helpers\CatalogRuleProduct
     */
    protected $catalogRuleProductHelper;

    /**
     * CustomerGroupRepository object
     *
     * @var \Mrpath\Customer\Repositories\CustomerGroupRepository
     */
    protected $customerGroupRepository;

    /**
     * Create a new helper instance.
     *
     * @param  \Mrpath\Attribute\Repositories\CatalogRuleProductPriceRepository  $catalogRuleProductPriceRepository
     * @param  \Mrpath\CatalogRule\Repositories\CatalogRuleProduct  $catalogRuleProductHelper
     * @param  \Mrpath\Customer\Repositories\CustomerGroupRepository  $customerGroupRepository
     * @return void
     */
    public function __construct(
        CatalogRuleProductPriceRepository $catalogRuleProductPriceRepository,
        CatalogRuleProduct $catalogRuleProductHelper,
        CustomerGroupRepository $customerGroupRepository
    )
    {
        $this->catalogRuleProductPriceRepository = $catalogRuleProductPriceRepository;

        $this->catalogRuleProductHelper = $catalogRuleProductHelper;

        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * Collect discount on cart
     *
     * @param  int  $batchCount
     * @param  \Mrpath\Product\Contracts\Product  $product
     * @return void
     */
    public function indexRuleProductPrice($batchCount, $product = null)
    {
        $dates = [
            'current'  => $currentDate = Carbon::now(),
            'previous' => (clone $currentDate)->subDays('1')->setTime(23, 59, 59),
            'next'     => (clone $currentDate)->addDays('1')->setTime(0, 0, 0),
        ];

        $prices = $endRuleFlags = [];

        $previousKey = null;

        $catalogRuleProducts = $this->catalogRuleProductHelper->getCatalogRuleProducts($product);

        foreach ($catalogRuleProducts as $row) {
            $productKey = $row->product_id . '-' . $row->channel_id . '-' . $row->customer_group_id;

            if ($previousKey && $previousKey != $productKey) {
                $endRuleFlags = [];

                if (count($prices) > $batchCount) {
                    $this->catalogRuleProductPriceRepository->getModel()->insert($prices);

                    $prices = [];
                }
            }

            foreach ($dates as $key => $date) {
                if ((! $row->starts_from || $date >= $row->starts_from)
                    && (! $row->ends_till || $date <= $row->ends_till)
                ) {
                    $priceKey = $date->getTimestamp() . '-' . $productKey;

                    if (isset($endRuleFlags[$priceKey])) {
                        continue;
                    }

                    if (! isset($prices[$priceKey])) {
                        $prices[$priceKey] = [
                            'rule_date'         => $date,
                            'catalog_rule_id'   => $row->catalog_rule_id,
                            'channel_id'        => $row->channel_id,
                            'customer_group_id' => $row->customer_group_id,
                            'product_id'        => $row->product_id,
                            'price'             => $this->calculate($row),
                            'starts_from'       => $row->starts_from,
                            'ends_till'         => $row->ends_till,
                        ];
                    } else {
                        $prices[$priceKey]['price'] = $this->calculate($row, $prices[$priceKey]);

                        $prices[$priceKey]['starts_from'] = max($prices[$priceKey]['starts_from'], $row->starts_from);

                        $prices[$priceKey]['ends_till'] = min($prices[$priceKey]['ends_till'], $row->ends_till);
                    }

                    if ($row->end_other_rules) {
                        $endRuleFlags[$priceKey] = true;
                    }
                }
            }

            $previousKey = $productKey;
        }

        $this->catalogRuleProductPriceRepository->getModel()->insert($prices);
    }

    /**
     * Calculates product price based on rule
     *
     * @param  array  $rule
     * @param  \Mrpath\Product\Contracts\Product|null  $productData
     * @return float
     */
    public function calculate($rule, $productData = null)
    {
        $price = $productData && isset($productData['price']) ? $productData['price'] : $rule->price;

        switch ($rule->action_type) {
            case 'to_fixed':
                $price = min($rule->discount_amount, $price);

                break;

            case 'to_percent':
                $price = $price * $rule->discount_amount / 100;

                break;

            case 'by_fixed':
                $price = max(0, $price - $rule->discount_amount);

                break;

            case 'by_percent':
                $price = $price * (1 - $rule->discount_amount / 100);

                break;
        }

        return $price;
    }

    /**
     * Clean product price index
     *
     * @param  array  $productIds
     * @return void
     */
    public function cleanProductPriceIndex($productIds = [])
    {
        if (count($productIds)) {
            $this->catalogRuleProductPriceRepository->getModel()->whereIn('product_id', $productIds)->delete();
        } else {
            $this->catalogRuleProductPriceRepository->deleteWhere([
                ['product_id', 'like', '%%']
            ]);
        }
    }

    /**
     * Get catalog rules product price for specific date, channel and customer group.
     *
     * @param  \Mrpath\Product\Contracts\Product  $product
     * @return array|void
     */
    public function getRulePrice($product)
    {
        if (auth()->guard()->check()) {
            $customerGroupId = auth()->guard()->user()->customer_group_id;
        } else {
            $customerGroup = $this->customerGroupRepository->getCustomerGuestGroup();

            if (! $customerGroup) {
                return;
            }

            $customerGroupId = $customerGroup->id;
        }

        return $this->catalogRuleProductPriceRepository->checkInLoadedCatalogRulePrice($product, $customerGroupId);
    }
}