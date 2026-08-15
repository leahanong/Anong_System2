<?php
/**
 * Microservice API Endpoint: get_employees.php
 * Returns staff/employee records in JSON format for the Main System dropdowns.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $status = isset($_GET['status']) ? trim($_GET['status']) : 'Active';
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id, employee_code, name, department, role, email, phone, status FROM employees WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $employee = $stmt->fetch();

        if ($employee) {
            echo json_encode([
                'success' => true,
                'data' => $employee,
                'timestamp' => date('c')
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Employee not found'
            ]);
        }
        exit;
    }

    $query = "SELECT id, employee_code, name, department, role, email, phone, status FROM employees WHERE 1=1";
    $params = [];

    if ($status !== 'all') {
        $query .= " AND status = :status";
        $params['status'] = $status;
    }

    if (!empty($department)) {
        $query .= " AND department = :department";
        $params['department'] = $department;
    }

    $query .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();

    // Format for dropdowns and full view
    $formatted = array_map(function($emp) {
        return [
            'id' => (int)$emp['id'],
            'employee_code' => $emp['employee_code'],
            'name' => $emp['name'],
            'department' => $emp['department'],
            'role' => $emp['role'],
            'email' => $emp['email'],
            'phone' => $emp['phone'],
            'status' => $emp['status'],
            'display_label' => "{$emp['name']} ({$emp['role']} - {$emp['department']})"
        ];
    }, $employees);

    echo json_encode([
        'success' => true,
        'count' => count($formatted),
        'data' => $formatted,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
