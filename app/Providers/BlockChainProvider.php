<?php

namespace App\Providers;

use App\Http\Controllers\TransactionController;
use Illuminate\Support\ServiceProvider;

class BlockChainProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(TransactionController $transactionController): void
    {
    }
}
