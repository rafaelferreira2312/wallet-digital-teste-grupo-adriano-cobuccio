<?php
namespace Tests\unit;

use App\Entities\Transaction;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\TransactionService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Database\BaseConnection;

class TransactionServiceTest extends CIUnitTestCase
{
    protected $db;
    protected $transactionService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->db = db_connect();
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('users')->truncate();
        $this->db->table('wallets')->truncate();
        $this->db->table('transactions')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        
        // Criar usuários de teste
        $this->db->table('users')->insertBatch([
            ['name' => 'User 1', 'email' => 'user1@test.com', 'password_hash' => password_hash('password', PASSWORD_BCRYPT)],
            ['name' => 'User 2', 'email' => 'user2@test.com', 'password_hash' => password_hash('password', PASSWORD_BCRYPT)]
        ]);
        
        // Criar carteiras
        $this->db->table('wallets')->insertBatch([
            ['user_id' => 1, 'balance' => 100.00],
            ['user_id' => 2, 'balance' => 50.00]
        ]);
        
        $this->transactionService = new TransactionService(
            new TransactionRepository($this->db),
            new WalletRepository($this->db),
            $this->db
        );
    }
    
    public function testDepositIncreasesBalance()
    {
        $transaction = $this->transactionService->deposit(1, 50.00);
        
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('completed', $transaction->status);
        
        $balance = $this->db->table('wallets')
            ->where('user_id', 1)
            ->get()
            ->getRow()
            ->balance;
            
        $this->assertEquals(150.00, $balance);
    }
    
    public function testTransferBetweenUsers()
    {
        $transaction = $this->transactionService->transfer(1, 2, 30.00);
        
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('completed', $transaction->status);
        
        $senderBalance = $this->db->table('wallets')
            ->where('user_id', 1)
            ->get()
            ->getRow()
            ->balance;
            
        $receiverBalance = $this->db->table('wallets')
            ->where('user_id', 2)
            ->get()
            ->getRow()
            ->balance;
            
        $this->assertEquals(70.00, $senderBalance);
        $this->assertEquals(80.00, $receiverBalance);
    }
    
    public function testTransferFailsWithInsufficientBalance()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance');
        
        $this->transactionService->transfer(1, 2, 150.00);
    }
    
    public function testReverseTransaction()
    {
        // Primeiro fazer uma transferência
        $originalTransaction = $this->transactionService->transfer(1, 2, 30.00);
        
        // Agora reverter
        $reversal = $this->transactionService->reverse(1, $originalTransaction->reference);
        
        $this->assertInstanceOf(Transaction::class, $reversal);
        $this->assertEquals('completed', $reversal->status);
        $this->assertEquals('reversal', $reversal->type);
        
        // Verificar saldos após reversão
        $senderBalance = $this->db->table('wallets')
            ->where('user_id', 1)
            ->get()
            ->getRow()
            ->balance;
            
        $receiverBalance = $this->db->table('wallets')
            ->where('user_id', 2)
            ->get()
            ->getRow()
            ->balance;
            
        $this->assertEquals(100.00, $senderBalance); // Volta ao valor original
        $this->assertEquals(50.00, $receiverBalance); // Volta ao valor original
    }
}