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
        $adminUserId = $request->getHeader('admin_user_id')[0];
    
        $queryParams = $request->getQueryParams();
        $active = isset($queryParams['active']) ? $queryParams['active'] : null;
        $categoryId = isset($queryParams['category_id']) ? $queryParams['category_id'] : null;
        $orderBy = isset($queryParams['order_by']) ? $queryParams['order_by'] : null;
    
        $stm = $this->service->getAll($adminUserId, $active, $categoryId, $orderBy);
        $response->getBody()->write(json_encode($stm->fetchAll()));
        return $response->withStatus(200);
    }
    

    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    { try {
        $idUser = $args['id'];

        $stm = $this->service->getCategoryToIdProduct($idUser);
        $response->getBody()->write(json_encode($stm->fetchAll()));
        return $response->withStatus(200);
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode(["menssage" => "Insira um id correto"]));
        // Tratar erro adequadamente (por exemplo, logar o erro)
        return $response->withStatus(500); // Ou o
    }
    }

    public function insertOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->insertOne($body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function updateOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->updateOne($args['id'], $body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function deleteOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->deleteOne($args['id'], $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }
}
