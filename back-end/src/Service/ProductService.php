<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class ProductService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId, $active = null, $categoryId = null, $orderBy = null)
    {
        $query = "
            SELECT p.*, c.title as category
            FROM product p
            INNER JOIN product_category pc ON pc.product_id = p.id
            INNER JOIN category c ON c.id = pc.cat_id
            WHERE p.company_id = {$adminUserId}
        ";
    
        // Aplicar filtros
        if ($active !== null) {
            $query .= " AND p.active = {$active}";
        }
        if ($categoryId !== null) {
            $query .= " AND pc.cat_id = {$categoryId}";
        }
    
        //  ordenamento
        if ($orderBy !== null) {
            if ($orderBy === 'created_at') {
                $query .= " ORDER BY p.created_at";
            } else {
                $query .= " ORDER BY {$orderBy}";
            }
        }
    
        $stm = $this->pdo->prepare($query);
        $stm->execute();
    
        return $stm;
    }
    

    public function getCategoryToIdProduct($productId)
    {
        $query = "
            SELECT p.*, c.title as category
            FROM product p
            INNER JOIN product_category pc ON pc.product_id = p.id
            INNER JOIN category c ON c.id = pc.cat_id
            WHERE p.id = {$productId}
        ";

        $stm = $this->pdo->prepare($query);

        $stm->execute();

        return $stm;
    }

    public function getOne($id)
    {
        $stm = $this->pdo->prepare("
            SELECT *
            FROM product
            WHERE id = {$id}
        ");
        $stm->execute();

        return $stm;
    }

    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO product (
                company_id,
                title,
                price,
                active
            ) VALUES (
                {$body['company_id']},
                '{$body['title']}',
                {$body['price']},
                {$body['active']}
            )
        ");
        if (!$stm->execute())
            return false;

        $productId = $this->pdo->lastInsertId();

        $stm = $this->pdo->prepare("
            INSERT INTO product_category (
                product_id,
                cat_id
            ) VALUES (
                {$productId},
                {$body['category_id']}
            );
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$productId},
                {$adminUserId},
                'create'
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            UPDATE product
            SET company_id = {$body['company_id']},
                title = '{$body['title']}',
                price = {$body['price']},
                active = {$body['active']}
            WHERE id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = {$body['category_id']}
            WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'update'
            )
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE FROM product_category WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;
        
        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'delete'
            )
        ");

        return $stm->execute();
    }

    // public function getLog($id)
    // {
    //     $stm = $this->pdo->prepare("
    //         SELECT *
    //         FROM product_log
    //         WHERE product_id = {$id}
    //         ORDER BY timestamp DESC
    //     ");
    //     $stm->execute();

    //     return $stm;
    // }

    public function getLog($idProduct, $adminUserId ,$action = null)
    {
        $query = "
            SELECT pl.*, au.name AS user_name
            FROM product_log pl
            INNER JOIN admin_user au ON pl.admin_user_id = au.id
            WHERE pl.product_id = {$idProduct} 
            AND pl.admin_user_id = {$adminUserId}
        ";
    
        if ($action !== null) {
            $query .= " AND pl.action = '{$action}'";
        }
    
        $query .= " ORDER BY pl.timestamp DESC";
    
        $stm = $this->pdo->prepare($query);
        $stm->execute();
    
        return $stm;
    }
    

    public function getOneproductCategory($productId)
    {

        $stm = $this->pdo->prepare( "
        SELECT p.*, c.title as category
        FROM product p
        WHERE  p.id =  {$productId}
    ");
        $stm->execute();

        return $query;
    }
}
