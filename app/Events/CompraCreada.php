<?php

namespace App\Events;

use App\Models\Compra;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompraCreada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Compra $compra
    ) {}
}
