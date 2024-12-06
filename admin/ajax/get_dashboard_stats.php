<?php
require_once '../../includes/admin_middleware.php';
require_once '../../includes/db_connect.php';

checkAdminAccess();

$stmt = $pdo->query("SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
    SUM(CASE WHEN status = 'delivered' THEN total_price ELSE 0 END) as total_revenue
    FROM orders");
    
echo json_encode($stmt->fetch()); 