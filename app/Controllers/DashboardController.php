<?php

namespace App\Controllers;

use App\Services\TransactionService;

class DashboardController extends BaseController
{
    protected $transactionService;
    
    public function __construct()
    {
        $this->transactionService = new TransactionService(
            new \App\Repositories\TransactionRepository(db_connect()),
            new \App\Repositories\WalletRepository(db_connect()),
            db_connect()
        );
    }
    
    public function index()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }
        
        $userId = session()->get('user_id');
        
        // Obter saldo e transações
        $walletRepo = new \App\Repositories\WalletRepository(db_connect());
        $balance = $walletRepo->getBalance($userId);
        
        $transactionRepo = new \App\Repositories\TransactionRepository(db_connect());
        $transactions = $transactionRepo->getUserTransactions($userId);
        
        return view('dashboard', [
            'balance' => $balance,
            'transactions' => $transactions
        ]);
    }
}