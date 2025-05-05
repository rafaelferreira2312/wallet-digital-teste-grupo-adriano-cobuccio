<?php
namespace App\Services;

use App\Entities\Transaction;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use CodeIgniter\Exceptions\PageNotFoundException;

class TransactionService
{
    protected $transactionRepo;
    protected $walletRepo;
    protected $db;

    public function __construct(
        TransactionRepository $transactionRepo,
        WalletRepository $walletRepo,
        $db
    ) {
        $this->transactionRepo = $transactionRepo;
        $this->walletRepo = $walletRepo;
        $this->db = $db;
    }

    public function deposit(int $userId, float $amount): Transaction
    {
        $transaction = new Transaction([
            'reference' => bin2hex(random_bytes(18)),
            'to_user_id' => $userId,
            'amount' => $amount,
            'type' => 'deposit',
            'status' => 'pending'
        ]);

        $this->db->transStart();
        
        try {
            $transactionId = $this->transactionRepo->create($transaction);
            
            $this->walletRepo->incrementBalance($userId, $amount);
            
            $this->transactionRepo->updateStatus($transactionId, 'completed');
            
            $this->db->transComplete();
            
            return $this->transactionRepo->findById($transactionId);
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function transfer(int $fromUserId, int $toUserId, float $amount): Transaction
    {
        if ($fromUserId === $toUserId) {
            throw new \InvalidArgumentException('Cannot transfer to yourself');
        }

        $this->db->transStart();
        
        try {
            // Verificar saldo
            $balance = $this->walletRepo->getBalance($fromUserId);
            
            if ($balance < $amount) {
                throw new \RuntimeException('Insufficient balance');
            }
            
            // Criar transação
            $transaction = new Transaction([
                'reference' => bin2hex(random_bytes(18)),
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amount,
                'type' => 'transfer',
                'status' => 'pending'
            ]);
            
            $transactionId = $this->transactionRepo->create($transaction);
            
            // Atualizar saldos
            $this->walletRepo->decrementBalance($fromUserId, $amount);
            $this->walletRepo->incrementBalance($toUserId, $amount);
            
            // Atualizar status
            $this->transactionRepo->updateStatus($transactionId, 'completed');
            
            $this->db->transComplete();
            
            return $this->transactionRepo->findById($transactionId);
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function reverse(int $userId, string $transactionReference): Transaction
    {
        $originalTransaction = $this->transactionRepo->findByReference($transactionReference);
        
        if (!$originalTransaction || !$originalTransaction->isReversible()) {
            throw new PageNotFoundException('Transaction not found or not reversible');
        }
        
        // Verificar se o usuário tem permissão para reverter
        if ($originalTransaction->from_user_id !== $userId 
            && $originalTransaction->to_user_id !== $userId) {
            throw new \RuntimeException('Unauthorized to reverse this transaction');
        }
        
        $this->db->transStart();
        
        try {
            // Criar transação de reversão
            $reversal = new Transaction([
                'reference' => bin2hex(random_bytes(18)),
                'from_user_id' => $originalTransaction->to_user_id,
                'to_user_id' => $originalTransaction->from_user_id,
                'amount' => $originalTransaction->amount,
                'type' => 'reversal',
                'status' => 'pending',
                'original_transaction_id' => $originalTransaction->id
            ]);
            
            $reversalId = $this->transactionRepo->create($reversal);
            
            // Reverter saldos
            if ($originalTransaction->from_user_id) {
                $this->walletRepo->incrementBalance($originalTransaction->from_user_id, $originalTransaction->amount);
            }
            $this->walletRepo->decrementBalance($originalTransaction->to_user_id, $originalTransaction->amount);
            
            // Atualizar status
            $this->transactionRepo->updateStatus($reversalId, 'completed');
            $this->transactionRepo->markAsReversed($originalTransaction->id);
            
            $this->db->transComplete();
            
            return $this->transactionRepo->findById($reversalId);
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}