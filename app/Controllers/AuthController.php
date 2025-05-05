<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        if ($this->request->getMethod() === 'post') {
            $email = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            
            $userModel = new UserModel();
            $user = $userModel->where('email', $email)->first();
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return redirect()->back()->with('error', 'Credenciais inválidas');
            }
            
            // Criar sessão
            session()->set([
                'user_id' => $user['id'],
                'logged_in' => true
            ]);
            
            return redirect()->to('/dashboard');
        }
        
        return view('auth/login');
    }
    
    public function register()
    {
        if ($this->request->getMethod() === 'post') {
            $rules = [
                'name' => 'required|min_length[3]',
                'email' => 'required|valid_email|is_unique[users.email]',
                'password' => 'required|min_length[8]',
                'confirm_password' => 'matches[password]'
            ];
            
            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', $this->validator->getErrors());
            }
            
            $userModel = new UserModel();
            
            $data = [
                'name' => $this->request->getPost('name'),
                'email' => $this->request->getPost('email'),
                'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT)
            ];
            
            $userModel->insert($data);
            
            // Criar carteira para o usuário
            $walletModel = new \App\Models\WalletModel();
            $walletModel->insert([
                'user_id' => $userModel->getInsertID(),
                'balance' => 0.00
            ]);
            
            return redirect()->to('/login')->with('success', 'Cadastro realizado com sucesso!');
        }
        
        return view('auth/register');
    }
    
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}