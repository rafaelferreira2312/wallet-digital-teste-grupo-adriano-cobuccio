<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AuthService;
use CodeIgniter\API\ResponseTrait;

class AuthController extends BaseController
{
    use ResponseTrait;
    
    protected $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register()
    {
        $rules = [
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        try {
            $user = $this->authService->register(
                $this->request->getPost('name'),
                $this->request->getPost('email'),
                $this->request->getPost('password')
            );
            
            return $this->respondCreated([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function login()
    {
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        try {
            $token = $this->authService->login(
                $this->request->getPost('email'),
                $this->request->getPost('password')
            );
            
            return $this->respond([
                'message' => 'Login successful',
                'token' => $token
            ]);
        } catch (\RuntimeException $e) {
            return $this->failUnauthorized($e->getMessage());
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}