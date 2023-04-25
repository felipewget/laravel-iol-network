<?php

namespace App\Repositories;

use Illuminate\Support\Str;

class TransactionRepository
{

    public function createAward(string $publicAddress, int $amount, string $observation = ""): array
    {
        $baseFields = $this->_getBaseField("award");

        $transaction = [
            "receiver" => $publicAddress,
            "amount" => $amount,
            "observation" => $observation
        ];

        return array_merge($baseFields, $transaction);
    }

    public function transferIol($params, $signatures)
    {
        $baseFields = $this->_getBaseField("transfer_iol", $params['tip']);

        $transaction = [
            "sender" => $params['sender'],
            "receiver" => $params['receiver'],
            "amount" => $params['amount'],
            "observation" => $params['observation'] ?? '',
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures
        ];

        return array_merge($baseFields, $transaction);
    }

    public function createDomain($params, $signatures)
    {
        $baseFields = $this->_getBaseField("create_domain", $params['tip']);

        $transaction = [
            "domain" => $params['domain'],
            "extension" => $params['extension'],
            "days" => $this->calcDomainDaysByCoins($params['amount']), // 1 Iol coin a month
            "amount" => $params['amount'],
            "server_addresses" => $params['server_addresses'],
            "observation" => $params['observation'],
            "sender" => $params['sender'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures
        ];

        return array_merge($baseFields, $transaction);
    }

    public function updateDomain($params, $signatures)
    {
        $baseFields = $this->_getBaseField("update_domain", $params['tip']);

        $transaction = [
            "domain" => $params['domain'],
            "extension" => $params['extension'],
            "server_addresses" => $params['server_addresses'],
            "observation" => $params['observation'],
            "sender" => $params['sender'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures
        ];

        return array_merge($baseFields, $transaction);
    }

    public function transferDomain($params, $signatures)
    {
        $baseFields = $this->_getBaseField("transfer_domain", $params['tip']);

        $transaction = [
            "sender" => $params['sender'],
            "receiver" => $params['receiver'],
            "domain" => $params['domain'],
            "extension" => $params['extension'],
            "observation" => $params['observation'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures,
        ];

        return array_merge($baseFields, $transaction);
    }

    public function createData($params, $signatures)
    {
        $baseFields = $this->_getBaseField("create_data", $params['tip']);

        $transaction = [
            "sender" => $params['sender'],
            "data" => json_decode($params['data']),
            "observation" => $params['observation'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures,
        ];

        return array_merge($baseFields, $transaction);
    }

    public function updateData($params, $signatures)
    {
        $baseFields = $this->_getBaseField("update_data", $params['tip']);

        $transaction = [
            "sender" => $params['sender'],
            "data_id" => $params['data_id'],
            "data" => json_decode($params['data']),
            "observation" => $params['observation'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures,
        ];

        return array_merge($baseFields, $transaction);
    }

    public function transferData($params, $signatures)
    {
        $baseFields = $this->_getBaseField("transfer_data", $params['tip']);

        $transaction = [
            "data_id" => $params['data_id'],
            "sender" => $params['sender'],
            "receiver" => $params['receiver'],
            "observation" => $params['observation'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures,
        ];

        return array_merge($baseFields, $transaction);
    }

    public function createProduct($params, $signatures)
    {
        $baseFields = $this->_getBaseField("product", $params['tip']);

        $transaction = [
            "sender" => $params['sender'],
            "data_id" => $params['data_id'],
            "product_type" => $params['type'], // transfer|order
            "observation" => $params['observation'],
            "timestamp" => $params['timestamp'],
            "price" => (float)$params['price'],
            "signatures" => $signatures,
        ];

        $transaction["fields"] = json_decode($params['fields']); // [{name: "label", type: "text"}]

        if (isset($params['stock']) && $params['type'] != "transfer") $transaction["stock"] = (int)$params['stock'];

        return array_merge($baseFields, $transaction);
    }

    public function removeProduct($params, $signatures)
    {
        $baseFields = $this->_getBaseField("remove_product", $params['tip']);

        $transaction = [
            "sender" => $params['sender'],
            "transaction_id" => $params['transaction_id'],
            "observation" => $params['observation'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures,
        ];

        return array_merge($baseFields, $transaction);
    }

    public function executeOrder($params, $signatures, $summary)
    {
        $baseFields = $this->_getBaseField("order", $params['tip']);

        $transaction = [
            "transaction_id" => $params['transaction_id'],
            "sender" => $params['sender'],
            "receiver" => $params['receiver'],
            "amount" => $params['price'],
            "summary" => $summary,
            "observation" => $params['observation'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures,
        ];

        if (isset($params['stock']) && $params['type'] != "transfer") $transaction["stock"] = $params['stock'];

        return array_merge($baseFields, $transaction);
    }

    public function createAlias($params, $signatures)
    {
        $baseFields = $this->_getBaseField("alias", $params['tip']);

        $transaction = [
            "sender" => $params['sender'],
            "alias" => $params['alias'],
            "timestamp" => $params['timestamp'],
            "signatures" => $signatures,
        ];

        if (isset($transaction['old_alias'])) $transaction['old_alias'] = $params['old_alias'];

        return array_merge($baseFields, $transaction);
    }

    public function createCode($params)
    {
        $baseFields = $this->_getBaseField("code", $params['tip']);

        $transaction = [
            "owner" => 'adasdsa',
            "code" => $params['code'],
            "keys" => [
                "sender" => "sdsadasdasda",
                "owner" => "sdsadasdasda",
            ]
        ];

        if (isset($transaction['old_alias'])) $transaction['old_alias'] = $params['old_alias'];

        return array_merge($baseFields, $transaction);
    }

    private function _getBaseField(String $type, float $tip = 0)
    {
        return [
            "uid" => Str::uuid()->toString(),
            "type" => $type,
            "created_at" => date('YmdHis'),
            "tip" => $tip,
        ];
    }

    public function calcDomainDaysByCoins(int $iolCoins)
    {
        return $iolCoins * 30;
    }

    public function isProductFieldsValid($productFields)
    {
        $fields = json_decode($productFields);

        if (gettype($fields) != "array") return false;

        foreach ($fields as $field) {
            if (!(isset($field->name) && isset($field->type))) return false;
        }

        return true;
    }

    public function hasBalance($balance, $amount, $tip)
    {
        return ($balance - ($amount + $tip)) > 0;
    }
}
