<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class CategoryService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId)
    {
        $query = "
        SELECT p.*, c.title as category
        FROM product p
        INNER JOIN product_category pc ON pc.product_id = p.id
        INNER JOIN category c ON c.id = pc.category_id
        WHERE p.company_id = {$adminUserId}
    ";


        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
    }

    public function getOne( $productId)
    {

        $query = "
        SELECT p.*, c.title as category
        FROM product p
        INNER JOIN product_category pc ON pc.product_id = p.id
        INNER JOIN category c ON c.id = pc.category_id
        WHERE  p.id =  {$productId}
    ";


        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
    }

    public function getProductCategory($productId)
    {
        $query = "
            SELECT c.id
            FROM category c
            INNER JOIN product_category pc
                ON pc.cat_id = c.id
            WHERE pc.product_id = {$productId}
        ";

        $stm = $this->pdo->prepare($query);

        $stm->execute();

        return $stm;
    }

    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO category (
                company_id,
                title,
                active
            ) VALUES (
                {$this->getCompanyFromAdminUser($adminUserId)},
                '{$body['title']}',
                {$body['active']}
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $active = (int)$body['active'];

        $stm = $this->pdo->prepare("
            UPDATE category
            SET title = '{$body['title']}',
                active = {$active}
            WHERE id = {$id}
            AND company_id = {$this->getCompanyFromAdminUser($adminUserId)}
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE
            FROM category
            WHERE id = {$id}
            AND company_id = {$this->getCompanyFromAdminUser($adminUserId)}
        ");

        return $stm->execute();
    }

    private function getCompanyFromAdminUser($adminUserId)
    {
        $query = "
            SELECT company_id
            FROM admin_user
            WHERE id = {$adminUserId}
        ";

        $stm = $this->pdo->prepare($query);
        
        $stm->execute();

        return $stm->fetch()->company_id;
    }
}
