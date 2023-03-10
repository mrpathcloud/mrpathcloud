<?php

namespace Mrpath\Admin\Http\Controllers\Customer;

use Mrpath\Customer\Rules\VatIdRule;
use Mrpath\Admin\DataGrids\AddressDataGrid;
use Mrpath\Admin\Http\Controllers\Controller;
use Mrpath\Customer\Repositories\CustomerRepository;
use Mrpath\Customer\Repositories\CustomerAddressRepository;

class AddressController extends Controller
{
    /**
     * Contains route related configuration.
     *
     * @var array
     */
    protected $_config;

    /**
     * CustomerRepository object
     *
     * @var \Mrpath\Customer\Repositories\CustomerRepository
     */
    protected $customerRepository;

    /**
     * CustomerAddressRepository object
     *
     * @var \Mrpath\Customer\Repositories\CustomerAddressRepository
     */
    protected $customerAddressRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Mrpath\Customer\Repositories\CustomerRepository         $customerRepository
     * @param  \Mrpath\Customer\Repositories\CustomerAddressRepository  $customerAddressRepository
     * @return void
     */
    public function __construct(
        CustomerRepository $customerRepository,
        CustomerAddressRepository $customerAddressRepository
    ) {
        $this->customerRepository = $customerRepository;

        $this->customerAddressRepository = $customerAddressRepository;

        $this->_config = request('_config');
    }

    /**
     * Fetch address by customer id.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function index($id)
    {
        $customer = $this->customerRepository->find($id);

        if (request()->ajax()) {
            return app(AddressDataGrid::class)->toJson();
        }

        return view($this->_config['view'], compact('customer'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function create($id)
    {
        $customer = $this->customerRepository->find($id);

        return view($this->_config['view'], compact('customer'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        request()->merge([
            'address1' => implode(PHP_EOL, array_filter(request()->input('address1'))),
        ]);

        $data = collect(request()->input())->except('_token')->toArray();

        $this->validate(request(), [
            'company_name' => 'string',
            'address1'     => 'string|required',
            'country'      => 'string|required',
            'state'        => 'string|required',
            'city'         => 'string|required',
            'postcode'     => 'required',
            'phone'        => 'required',
            'vat_id'       => new VatIdRule(),
        ]);

        if ($this->customerAddressRepository->create($data)) {
            session()->flash('success', trans('admin::app.customers.addresses.success-create'));

            return redirect()->route('admin.customer.edit', ['id' => $data['customer_id']]);
        } else {
            session()->flash('success', trans('admin::app.customers.addresses.error-create'));

            return redirect()->back();
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $address = $this->customerAddressRepository->find($id);

        return view($this->_config['view'], compact('address'));
    }

    /**
     * Edit's the pre made resource of customer called address.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        request()->merge(['address1' => implode(PHP_EOL, array_filter(request()->input('address1')))]);

        $this->validate(request(), [
            'company_name' => 'string',
            'address1'     => 'string|required',
            'country'      => 'string|required',
            'state'        => 'string|required',
            'city'         => 'string|required',
            'postcode'     => 'required',
            'phone'        => 'required',
            'vat_id'       => new VatIdRule(),
        ]);

        $data = collect(request()->input())->except('_token')->toArray();

        $address = $this->customerAddressRepository->find($id);

        if ($address) {
            $this->customerAddressRepository->update($data, $id);

            session()->flash('success', trans('admin::app.customers.addresses.success-update'));

            return redirect()->route('admin.customer.addresses.index', ['id' => $address->customer_id]);
        }
        return redirect()->route($this->_config['redirect']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->customerAddressRepository->delete($id);

        return response()->json([
            'redirect' => false,
            'message' => trans('admin::app.customers.addresses.success-delete')
        ]);
    }

    /**
     * Mass delete the customer's addresses.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function massDestroy($id)
    {
        $addressIds = explode(',', request()->input('indexes'));

        foreach ($addressIds as $addressId) {
            $this->customerAddressRepository->delete($addressId);
        }

        session()->flash('success', trans('admin::app.customers.addresses.success-mass-delete'));

        return redirect()->route($this->_config['redirect'], ['id' => $id]);
    }
}
