<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Payable
{
    /**
     * Get the entity responsible for paying (e.g., Business, User).
     */
    public function getPayer(): Model;
}
