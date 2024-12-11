<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/check_user_status.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user is blocked
if (!checkUserStatus($pdo, $_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Your account has been blocked. Please contact support for assistance.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // First verify that the order belongs to this user and is in pending status
        $check_stmt = $pdo->prepare("
            SELECT status 
            FROM orders 
            WHERE order_id = ? AND user_id = ? AND status = 'pending'
        ");
        $check_stmt->execute([$order_id, $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            // Update the order status to cancelled
            $update_stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled', 
                    updated_at = CURRENT_TIMESTAMP,
                    cancelled_at = CURRENT_TIMESTAMP,
                    cancelled_by = ?
                WHERE order_id = ?
            ");
            
            if ($update_stmt->execute([$user_id, $order_id])) {
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
            } else {
                throw new PDOException("Failed to update order status");
            }
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Invalid order or status']);
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        // Log the actual error for debugging
        error_log("Cancel Order Error: " . $e->getMessage() . " in " . __FILE__ . " on line " . __LINE__);
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred. Please try again later.',
            'debug' => $e->getMessage() // Remove this in production
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 