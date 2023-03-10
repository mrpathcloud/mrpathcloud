<?php

namespace Mrpath\BookingProduct\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mrpath\Core\Eloquent\TranslatableModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mrpath\BookingProduct\Database\Factories\BookingProductEventTicketFactory;
use Mrpath\BookingProduct\Contracts\BookingProductEventTicket as BookingProductEventTicketContract;

class BookingProductEventTicket extends TranslatableModel implements BookingProductEventTicketContract
{
    use HasFactory;

    public $timestamps = false;

    public $translatedAttributes = [
        'name',
        'description',
    ];

    protected $fillable = [
        'price',
        'qty',
        'special_price',
        'special_price_from',
        'special_price_to',
        'booking_product_id',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return BookingProductEventTicketFactory::new();
    }
}