<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAliasRequest;
use App\Http\Requests\CreateDataRequest;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\CreateUpdateDomainRequest;
use App\Http\Requests\DeleteProductRequest;
use App\Http\Requests\ExecuteOrderRequest;
use App\Http\Requests\TransferDataRequest;
use App\Http\Requests\TransferDomainRequest;
use App\Http\Requests\TransferIolRequest;
use App\Http\Requests\UpdateDataRequest;
use App\Models\Block;
use App\Repositories\BlockRepository;
use App\Repositories\DomainRepository;
use App\Repositories\TmpTransactionRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\ValidationRepository;
use Spatie\Crypto\Rsa\PrivateKey;
use Spatie\Crypto\Rsa\PublicKey;

class TransactionController extends Controller
{

    private BlockRepository $blockRepository;
    private TransactionRepository $transactionRepository;
    private TmpTransactionRepository $tmpTransactionRepository;
    private ValidationRepository $validationRepository;
    private DomainRepository $domainRepository;

    public function __construct(BlockRepository $blockRepository, TransactionRepository $transactionRepository, TmpTransactionRepository $tmpTransactionRepository, ValidationRepository $validationRepository, DomainRepository $domainRepository)
    {
        $this->blockRepository = $blockRepository;
        $this->transactionRepository = $transactionRepository;
        $this->tmpTransactionRepository = $tmpTransactionRepository;
        $this->validationRepository = $validationRepository;
        $this->domainRepository = $domainRepository;
    }

    public function getStatus()
    {
        // return $this->transactionRepository->transferIol();
    }

    public function transferIol(TransferIolRequest $request)
    {

        $this->blockRepository->getBalance($request->get('sender'));

        $inputs = $request->only('sender', 'receiver', 'amount', 'tip', 'observation', 'timestamp');

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$request->validated()) abort(403);

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], $inputs['amount'], $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->transferIol($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function createDomain(CreateUpdateDomainRequest $request)
    {
        $inputs = $request->only(['sender', 'domain', 'extension', 'server_addresses', 'amount', 'tip', 'observation', 'timestamp']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$request->validated()) abort(403, 'INVALID PARAMS');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403, 'INVALID SIGNATURE');

        if (!$this->domainRepository->isValisDomain($inputs['domain'], $inputs['extension'])) return abort(403, 'INVALID DOMAIN');

        $daysToBuy = $this->transactionRepository->calcDomainDaysByCoins($inputs['amount']);

        $domainOwner = $this->blockRepository->getLastDomainOwner($inputs['domain'] . "." . $inputs['extension']);

        $daysToDomainExpire = $domainOwner
            ? $this->blockRepository->getDomainBalance($inputs['domain'] . "." . $inputs['extension'])
            : 0;

        if ($domainOwner && ($daysToDomainExpire + $daysToBuy) > 30 * 12 * 3) abort(403, 'MAX ' . (30 * 12 * 3) . ' DAYS PAID FOR DOMAIN');

        if ($domainOwner && $domainOwner != $inputs['sender'] && $daysToDomainExpire > 0) abort(403, 'OWNER ONLY CAN EXTEND THIS DOMAIN EXPIRATION');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], $inputs['amount'], $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->createDomain($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function updateDomain(CreateUpdateDomainRequest $request)
    {

        $inputs = $request->only(['sender', 'domain', 'extension', 'server_addresses', 'tip', 'observation', 'timestamp']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$request->validated()) abort(403, 'INVALID PARAMS');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        $domainOwner = $this->blockRepository->getLastDomainOwner($inputs['domain'] . "." . $inputs['extension']);

        if (!$domainOwner) return abort(404, 'DOMAIN NOT FOUND');

        $daysToDomainExpire = $this->blockRepository->getDomainBalance($inputs['domain'] . "." . $inputs['extension']);

        if ($domainOwner && $domainOwner != $inputs['sender'] && $daysToDomainExpire > 0) abort(403, 'OWNER ONLY CAN MANAGE THIS DOMAIN');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->updateDomain($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function transferDomain(TransferDomainRequest $request)
    {
        $inputs = $request->only(['sender', 'receiver', 'domain', 'extension', 'tip', 'observation', 'timestamp']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$request->validated()) abort(403, 'INVALID PARAMS');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        $domainOwner = $this->blockRepository->getLastDomainOwner($inputs['domain'] . "." . $inputs['extension']);
        if (!$domainOwner) return abort(404, 'DOMAIN NOT FOUND');

        $daysToDomainExpire = $this->blockRepository->getDomainBalance($inputs['domain'] . "." . $inputs['extension']);

        if ($domainOwner && $domainOwner != $inputs['sender'] && $daysToDomainExpire > 0) abort(403, 'OWNER ONLY CAN MANAGE THIS DOMAIN');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->transferDomain($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function createData(CreateDataRequest $request)
    {
        $inputs = $request->only(['sender', 'data', 'tip', 'observation', 'timestamp']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        if (!$this->validationRepository->isValidJson($inputs['data'])) return abort(403, 'DATA CONTENT NEEDS TO BE JSON');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->createData($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function updateData(UpdateDataRequest $request)
    {
        $inputs = $request->only(['sender', 'data_id', 'data', 'tip', 'observation', 'timestamp']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        if (!$this->validationRepository->isValidJson($inputs['data'])) return abort(403, 'DATA CONTENT NEEDS TO BE JSON');

        $dataOwner = $this->blockRepository->getDataOwner($inputs['data_id']);

        if ($dataOwner && $dataOwner != $inputs['sender']) abort(403, 'ONLY OWNER CAN UPDATE');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->updateData($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function transferData(TransferDataRequest $request)
    {
        $inputs = $request->only(['sender', 'data_id', 'receiver', 'tip', 'observation', 'timestamp']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403, 'INVALID SIGNATURE');

        $dataOwner = $this->blockRepository->getDataOwner($inputs['data_id']);

        if ($dataOwner && $dataOwner != $inputs['sender']) abort(403, 'ONLY OWNER CAN UPDATE');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->transferData($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function createProduct(CreateProductRequest $request)
    {
        $inputs = $request->only(['sender', 'data_id', 'tip', 'type', 'observation', 'fields', 'stock', 'timestamp', 'price']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        if (!$this->validationRepository->isValidJson($inputs['fields'])) return abort(403, 'FIELDS NEEDS TO BE JSON');

        if (!$this->transactionRepository->isProductFieldsValid($inputs['fields'])) return abort(403, 'INVALID FIELDS TYPE');

        $transaction = $this->blockRepository->getTransactionById($inputs['data_id']);

        if (!$transaction || $transaction && $transaction->type != "create_data") return abort(403, 'DATA ID SHOULD BE A DATA TRANSACTION');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->createProduct($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function removeProduct(DeleteProductRequest $request)
    {
        $inputs = $request->only(['sender', 'transaction_id', 'tip', 'observation', 'timestamp']);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        $transaction = $this->blockRepository->getProductByUid($inputs['transaction_id']);

        if (!$transaction) return abort(403, 'TRANSACTION ID SHOULD BE A PRODUCT ID');

        if ($transaction && $transaction->transaction['sender'] != $inputs['sender']) return abort(403, 'YOU NEED TO BE OWNER TO DELETE A PRODUCT');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->removeProduct($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function executeOrder(ExecuteOrderRequest $request)
    {
        $inputs = $request->only([
            'sender',
            'transaction_id',
            'tip',
            'observation',
            'fields',
            'timestamp'
        ]);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');

        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403);

        if (!$this->validationRepository->isValidJson($inputs['fields'])) return abort(403, 'FIELDS NEEDS TO BE JSON');
        if (gettype(json_decode($inputs['fields'])) != "object") return abort(403, 'FIELDS SHOULD BE OBJECT');

        $transaction = $this->blockRepository->getProductByUid($inputs['transaction_id']);
        if (!$transaction) return abort(403, 'INVALID PRODUCT');

        $numOfOrders = $this->blockRepository->countOrderToProduct($inputs['transaction_id']);
        if ($numOfOrders > 0 && $transaction->transaction['type'] == "transfer") return  abort(403, 'THIS DATA IS SOLD ALREADY');
        if ($numOfOrders > 0 && $transaction->transaction['stock'] < $numOfOrders) return  abort(403, 'STOCK OUT');
        info($transaction->transaction);
        info('e.e');
        $objInputTransactions = $inputs + ['price' => $transaction->transaction['price'], 'receiver' => $transaction->transaction['sender']];

        $summaries = [
            "sender" => (new PublicKey($publicKey))->encrypt($inputs['fields']),
            "receiver" => (new PublicKey(base64_decode($objInputTransactions['receiver'])))->encrypt($inputs['fields']),
        ];

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], $transaction->transaction['price'], $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->executeOrder($objInputTransactions, ['sender' => $signature], $summaries);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function createAlias(CreateAliasRequest $request)
    {
        $inputs = $request->only(["sender", "alias", "tip", "timestamp"]);

        $publicKey = base64_decode($inputs['sender']);

        $signature = $request->get('signature');
        info(json_encode($inputs));
        if (!$this->validationRepository->verifySignature($publicKey, json_encode($inputs), $signature)) abort(403, 'INVALID SIGNATURE');

        if ($this->blockRepository->getAliasOwner($inputs['alias']) != null) return abort(403, 'THIS ALIAS IS BEEN USED');

        $currentBalance = $this->blockRepository->getBalance($inputs['sender']);
        if (!$this->transactionRepository->hasBalance($currentBalance['total'], 0, $inputs['tip'])) return abort(403, 'NO BALANCE');

        $transaction = $this->transactionRepository->createAlias($inputs, ['sender' => $signature]);

        return $this->tmpTransactionRepository->addTmpTransaction($transaction);
    }

    public function createFirstBlock()
    {
        if (!Block::first()) {
            $transaction = $this->transactionRepository->createAward("felipeWallet", 15000000, "initial amount to manage IOL network");

            $this->blockRepository->createBlock(18032023, "welcome to iol network", [$transaction]);
        }
    }

    public function listOpenedTransactions()
    {
        return $this->tmpTransactionRepository->listTmpTransactions();
    }
}
