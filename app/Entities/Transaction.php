<?php
namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Transaction extends Entity
{
    protected $attributes = [
        'id' => null,
        'reference' => null,
        'from_user_id' => null,
        'to_user_id' => null,
        'amount' => null,
        'type' => null,
        'status' => null,
        'reversed' => null,
        'original_transaction_id' => null
    ];

    public function isReversible(): bool
    {
        return !$this->attributes['reversed'] 
            && $this->attributes['status'] === 'completed'
            && in_array($this->attributes['type'], ['deposit', 'transfer']);
    }
}