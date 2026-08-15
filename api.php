<?php
/**
 * Microservice REST API: api.php
 * Provides full RESTful access to employee resources with JSON responses.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            require __DIR__ . '/get_employees.php';
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            // Check for delete action sent via POST
            if (($input['action'] ?? '') === 'delete' || $action === 'delete') {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    echo json_encode([
                        'success' => true,
                        'message' => "Employee #{$id} deleted successfully"
                    ]);
                    exit;
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
                exit;
            }

            if (empty($input['name']) || empty($input['department']) || empty($input['role']) || empty($input['email'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: name, department, role, email'
                ]);
                exit;
            }

            // Generate unique employee code
            $code = !empty($input['employee_code']) ? trim($input['employee_code']) : 'EMP-' . rand(1000, 9999);

            $stmt = $pdo->prepare("INSERT INTO employees (employee_code, name, department, role, email, phone, status) 
                                   VALUES (:code, :name, :dept, :role, :email, :phone, :status)");
            $stmt->execute([
                'code' => $code,
                'name' => trim($input['name']),
                'dept' => trim($input['department']),
                'role' => trim($input['role']),
                'email' => trim($input['email']),
                'phone' => trim($input['phone'] ?? ''),
                'status' => in_array($input['status'] ?? '', ['Active', 'On Leave', 'Inactive']) ? $input['status'] : 'Active'
            ]);

            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Employee created successfully',
                'id' => (int)$newId,
                'employee_code' => $code
            ]);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
                $stmt->execute(['id' => $id]);
                echo json_encode([
                    'success' => true,
                    'message' => "Employee #{$id} deleted successfully"
                ]);
                exit;
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
