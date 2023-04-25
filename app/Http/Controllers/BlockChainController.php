<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TransactionResource;
use App\Models\Block;
use App\Repositories\BlockRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;

class BlockChainController extends Controller
{

    private BlockRepository $blockRepository;
    private TransactionRepository $transactionRepository;

    public function __construct(BlockRepository $blockRepository, TransactionRepository $transactionRepository)
    {
        $this->blockRepository = $blockRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function initializeBlockhain()
    {
        if (!Block::first()) {
            $transaction = $this->transactionRepository->createAward("felipeWallet", 15000000, "initial amount to manage IOL network");

            $this->blockRepository->createBlock(18032023, "welcome to iol network", [$transaction]);
        }
    }

    public function transactionHistory(string $accountAddress)
    {
        return $this->blockRepository->transactionHistory($accountAddress);
    }

    public function getWalletBalance(string $accountAddress)
    {
        $balance = $this->blockRepository->getBalance($accountAddress);
        $alias = $this->blockRepository->getAliasByAccountAddress($accountAddress);

        return [
            'alias' => $alias,
            'balance' => $balance['balance'],
            'openedTransactions' => $balance['tmpBalance']
        ];
    }

    public function listProductsByWallet($accountAddress)
    {
        $products = $this->blockRepository->listProductsByWallet($accountAddress);

        return ProductResource::collection($this->blockRepository->listProductsByWallet($accountAddress));
    }

    public function getProductById($transactionId)
    {
        return new TransactionResource($this->blockRepository->getProductById($transactionId));
    }

    public function getProductOrdersHistory($transactionId)
    {
        return $this->blockRepository->listOrdersHistoryByProduct($transactionId);
    }

    public function listOrdersHistoryByWallet($walletAddress)
    {
        return $this->blockRepository->ordersHistoryByWallet($walletAddress);
    }

    public function getDomain($domain)
    {
        return $this->blockRepository->getDomain($domain);
    }

    public function getDomainHistory($domain)
    {
        return TransactionResource::collection($this->blockRepository->domainHistory($domain));
    }

    public function listDomainsByWallet($walletAddress)
    {
        return $this->blockRepository->domainHistoryByWallet($walletAddress);
    }

    public function getTransactionById(string $transactionId)
    {
        $transaction = $this->blockRepository->getTransactionById($transactionId);

        if (!$transaction) abort(404);

        return new TransactionResource($transaction);
    }

    public function listBlocks(Request $request)
    {
        $page = $request->get('page') > 0 ? $request->get('page') : 1;

        $limit = 10;

        return BlockResource::collection(Block::orderByDesc('index')->skip(($page * $limit) - $limit)->limit($limit)->get());
    }
}
