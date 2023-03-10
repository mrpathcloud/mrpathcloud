<?php
namespace Mrpath\Payment\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Mrpath\Sales\Repositories\OrderRepository;
use Mrpath\Sales\Repositories\InvoiceRepository;

/**
 * Generate Invoice Event handler
 *
 */
class GenerateInvoice
{
    /**
     * OrderRepository object
     *
     * @var \Mrpath\Sales\Repositories\OrderRepository
     */
    protected $orderRepository;

    /**
     * InvoiceRepository object
     *
     * @var \Mrpath\Sales\Repositories\InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * Create the event listener.
     *
     * @param  Mrpath\Sales\Repositories\OrderRepository $orderRepository
     * @param \Mrpath\Sales\Repositories\InvoiceRepository invoiceRepository
     * @return void
     */
    public function __construct(
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository
        )
        {
            $this->orderRepository = $orderRepository;
            $this->invoiceRepository = $invoiceRepository;
        }

    /**
     * Generate a new invoice.
     *
     * @param  object  $order
     * @return void
     */
    public function handle($order)
    {
        if ($order->payment->method == 'cashondelivery' && core()->getConfigData('sales.paymentmethods.cashondelivery.generate_invoice')) {
            $this->invoiceRepository->create($this->prepareInvoiceData($order), core()->getConfigData('sales.paymentmethods.cashondelivery.invoice_status'), core()->getConfigData('sales.paymentmethods.cashondelivery.order_status'));
        }

        if ($order->payment->method == 'moneytransfer' && core()->getConfigData('sales.paymentmethods.moneytransfer.generate_invoice')) {
            $this->invoiceRepository->create($this->prepareInvoiceData($order), core()->getConfigData('sales.paymentmethods.moneytransfer.invoice_status'), core()->getConfigData('sales.paymentmethods.moneytransfer.order_status'));
        }
    }

    /**
     * Prepares order's invoice data for creation.
     *
     * @return array
     */
    protected function prepareInvoiceData($order)
    {
        $invoiceData = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }
}