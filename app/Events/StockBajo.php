<?php

namespace App\Events;

use App\Models\Producto;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockBajo
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Producto $producto
    ) {}
}
