<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWalletTables extends Migration
{
    public function up()
    {
        // Tabela de usuários
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('users');

        // Tabela de carteiras
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'unique' => true
            ],
            'balance' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0.00
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wallets');

        // Tabela de transações
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'reference' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
                'unique' => true
            ],
            'from_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true
            ],
            'to_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2'
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['deposit', 'transfer', 'reversal'],
                'default' => 'transfer'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'completed', 'reversed', 'failed'],
                'default' => 'pending'
            ],
            'reversed' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0
            ],
            'original_transaction_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('from_user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('to_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('original_transaction_id', 'transactions', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('transactions');
    }

    public function down()
    {
        // Ordem reversa para evitar erros de chave estrangeira
        $this->forge->dropTable('transactions', true);
        $this->forge->dropTable('wallets', true);
        $this->forge->dropTable('users', true);
    }
}