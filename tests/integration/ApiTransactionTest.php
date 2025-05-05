<?php
namespace Tests\integration;

use CodeIgniter\Test\CIDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class ApiTransactionTest extends CIDatabaseTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    
    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $namespace = 'App';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar usuário de teste
        $this->db->table('users')->insert([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT)
        ]);
        
        // Criar carteira
        $this->db->table('wallets')->insert([
            'user_id' => 1,
            'balance' => 100.00
        ]);
        
        // Gerar token JWT
        $this->token = \Config\Services::jwt()->encode([
            'user_id' => 1,
            'email' => 'test@example.com'
        ]);
    }
    
    public function testDeposit()
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->post('/api/deposit', [
            'amount' => 50.00
        ]);
        
        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'Deposit successful']);
        
        // Verificar se o saldo foi atualizado
        $balance = $this->db->table('wallets')
            ->where('user_id', 1)
            ->get()
            ->getRow()
            ->balance;
            
        $this->assertEquals(150.00, $balance);
    }
    
    public function testTransfer()
    {
        // Criar segundo usuário
        $this->db->table('users')->insert([
            'name' => 'Receiver User',
            'email' => 'receiver@example.com',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT)
        ]);
        
        $this->db->table('wallets')->insert([
            'user_id' => 2,
            'balance' => 50.00
        ]);
        
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->post('/api/transfer', [
            'to_user_id' => 2,
            'amount' => 30.00
        ]);
        
        $result->assertStatus(200);
        $result->assertJSONFragment(['message' => 'Transfer successful']);
        
        // Verificar saldos
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
    
    public function testTransferWithInsufficientBalance()
    {
        $this->db->table('users')->insert([
            'name' => 'Receiver User',
            'email' => 'receiver@example.com',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT)
        ]);
        
        $result = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->post('/api/transfer', [
            'to_user_id' => 2,
            'amount' => 150.00
        ]);
        
        $result->assertStatus(400);
        $result->assertJSONFragment(['message' => 'Insufficient balance']);
    }
}