<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VentaCreada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Venta $venta
    ) {}
}
