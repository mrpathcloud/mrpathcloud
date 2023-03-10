<?php

namespace Mrpath\Product\Type;

use Mrpath\Attribute\Repositories\AttributeRepository;
use Mrpath\Checkout\Models\CartItem;
use Mrpath\Product\Datatypes\CartItemValidationResult;
use Mrpath\Product\Repositories\ProductAttributeValueRepository;
use Mrpath\Product\Repositories\ProductDownloadableLinkRepository;
use Mrpath\Product\Repositories\ProductDownloadableSampleRepository;
use Mrpath\Product\Repositories\ProductImageRepository;
use Mrpath\Product\Repositories\ProductInventoryRepository;
use Mrpath\Product\Repositories\ProductRepository;
use Mrpath\Product\Repositories\ProductVideoRepository;

class Downloadable extends AbstractType
{
    /**
     * Product downloadable link repository instance.
     *
     * @var \Mrpath\Product\Repositories\ProductDownloadableLinkRepository
     */
    protected $productDownloadableLinkRepository;

    /**
     * Product downloadable sample repository instance.
     *
     * @var \Mrpath\Product\Repositories\ProductDownloadableSampleRepository
     */
    protected $productDownloadableSampleRepository;

    /**
     * Skip attribute for downloadable product type.
     *
     * @var array
     */
    protected $skipAttributes = ['length', 'width', 'height', 'weight', 'guest_checkout'];

    /**
     * These blade files will be included in product edit page.
     *
     * @var array
     */
    protected $additionalViews = [
        'admin::catalog.products.accordians.images',
        'admin::catalog.products.accordians.videos',
        'admin::catalog.products.accordians.categories',
        'admin::catalog.products.accordians.downloadable',
        'admin::catalog.products.accordians.channels',
        'admin::catalog.products.accordians.product-links',
    ];

    /**
     * Is a stokable product type.
     *
     * @var bool
     */
    protected $isStockable = false;

    /**
     * Show quantity box.
     *
     * @var bool
     */
    protected $allowMultipleQty = false;

    /**
     * Get product options.
     *
     * @var array
     */
    protected $getProductOptions = [];

    /**
     * Create a new product type instance.
     *
     * @param \Mrpath\Attribute\Repositories\AttributeRepository               $attributeRepository
     * @param \Mrpath\Product\Repositories\ProductRepository                   $productRepository
     * @param \Mrpath\Product\Repositories\ProductAttributeValueRepository     $attributeValueRepository
     * @param \Mrpath\Product\Repositories\ProductInventoryRepository          $productInventoryRepository
     * @param \Mrpath\Product\Repositories\ProductImageRepository              $productImageRepository
     * @param \Mrpath\Product\Repositories\ProductDownloadableLinkRepository   $productDownloadableLinkRepository
     * @param \Mrpath\Product\Repositories\ProductDownloadableSampleRepository $productDownloadableSampleRepository
     * @param \Mrpath\Product\Repositories\ProductVideoRepository              $productVideoRepository
     * @return void
     */
    public function __construct(
        AttributeRepository $attributeRepository,
        ProductRepository $productRepository,
        ProductAttributeValueRepository $attributeValueRepository,
        ProductInventoryRepository $productInventoryRepository,
        productImageRepository $productImageRepository,
        ProductDownloadableLinkRepository $productDownloadableLinkRepository,
        ProductDownloadableSampleRepository $productDownloadableSampleRepository,
        ProductVideoRepository $productVideoRepository
    ) {
        parent::__construct(
            $attributeRepository,
            $productRepository,
            $attributeValueRepository,
            $productInventoryRepository,
            $productImageRepository,
            $productVideoRepository
        );

        $this->productDownloadableLinkRepository = $productDownloadableLinkRepository;

        $this->productDownloadableSampleRepository = $productDownloadableSampleRepository;
    }

    /**
     * Update.
     *
     * @param  array  $data
     * @param  int  $id
     * @param  string  $attribute
     * @return \Mrpath\Product\Contracts\Product
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $product = parent::update($data, $id, $attribute);
        $route = request()->route() ? request()->route()->getName() : '';

        if ($route != 'admin.catalog.products.massupdate') {
            $this->productDownloadableLinkRepository->saveLinks($data, $product);

            $this->productDownloadableSampleRepository->saveSamples($data, $product);
        }

        return $product;
    }

    /**
     * Return true if this product type is saleable.
     *
     * @return bool
     */
    public function isSaleable()
    {
        if (! $this->product->status) {
            return false;
        }

        if (is_callable(config('products.isSaleable')) &&
            call_user_func(config('products.isSaleable'), $this->product) === false) {
            return false;
        }

        if ($this->product->downloadable_links()->count()) {
            return true;
        }

        return false;
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    public function getTypeValidationRules()
    {
        return [
            'downloadable_links.*.type'       => 'required',
            'downloadable_links.*.file'       => 'required_if:type,==,file',
            'downloadable_links.*.file_name'  => 'required_if:type,==,file',
            'downloadable_links.*.url'        => 'required_if:type,==,url',
            'downloadable_links.*.downloads'  => 'required',
            'downloadable_links.*.sort_order' => 'required',
        ];
    }

    /**
     * Add product. Returns error message if can't prepare product.
     *
     * @param  array  $data
     * @return array
     */
    public function prepareForCart($data)
    {
        if (! isset($data['links']) || ! count($data['links'])) {
            return trans('shop::app.checkout.cart.integrity.missing_links');
        }

        $products = parent::prepareForCart($data);

        foreach ($this->product->downloadable_links as $link) {
            if (! in_array($link->id, $data['links'])) {
                continue;
            }

            $products[0]['price'] += core()->convertPrice($link->price);
            $products[0]['base_price'] += $link->price;
            $products[0]['total'] += (core()->convertPrice($link->price) * $products[0]['quantity']);
            $products[0]['base_total'] += ($link->price * $products[0]['quantity']);
        }

        return $products;
    }

    /**
     * Compare options.
     *
     * @param  array  $options1
     * @param  array  $options2
     * @return bool
     */
    public function compareOptions($options1, $options2)
    {
        if ($this->product->id != $options2['product_id']) {
            return false;
        }

        if (isset($options1['links']) && isset($options2['links'])) {
            return $options1['links'] === $options2['links'];
        }

        if (! isset($options1['links'])) {
            return false;
        }

        if (! isset($options2['links'])) {
            return false;
        }
    }

    /**
     * Returns additional information for items.
     *
     * @param  array  $data
     * @return array
     */
    public function getAdditionalOptions($data)
    {
        $labels = [];

        foreach ($this->product->downloadable_links as $link) {
            if (in_array($link->id, $data['links'])) {
                $labels[] = $link->title;
            }
        }

        $data['attributes'][0] = [
            'attribute_name' => 'Downloads',
            'option_id'      => 0,
            'option_label'   => implode(', ', $labels),
        ];

        return $data;
    }

    /**
     * Validate cart item product price
     *
     * @param  \Mrpath\Checkout\Models\CartItem  $item
     * @return \Mrpath\Product\Datatypes\CartItemValidationResult
     */
    public function validateCartItem(CartItem $item): CartItemValidationResult
    {
        $result = new CartItemValidationResult();

        if (parent::isCartItemInactive($item)) {
            $result->itemIsInactive();

            return $result;
        }

        $price = $item->product->getTypeInstance()->getFinalPrice($item->quantity);

        foreach ($item->product->downloadable_links as $link) {
            if (! in_array($link->id, $item->additional['links'])) {
                continue;
            }

            $price += $link->price;
        }

        $price = round($price, 2);

        if ($price == $item->base_price) {
            return $result;
        }

        $item->base_price = $price;
        $item->price = core()->convertPrice($price);

        $item->base_total = $price * $item->quantity;
        $item->total = core()->convertPrice($price * $item->quantity);

        $item->save();

        return $result;
    }

    /**
     * Get product maximum price
     *
     * @return float
     */
    public function getMaximamPrice()
    {
        return $this->product->price;
    }
}
