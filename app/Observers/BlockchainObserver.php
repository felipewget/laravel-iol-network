<?php

namespace App\Observers;

use App\Models\Block;

class BlockchainObserver
{
    public function created(Block $block): void
    {
        if ($block->index % 100) info('dump last 100 blocks to help to sync');
    }
}
