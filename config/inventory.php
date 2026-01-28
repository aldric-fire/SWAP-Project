<?php
/**
 * Inventory Data Access
 *
 * Centralizes SQL away from presentation files.
 * Uses PDO prepared statements only. (OWASP A03: Injection)
 */

require_once __DIR__ . '/db.php';

function fetch_suppliers(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name ASC');
    return $stmt->fetchAll();
}

function fetch_inventory_items(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT i.item_id, i.item_name, i.category, i.quantity, i.min_threshold, i.status, s.supplier_name
         FROM inventory_items i
         LEFT JOIN suppliers s ON s.supplier_id = i.supplier_id
         ORDER BY i.last_updated_at DESC'
    );

    return $stmt->fetchAll();
}

function fetch_inventory_item(PDO $pdo, int $itemId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM inventory_items WHERE item_id = :id');
    $stmt->execute([':id' => $itemId]);
    $item = $stmt->fetch();

    return $item ?: null;
}

function insert_inventory_item(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO inventory_items (item_name, category, quantity, min_threshold, supplier_id, last_updated_by, status)
         VALUES (:item_name, :category, :quantity, :min_threshold, :supplier_id, :last_updated_by, :status)'
    );

    $stmt->execute([
        ':item_name' => $data['item_name'],
        ':category' => $data['category'],
        ':quantity' => $data['quantity'],
        ':min_threshold' => $data['min_threshold'],
        ':supplier_id' => $data['supplier_id'],
        ':last_updated_by' => $data['last_updated_by'],
        ':status' => $data['status']
    ]);

    return (int)$pdo->lastInsertId();
}

function update_inventory_item(PDO $pdo, array $data): bool
{
    $stmt = $pdo->prepare(
        'UPDATE inventory_items
         SET item_name = :item_name,
             category = :category,
             quantity = :quantity,
             min_threshold = :min_threshold,
             supplier_id = :supplier_id,
             last_updated_by = :last_updated_by,
             status = :status
         WHERE item_id = :id'
    );

    return $stmt->execute([
        ':item_name' => $data['item_name'],
        ':category' => $data['category'],
        ':quantity' => $data['quantity'],
        ':min_threshold' => $data['min_threshold'],
        ':supplier_id' => $data['supplier_id'],
        ':last_updated_by' => $data['last_updated_by'],
        ':status' => $data['status'],
        ':id' => $data['item_id']
    ]);
}

function delete_inventory_item(PDO $pdo, int $itemId): bool
{
    try {
        // Start transaction to ensure data integrity
        $pdo->beginTransaction();
        
        // First, delete all stock requests related to this item
        $stmt = $pdo->prepare('DELETE FROM stock_requests WHERE item_id = :id');
        $stmt->execute([':id' => $itemId]);
        
        // Then delete the inventory item
        $stmt = $pdo->prepare('DELETE FROM inventory_items WHERE item_id = :id');
        $result = $stmt->execute([':id' => $itemId]);
        
        // Commit transaction
        $pdo->commit();
        
        return $result;
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
