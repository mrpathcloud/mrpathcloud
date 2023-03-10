<?php

namespace Mrpath\Shop\Http\Controllers;

use Mrpath\Shop\Http\Controllers\Controller;
use Mrpath\Core\Repositories\SliderRepository;
use Mrpath\Product\Repositories\SearchRepository;

class HomeController extends Controller
{
    /**
     * Slider repository instance.
     *
     * @var \Mrpath\Core\Repositories\SliderRepository
     */
    protected $sliderRepository;

    /**
     * Search repository instance.
     *
     * @var \Mrpath\Core\Repositories\SearchRepository
     */
    protected $searchRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Mrpath\Core\Repositories\SliderRepository  $sliderRepository
     * @param  \Mrpath\Product\Repositories\SearchRepository  $searchRepository
     * @return void
     */
    public function __construct(
        SliderRepository $sliderRepository,
        SearchRepository $searchRepository
    ) {
        $this->sliderRepository = $sliderRepository;

        $this->searchRepository = $searchRepository;

        parent::__construct();
    }

    /**
     * Loads the home page for the storefront.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $sliderData = $this->sliderRepository->getActiveSliders();

        return view($this->_config['view'], compact('sliderData'));
    }

    /**
     * Loads the home page for the storefront if something wrong.
     *
     * @return \Exception
     */
    public function notFound()
    {
        abort(404);
    }

    /**
     * Upload image for product search with machine learning.
     *
     * @return \Illuminate\Http\Response
     */
    public function upload()
    {
        return $this->searchRepository->uploadSearchImage(request()->all());
    }
}
