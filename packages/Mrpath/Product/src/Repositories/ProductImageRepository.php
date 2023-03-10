<?php

namespace Mrpath\Product\Repositories;

use Illuminate\Container\Container as App;
use Mrpath\Product\Repositories\ProductRepository;

class ProductImageRepository extends ProductMediaRepository
{
    /**
     * Product repository object.
     *
     * @var Mrpath\Product\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * Create a new repository instance.
     *
     * @param  \Mrpath\Product\Repositories\ProductRepository $productRepository
     * @param  \Illuminate\Container\Container                $app
     * @return void
     */
    public function __construct(
        ProductRepository $productRepository,
        App $app
    ) {
        parent::__construct($app);

        $this->productRepository = $productRepository;
    }

    /**
     * Specify model class name.
     *
     * @return string
     */
    public function model(): string
    {
        return \Mrpath\Product\Contracts\ProductImage::class;
    }

    /**
     * Upload images.
     *
     * @param  array  $data
     * @param  \Mrpath\Product\Models\Product  $product
     * @return void
     */
    public function uploadImages($data, $product): void
    {
        $this->upload($data, $product, 'images');

        if (isset($data['variants'])) {
            $this->uploadVariantImages($data['variants']);
        }
    }

    /**
     * Upload variant images.
     *
     * @param  array $variants
     * @return void
     */
    public function uploadVariantImages($variants): void
    {
        foreach ($variants as $variantsId => $variantData) {
            $product = $this->productRepository->find($variantsId);

            if (! $product) {
                break;
            }

            $this->upload($variantData, $product, 'images');
        }
    }
}
