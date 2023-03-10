@extends('shop::customers.account.index')

@section('page_title')
    {{ __('shop::app.customer.account.order.index.page-title') }}
@endsection

@section('page-detail-wrapper')
    <div class="account-head mb-10">
        <span class="account-heading">
            {{ __('shop::app.customer.account.order.index.title') }}
        </span>
    </div>

    {!! view_render_event('mrpath.shop.customers.account.orders.list.before') !!}

        <div class="account-items-list">
            <div class="account-table-content">

                {!! app('Mrpath\Shop\DataGrids\OrderDataGrid')->render() !!}

            </div>
        </div>

    {!! view_render_event('mrpath.shop.customers.account.orders.list.after') !!}
@endsection