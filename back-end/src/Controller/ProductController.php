<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Model\Product;
use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController
{
    private ProductService $service;
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->service = new ProductService();
        $this->categoryService = new CategoryService();
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $adminUserId = $request->getHeader('admin_user_id')[0];
        
            $queryParams = $request->getQueryParams();
            $active = isset($queryParams['active']) ? $queryParams['active'] : null;
            $categoryId = isset($queryParams['category_id']) ? $queryParams['category_id'] : null;
            $orderBy = isset($queryParams['order_by']) ? $queryParams['order_by'] : null;
        
            $stm = $this->service->getAll($adminUserId, $active, $categoryId, $orderBy);
            $response->getBody()->write(json_encode($stm->fetchAll()));
            return $response->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["menssage" => "Erro interno, não foi possível encontrar os produtos."]));
            return $response->withStatus(500); 
        }
    }
    

    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $idUser = $args['id'];

            $stm = $this->service->getCategoryToIdProduct($idUser);
            $response->getBody()->write(json_encode($stm->fetchAll()));
            return $response->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["menssage" => "Insira um id correto"]));
            return $response->withStatus(500); 
        }
    }

    public function insertOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try{
            $body = $request->getParsedBody();
            $adminUserId = $request->getHeader('admin_user_id')[0];

            if ($this->service->insertOne($body, $adminUserId)) {
                return $response->withStatus(200);
            } else {
                return $response->withStatus(404);
            }

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["menssage" => "Erro interno, não foi possível criar seu produto."]));
            return $response->withStatus(500); 
        }
        
    }

    public function updateOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try{
            $body = $request->getParsedBody();
            $adminUserId = $request->getHeader('admin_user_id')[0];

            if (isset($body['price'])) {
                $this->service->logPriceUpdate($productId, $adminUserId);
            }

            if ($this->service->updateOne($args['id'], $body, $adminUserId)) {
                return $response->withStatus(200);
            } else {
                return $response->withStatus(404);
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["menssage" => "Erro interno, não foi possível atualizar produto."]));
            return $response->withStatus(500); 
        }
    }

    public function deleteOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {   try{
            $adminUserId = $request->getHeader('admin_user_id')[0];
        
            if ($this->service->deleteOne($args['id'], $adminUserId)) {
                return $response->withStatus(200);
            } else {
                return $response->withStatus(404);
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["menssage" => "Erro interno, não foi possível excluir o produto."]));
            return $response->withStatus(500);
        }
    }

    public function getLog(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try{
            $adminUserId = $request->getHeader('admin_user_id')[0];
            $idProduct = $args['id'];
            $queryParams = $request->getQueryParams();


            $action = isset($queryParams['action']) ? $queryParams['action'] : null;

            $stm = $this->service->getLog($idProduct, $adminUserId, $action);
            $response->getBody()->write(json_encode($stm->fetchAll()));
            return $response->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(["menssage" => "Erro interno, não foi encontrar id do seu produto."]));
            return $response->withStatus(500); 
        }
    }
}
