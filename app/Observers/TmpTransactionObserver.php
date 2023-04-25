<?php

namespace App\Observers;

use App\Models\TmpTransaction;
use App\Repositories\BlockRepository;
use App\Repositories\TmpTransactionRepository;

class TmpTransactionObserver
{

    private $blockRepository;

    public function __construct(BlockRepository $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }

    public function created(): void
    {
        if (TmpTransaction::count('*') >= 2) $this->blockRepository->proccessLocalNewBlock();
    }
}
