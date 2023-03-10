<?php

namespace Mrpath\Product\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Mrpath\Product\Console\Commands\PriceUpdate;
use Mrpath\Product\Facades\ProductImage as ProductImageFacade;
use Mrpath\Product\Facades\ProductVideo as ProductVideoFacade;
use Mrpath\Product\Models\ProductProxy;
use Mrpath\Product\Observers\ProductObserver;
use Mrpath\Product\ProductImage;
use Mrpath\Product\ProductVideo;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        include __DIR__ . '/../Http/helpers.php';

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->app->register(EventServiceProvider::class);

        $this->publishes([
            dirname(__DIR__) . '/Config/imagecache.php' => config_path('imagecache.php'),
        ]);

        ProductProxy::observe(ProductObserver::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->registerCommands();

        $this->registerFacades();
    }

    /**
     * Register configuration.
     *
     * @return void
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/product_types.php', 'product_types');
    }

    /**
     * Register the console commands of this package.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PriceUpdate::class]);
        }
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerFacades(): void
    {
        /**
         * Product image.
         */
        $loader = AliasLoader::getInstance();

        $loader->alias('productimage', ProductImageFacade::class);

        $this->app->singleton('productimage', function () {
            return app()->make(ProductImage::class);
        });

        /**
         * Product video.
         */
        $loader->alias('productvideo', ProductVideoFacade::class);

        $this->app->singleton('productvideo', function () {
            return app()->make(ProductVideo::class);
        });
    }
}
