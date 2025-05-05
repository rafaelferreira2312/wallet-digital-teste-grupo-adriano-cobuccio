<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class JWTAuthenticationFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getServer('HTTP_AUTHORIZATION');
        
        if (!$authHeader) {
            return Services::response()
                ->setJSON(['error' => 'Authorization header missing'])
                ->setStatusCode(401);
        }
        
        try {
            $token = sscanf($authHeader, 'Bearer %s')[0] ?? '';
            
            if (empty($token)) {
                throw new Exception('Token not provided');
            }
            
            $decoded = Services::jwt()->decode($token);
            
            // Adicionar usuário à requisição
            $request->user = (object) [
                'id' => $decoded->user_id,
                'email' => $decoded->email
            ];
            
        } catch (Exception $e) {
            return Services::response()
                ->setJSON(['error' => $e->getMessage()])
                ->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada a fazer após a requisição
    }
}