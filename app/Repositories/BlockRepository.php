<?php

namespace App\Repositories;

use App\Models\Block;
use App\Models\TmpTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BlockRepository
{
    private $validationRepository;
    private $tmpTransactionRepository;

    public function __construct(ValidationRepository $validationRepository, TmpTransactionRepository $tmpTransactionRepository)
    {
        $this->validationRepository = $validationRepository;
        $this->tmpTransactionRepository = $tmpTransactionRepository;
    }

    public function createBlock(int $proofOfWork, string $previousHash, $transactions)
    {
        $lastBlock = $this->getLastBlock();

        $block = Block::create([
            'proof_of_work' => $proofOfWork,
            'previous_hash' => $previousHash,
            'index' => $lastBlock?->index
                ? ++$lastBlock->index
                : 1,
        ]);

        $formatedTransactions = array_map(function ($transaction) use ($block) {
            return [
                'index_id' => $block->index,
                'uid' => $transaction['uid'],
                'type' => $transaction['type'],
                'transaction' => $transaction,
                'created_at' => $transaction['created_at'],
            ];
        }, $transactions);

        $block->transactions()->createMany($formatedTransactions);
    }

    public function proccessLocalNewBlock()
    {
        $block = $this->getLastBlock();

        $dificulty = $this->validationRepository->getDifficultyLevel();
        $proofOfWork = $this->validationRepository->proofOfWork($block->proof_of_work, $dificulty);
        $blockHash = $this->validationRepository->getBlockHash($block);

        $tmpTransactions = $this->tmpTransactionRepository->listTmpTransactions()->toArray();
        $localTransactions = array_map(fn ($openedTransaction) => $openedTransaction['transaction'], $tmpTransactions);

        $block = $this->createBlock($proofOfWork, $blockHash, $localTransactions);

        // @todo Propagate -> $block

        $this->tmpTransactionRepository->removeTransitions();
    }

    public function getProductById(string $productId)
    {
        $product = Transaction::where(['type' => "product", 'uid' => $productId])->firstOrFail();
        $removeProduct = Transaction::where(['type' => "remove_product", 'transaction->product_id' => $productId])
            ->where('id', '>', $product->id)
            ->first();

        if ($removeProduct) abort(404);

        return $product;
    }

    public function listProductsByWallet(string $walletAddress)
    {
        return Transaction::where(['type' => "product", 'transaction->sender' => $walletAddress])
            ->whereNotExists(function ($query) {
                return $query->select(DB::raw(1))
                    ->from('transactions as t')
                    ->whereRaw("t.transaction::jsonb->'product_id' = transactions.transaction::jsonb->'uid'");
            })
            ->get();
    }

    public function listOrdersHistoryByProduct(string $productId)
    {
        Transaction::where(['type' => "product", 'uid' => $productId])->firstOrFail();

        return Transaction::where(['type' => "execute_order", 'transaction->product_id' => $productId])->get();
    }

    public function ordersHistoryByWallet(string $walletAddress)
    {
        return Transaction::where(['type' => "execute_order", 'transaction->receiver' => $walletAddress])->get();
    }

    public function getDomain($domain)
    {
        $arrDomain = explode(".iol", $domain);

        $domain = $arrDomain[0];

        unset($arrDomain[0]);
        $extension = "iol" . implode('.iol', $arrDomain);

        if ($this->getDomainBalance($domain . "." . $extension) == 0) abort(404, 'DOMAIN NOT FOUND');

        return Transaction::whereIn('type', ["create_domain", "update_domain"])
            ->where([
                'transaction->domain' => $domain,
                'transaction->extension' => $extension,
            ])
            ->orderByDesc('index_id')
            ->first();
    }

    public function getLastDomainOwner($domain)
    {
        $arrDomain = explode(".iol", $domain);

        $domain = $arrDomain[0];

        unset($arrDomain[0]);
        $extension = "iol" . implode('.iol', $arrDomain);

        $transaction = Transaction::whereIn('type', ["create_domain", "update_domain", "transfer_domain"])
            ->where([
                'transaction->domain' => $domain,
                'transaction->extension' => $extension,
            ])
            ->orderByDesc('index_id')
            ->first();

        if (!$transaction) return null;

        switch ($transaction->type) {
            case "update_domain":
            case "create_domain":
                return $transaction->transaction['sender'];
            case "transfer_domain":
                return $transaction->transaction['receiver'];
            default:
                return null;
        }
    }

    public function getDomainBalance($domain)
    {
        $arrDomain = explode(".iol", $domain);

        $domain = $arrDomain[0];

        unset($arrDomain[0]);
        $extension = "iol" . implode('.iol', $arrDomain);

        $transactions = Transaction::where('type', "create_domain")
            ->where([
                'transaction->domain' => $domain,
                'transaction->extension' => $extension,
            ])->orderBy('index_id')->get();

        if (!$transactions) 0;

        $daysToExpire = 0;

        $now = Carbon::now();

        foreach ($transactions as $transaction) {
            $date = Carbon::createFromFormat('YmdHis', $transaction->created_at);

            if ($date >= $now) {
                $daysToExpire += $transaction->transaction['days'];
            } else {
                $expirationDate = $date->addDays($transaction->transaction['days']);

                if ($expirationDate > $now) $daysToExpire += $now->diffInDays($expirationDate);
            }
        }

        return $daysToExpire;
    }

    public function domainHistory(string $domain)
    {
        $arrDomain = explode(".iol", $domain);

        $domain = $arrDomain[0];

        unset($arrDomain[0]);
        $extension = "iol" . implode('.iol', $arrDomain);

        return Transaction::whereIn('type', ["create_domain", "update_domain", "transfer_domain"])
            ->where([
                'transaction->domain' => $domain,
                'transaction->extension' => $extension,
            ])
            ->orderByDesc('index_id')
            ->get();
    }

    public function domainHistoryByWallet(string $walletAddress)
    {
        return Transaction::whereIn('type', ["create_domain", "transfer_domain"])
            ->where(function ($query) use ($walletAddress) {
                $query->orWhere('transaction->receiver', $walletAddress);
                $query->orWhere('transaction->sender', $walletAddress);
                $query->orWhere('transaction->owner', $walletAddress);
            })
            ->orderByDesc('index_id')
            ->get();
    }

    public function getTransactionById(string $transactionId)
    {
        return Transaction::where('uid', $transactionId)->first();
    }

    public function getLastBlock()
    {
        return Block::get()->sortByDesc('index')->first();
    }

    public function getBalance(string $accountAddress)
    {
        $transactions = Transaction::orWhere(['transaction->sender' => $accountAddress])
            ->orWhere('transaction->receiver', $accountAddress)
            ->orderBy('index_id', 'ASC')->get();

        $tmpTransactions = TmpTransaction::orWhere(['transaction->sender' => $accountAddress])
            ->orWhere('transaction->receiver', $accountAddress)->get()->sortByDesc('tip');

        $balance = 0;
        $tmpBalance = 0;

        if ($transactions) {
            foreach ($transactions as $transaction) {
                $balance = $this->_updateBalanceByTransaction($transaction->transaction, $accountAddress, $balance);
            }
        }

        if ($tmpTransactions) {
            foreach ($tmpTransactions as $tmpTransaction) {
                $tmpBalance = $this->_updateBalanceByTransaction($tmpTransaction->transaction, $accountAddress, $tmpBalance);
            }
        }

        return [
            'balance' => $balance,
            'tmpBalance' => $tmpBalance,
            'total' => $balance + $tmpBalance,
        ];
    }

    public function transactionHistory(string $accountAddress)
    {
        $transactions = Transaction::orWhere(['transaction->sender' => $accountAddress])
            ->orWhere('transaction->receiver', $accountAddress)
            ->orderBy('index_id', 'ASC')->get();

        $tmpTransactions = TmpTransaction::orWhere(['transaction->sender' => $accountAddress])
            ->orWhere('transaction->receiver', $accountAddress)->get()->sortByDesc('tip');

        return [
            'blocks' => $transactions,
            'tmp' => $tmpTransactions,
        ];
    }

    public function getAliasOwner(string $alias)
    {
        $transaction = Transaction::where('type', "alias")
            ->where(['transaction->alias' => $alias])
            ->orderByDesc('index_id')
            ->first();

        if (!$transaction) return null;

        $validationWithCurrentAccountAlias = Transaction::where('type', "alias")
            ->where(['transaction->sender' => $transaction->transaction['sender']])
            ->orderByDesc('index_id')
            ->first();

        if ($transaction->transaction['alias'] == $validationWithCurrentAccountAlias->transaction['alias']) return $transaction->transaction['sender'];

        return null;
    }

    public function getAliasByAccountAddress(string $accountAddress)
    {
        $transaction = Transaction::where('type', "alias")
            ->where(['transaction->sender' => $accountAddress])
            ->orderByDesc('index_id')
            ->first();

        if (!$transaction) return null;

        return $transaction->transaction['alias'];
    }

    public function getDataOwner(string $dataId)
    {
        $transaction = Transaction::whereIn('type', ["create_data", "update_data", "transfer_data"])
            ->orWhere(function ($query) use ($dataId) {
                $query->where('type', 'create_data');
                $query->where('transaction->uid', $dataId);
            })
            ->orWhere(function ($query) use ($dataId) {
                $query->where('type', 'update_data');
                $query->where('transaction->data_id', $dataId);
            })
            ->orWhere(function ($query) use ($dataId) {
                $query->where('type', 'transfer_data');
                $query->where('transaction->data_id', $dataId);
            })
            ->orderByDesc('index_id')
            ->first();

        if (!$transaction) return null;

        if ($transaction->type == "create_data") return $transaction->transaction['sender'];

        if ($transaction->type == "update_data") return $transaction->transaction['sender'];

        if ($transaction->type == "transfer_data") return $transaction->transaction['receiver'];

        return null;
    }

    public function getDataByUid(string $dataId)
    {
        return Transaction::whereIn('type', ["create_data", "update_data"])
            ->orWhere(function ($query) use ($dataId) {
                $query->where('type', 'create_data');
                $query->where('transaction->uid', $dataId);
            })
            ->orWhere(function ($query) use ($dataId) {
                $query->where('type', 'update_data');
                $query->where('transaction->data_id', $dataId);
            })
            ->orderByDesc('index_id')
            ->first();
    }

    public function getProductByUid(string $transactionId)
    {
        info($transactionId);
        $transaction = Transaction::whereIn('type', ["product", "remove_product"])
            ->where(function ($query) use ($transactionId) {
                $query->orWhere(function ($query) use ($transactionId) {
                    $query->where('type', 'product');
                    $query->where('transaction->uid', $transactionId);
                });
                $query->orWhere(function ($query) use ($transactionId) {
                    $query->where('type', 'remove_product');
                    $query->where('transaction->transaction_id', $transactionId);
                });
            })
            ->first();

        if (!$transaction || $transaction && $transaction->type == "remove_product") return null;

        return $transaction;
    }

    public function countOrderToProduct(string $transactionId)
    {
        return Transaction::where('type', "order")
            ->where('transaction->transaction_id', $transactionId)
            ->count();
    }

    private function _updateBalanceByTransaction($transaction, $accountAddress, $balance)
    {
        $tip = $transaction['tip'];

        switch ($transaction['type']) {
            case "award":
                $amount = $transaction['amount'];

                if ($transaction['receiver'] == $accountAddress) $balance += $amount;
            case "transfer_iol":
                $amount = $transaction['amount'];

                if ($transaction['sender'] == $accountAddress) $balance -= $amount + $tip;

                if ($transaction['receiver'] == $accountAddress) $balance += $amount;

                break;

            case "create_domain":
                $amount = $transaction['amount'];

                if ($transaction['sender'] == $accountAddress) $balance -= $amount + $tip;

                break;

            case "alias":
            case "transfer_domain":
            case "update_domain":
            case "create_data":
            case "update_data":
            case "transfer_data":
            case "product":
            case "remove_product":
                if ($transaction['sender'] == $accountAddress) $balance -= $tip;

                break;

            case "order":
                if ($transaction['sender'] == $accountAddress) $balance -= $tip + $transaction['amount'];

                if ($transaction['receiver'] == $accountAddress) $balance += $transaction['amount'];
                break;

            default:
        }

        return $balance;
    }
}
