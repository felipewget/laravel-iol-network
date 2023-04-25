<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BlockChainController;
use App\Models\Transaction;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('api')->group(function () {
    Route::get('/genesis', [BlockChainController::class, 'initializeBlockhain']);

    Route::prefix('accounts')->group(function () {
        Route::prefix('{accountAddress}')->group(function () {
            Route::get('/balance', [BlockChainController::class, 'getWalletBalance']);
            Route::get('/products', [BlockChainController::class, 'listProductsByWallet']);
            Route::get('/orders', [BlockChainController::class, 'listOrdersHistoryByWallet']);
            Route::get('/domains', [BlockChainController::class, 'listDomainsByWallet']);
            Route::get('/transactions', [BlockChainController::class, 'transactionHistory']);
        });

        Route::get('/create/keys', [AccountController::class, 'createAccount']);
        // Route::post('/create', );
        // Route::post('/alias', );
        // Route::get('/alias', );
        // Route::get('/balance', );
        // Route::get('/public-address', ); // alias or private
    });

    Route::prefix('datas')->group(function () {
        // Route::get('/', );
        // Route::get('/key', );
        // Route::get('{id}/owner', );
        // Route::get('/owner/list', );
        // Route::get('/key/list', ); // key for example, photo-user-123
        // Route::get('/history', );
    });

    Route::prefix('domains')->group(function () {
        // Route::get('/', );
        // Route::get('/contracts', );
    });

    // -- BLOCKS/EVENTS --

    Route::prefix('transactions')->group(function () {
        Route::get('/uid/{transactionId}', [BlockChainController::class, 'getTransactionById']);
        // Route::get('/', );
    });

    // -- NETWORK --

    Route::prefix('network')->group(function () {
        Route::get('/{domain}', [BlockChainController::class, 'getDomain']);
        Route::get('/{domain}/history', [BlockChainController::class, 'getDomainHistory']);
    });

    // -- PRODUCTS --

    Route::prefix('products')->group(function () {
        Route::get('/{transactionId}', [BlockChainController::class, 'getProductById']);
        Route::get('/{transactionId}/orders', [BlockChainController::class, 'getProductOrdersHistory']);
    });


    // -- TRANSACTIONS --

    Route::prefix('transactions')->group(function () {
        Route::prefix('iol')->group(function () {
            Route::post('/', [TransactionController::class, 'transferIol']);
            // Route::get('/status', [TransactionController::class, 'getStatus']);
        });

        Route::prefix('domain')->group(function () {
            Route::post('/', [TransactionController::class, 'createDomain']);
            Route::put('/', [TransactionController::class, 'updateDomain']);
            Route::put('/transfer', [TransactionController::class, 'transferDomain']);
        });

        Route::prefix('data')->group(function () {
            Route::post('/', [TransactionController::class, 'createData']);
            Route::put('/', [TransactionController::class, 'updateData']);
            Route::put('/transfer', [TransactionController::class, 'transferData']);
        });

        Route::prefix('product')->group(function () {
            Route::post('/', [TransactionController::class, 'createProduct']);
            Route::delete('/', [TransactionController::class, 'removeProduct']);
        });

        Route::prefix('alias')->group(function () {
            Route::post('/', [TransactionController::class, 'createAlias']);
        });

        Route::prefix('order')->group(function () {
            Route::post('/', [TransactionController::class, 'executeOrder']);
        });

        Route::prefix('code')->group(function () {
            Route::post('/', [TransactionController::class, 'createCode']);
        });
    });

    Route::prefix('blockchain')->group(function () {
        // Route::get('/accounts', );
        // Route::get('/iols', );
        // Route::get('/domains', );
        // Route::get('/datas', );
        // Route::get('/network', );
        // Route::get('/transactions', );
        Route::get('/blocks', [BlockChainController::class, 'listBlocks']);
        Route::get('/transactions/opened', [TransactionController::class, 'listOpenedTransactions']);
    });
});

Route::fallback(function () {
    include base_path() . '/resources/views/index.html';
});
