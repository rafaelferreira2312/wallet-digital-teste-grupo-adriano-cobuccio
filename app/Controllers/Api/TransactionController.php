<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\TransactionService;
use CodeIgniter\API\ResponseTrait;

class TransactionController extends BaseController
{
    use ResponseTrait;
    
    protected $transactionService;

    public function __construct()
    {
        $this->transactionService = new TransactionService(
            new \App\Repositories\TransactionRepository(db_connect()),
            new \App\Repositories\WalletRepository(db_connect()),
            db_connect()
        );
    }

    public function deposit()
    {
        $userId = $this->request->user->id;
        
        $rules = [
            'amount' => 'required|numeric|greater_than[0]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        try {
            $amount = (float) $this->request->getPost('amount');
            $transaction = $this->transactionService->deposit($userId, $amount);
            
            return $this->respond([
                'message' => 'Deposit successful',
                'transaction' => [
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function transfer()
    {
        $fromUserId = $this->request->user->id;
        
        $rules = [
            'to_user_id' => 'required|numeric|is_not_unique[users.id]',
            'amount' => 'required|numeric|greater_than[0]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        try {
            $toUserId = (int) $this->request->getPost('to_user_id');
            $amount = (float) $this->request->getPost('amount');
            
            $transaction = $this->transactionService->transfer($fromUserId, $toUserId, $amount);
            
            return $this->respond([
                'message' => 'Transfer successful',
                'transaction' => [
                    'reference' => $transaction->reference,
                    'to_user_id' => $transaction->to_user_id,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function reverse()
    {
        $userId = $this->request->user->id;
        
        $rules = [
            'transaction_reference' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        try {
            $reference = $this->request->getPost('transaction_reference');
            $transaction = $this->transactionService->reverse($userId, $reference);
            
            return $this->respond([
                'message' => 'Transaction reversed successfully',
                'transaction' => [
                    'reference' => $transaction->reference,
                    'original_reference' => $reference,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status
                ]
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}