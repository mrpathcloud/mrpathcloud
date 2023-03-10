<?php

namespace Mrpath\Shop\Http\Middleware;

use Closure;
use Mrpath\Core\Repositories\CurrencyRepository;

class Currency
{
    /**
     * Currency repository instance.
     *
     * @var \Mrpath\Core\Repositories\CurrencyRepository
     */
    protected $currencyRepository;

    /**
     * Create a middleware instance.
     *
     * @param  \Mrpath\Core\Repositories\CurrencyRepository  $currencyRepository
     * @return void
     */
    public function __construct(CurrencyRepository $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($currencyCode = request()->get('currency')) {
            if ($this->currencyRepository->findOneByField('code', $currencyCode)) {
                core()->setCurrency($currencyCode);

                session()->put('currency', $currencyCode);
            }
        } else {
            if ($currencyCode = session()->get('currency')) {
                core()->setCurrency($currencyCode);
            } else {
                core()->setCurrency(core()->getChannelBaseCurrencyCode());
            }
        }

        unset($request['currency']);

        return $next($request);
    }
}
