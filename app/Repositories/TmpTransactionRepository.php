<?php

namespace App\Repositories;

use App\Models\TmpTransaction;

class TmpTransactionRepository
{
    public function addTmpTransaction($tmpTransaction)
    {
        $data = [
            'uid' => $tmpTransaction['uid'],
            'type' => $tmpTransaction['type'],
            'transaction' => $tmpTransaction,
            'tip' => $tmpTransaction['tip'],
            'created_at' => $tmpTransaction['created_at'],
        ];

        return TmpTransaction::create($data);
    }

    public function removeTmpTransaction($uid)
    {
        return TmpTransaction::where("uid", $uid)->delete();
    }

    public function removeTransitions()
    {
        return TmpTransaction::truncate();
    }

    public function listTmpTransactions()
    {
        return TmpTransaction::get()->sortByDesc('tip');
    }

    public function countOpenedTransactions()
    {
        return TmpTransaction::count('*');
    }

    public function proccessTmpTransactions()
    {
    }
}
