<?php

namespace App\Repositories;

use App\Models\Block;
use Spatie\Crypto\Rsa\PublicKey;

class ValidationRepository
{
    public function getDifficultyLevel()
    {
        return 4;
    }

    public function proofOfWork(int $previousProof, int $dificultyLevel): int
    {
        $goldenNounce = 0;

        while (true) {
            $hash = hash('sha256', (string)(pow($goldenNounce, 2) - pow($previousProof, 2)));

            if (str_replace("0", "", substr($hash, 0, $dificultyLevel)) == "") return $goldenNounce;

            ++$goldenNounce;
        }
    }

    public function checkValidProof(int $previousProof, int $goldenNounce, int $dificultyLevel): bool
    {
        $hash = hash('sha256', (string)(pow($goldenNounce, 2) - pow($previousProof, 2)));

        return str_replace("0", "", substr($hash, 0, $dificultyLevel)) == "";
    }

    public function validateBlockChain(): bool|int
    {
        $allBlocks = Block::get();

        $lastBlock = null;
        foreach ($allBlocks as $index => $block) {
            if ($index > 1 && $lastBlock) { // Fisrt is genesis, second if non based on hash.. start at 3 block
                $check = $this->checkValidProof($lastBlock->proof_of_work, $block->proof_of_work, 2);

                if ($block->previous_hash != $this->getBlockHash($lastBlock)) return $index;
                if (!$check) return $index;
            }

            $lastBlock = $block;
        }

        return true;
    }

    public function getBlockHash(Block $block): string
    {
        $transactions = $block->transactions->sortBy('uid')->toArray();

        $jsonBlock = json_encode([
            'proof_of_work' => $block->proof_of_work,
            'previous_hash' => $block->previous_hash,
            'transactions' => $transactions,
        ]);

        return hash('sha256', $jsonBlock);
    }

    public function verifySignature($publicKey, $message, $sign)
    {
        info($message);
        return (new PublicKey($publicKey))->verify($message, $sign);
    }

    public function isValidJson(string $jsonStringfy)
    {
        $result = json_decode($jsonStringfy);

        if (json_last_error() === 0) return true;

        return false;
    }
}
