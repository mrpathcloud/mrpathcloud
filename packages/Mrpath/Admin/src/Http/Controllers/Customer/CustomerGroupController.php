<?php

namespace Mrpath\Admin\Http\Controllers\Customer;

use Mrpath\Admin\DataGrids\CustomerGroupDataGrid;
use Mrpath\Admin\Http\Controllers\Controller;
use Mrpath\Customer\Repositories\CustomerGroupRepository;

class CustomerGroupController extends Controller
{
    /**
     * Contains route related configuration.
     *
     * @var array
     */
    protected $_config;

    /**
     * Customer group repository instance.
     *
     * @var \Mrpath\Customer\Repositories\CustomerGroupRepository
     */
    protected $customerGroupRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Mrpath\Customer\Repositories\CustomerGroupRepository  $customerGroupRepository;
     * @return void
     */
    public function __construct(CustomerGroupRepository $customerGroupRepository)
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(CustomerGroupDataGrid::class)->toJson();
        }

        return view($this->_config['view']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view($this->_config['view']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:customer_groups,code', new \Mrpath\Core\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $data = request()->all();

        $data['is_user_defined'] = 1;

        $this->customerGroupRepository->create($data);

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Customer Group']));

        return redirect()->route($this->_config['redirect']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $group = $this->customerGroupRepository->findOrFail($id);

        return view($this->_config['view'], compact('group'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:customer_groups,code,' . $id, new \Mrpath\Core\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $this->customerGroupRepository->update(request()->all(), $id);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Customer Group']));

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
        $customerGroup = $this->customerGroupRepository->findOrFail($id);

        if ($customerGroup->is_user_defined == 0) {
            return response()->json([
                'message' => trans('admin::app.customers.customers.group-default'),
            ], 400);
        }

        if (count($customerGroup->customers) > 0) {
            return response()->json([
                'message' => trans('admin::app.response.customer-associate', ['name' => 'Customer Group']),
            ], 400);
        }

        try {
            $this->customerGroupRepository->delete($id);

            return response()->json(['message' => trans('admin::app.response.delete-success', ['name' => 'Customer Group'])]);
        } catch (\Exception $e) {}

        return response()->json(['message' => trans('admin::app.response.delete-failed', ['name' => 'Customer Group'])], 500);
    }
}
