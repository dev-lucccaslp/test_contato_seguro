<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CompanyService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportController
{
    private ProductService $productService;
    private CompanyService $companyService;
    
    public function __construct()
    {
        $this->productService = new ProductService();
        $this->companyService = new CompanyService();
    }
    
    public function generate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];
        
        $data = [];
        $data[] = [
            'Id do produto',
            'Nome da Empresa',
            'Nome do Produto',
            'Valor do Produto',
            'Categorias do Produto',
            'Data de Criação',
            'Logs de Alterações'
        ];
        
        $stm = $this->productService->getAll($adminUserId);
        $products = $stm->fetchAll();
    
        foreach ($products as $i => $product) {
            $stm = $this->companyService->getNameById($product->company_id);
            $companyName = $stm->fetch()->name;
    
            $action = isset($queryParams['action']) ? $queryParams['action'] : null;
    
            $stm = $this->productService->getLog($product->id, $adminUserId, $action);
            $productLogs = $stm->fetchAll();
            

            $formattedLogs = [];
            foreach ($productLogs as $log) {
                $translatedAction = '';
                switch ($log->action) {
                    case 'create':
                        $translatedAction = 'Criação';
                        break;
                    case 'update':
                        $translatedAction = 'Atualização';
                        break;
                    case 'delete':
                        $translatedAction = 'Remoção';
                        break;
                    default:
                        $translatedAction = '*';
                }
            
                $formattedTimestamp = date('d/m/Y H:i:s', strtotime($log->timestamp));
            
                $formattedLogs[] = "(Usuário: {$log->user_name}, {$translatedAction}, {$formattedTimestamp})";
            }

            $created_at = date('d/m/Y', strtotime($product->created_at));
    
            $data[$i+1][] = $product->id;
            $data[$i+1][] = $companyName;
            $data[$i+1][] = $product->title;
            $data[$i+1][] = $product->price;
            $data[$i+1][] = $product->category;
            $data[$i+1][] = $created_at;
            $data[$i+1][] = implode(', ', $formattedLogs); 
        }
        
        $report = "<table style='font-size: 10px;'>";
        foreach ($data as $row) {
            $report .= "<tr>";
            foreach ($row as $column) {
                $report .= "<td>{$column}</td>";
            }
            $report .= "</tr>";
        }
        $report .= "</table>";
        
        $response->getBody()->write($report);
        return $response->withStatus(200)->withHeader('Content-Type', 'text/html');
    }
}
