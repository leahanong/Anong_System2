<?php
require_once __DIR__ . '/db_config.php';

$message = '';
$error = '';

// Handle Status Toggle Action via GET
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && !empty($_GET['id'])) {
    $toggleId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT status, name FROM employees WHERE id = :id");
        $stmt->execute(['id' => $toggleId]);
        $emp = $stmt->fetch();
        if ($emp) {
            $newStatus = ($emp['status'] === 'Active') ? 'On Leave' : 'Active';
            $updateStmt = $pdo->prepare("UPDATE employees SET status = :status WHERE id = :id");
            $updateStmt->execute(['status' => $newStatus, 'id' => $toggleId]);
            header("Location: index.php?msg=" . urlencode("Status for '{$emp['name']}' updated to {$newStatus}."));
            exit;
        }
    } catch (Exception $e) {
        $error = "Failed to update status: " . $e->getMessage();
    }
}

// Handle Direct HTML Form submission to add employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $code = !empty($_POST['employee_code']) ? trim($_POST['employee_code']) : 'EMP-' . rand(1000, 9999);

    if (empty($name) || empty($department) || empty($role) || empty($email)) {
        $error = "Please fill in all required fields (Name, Department, Role, Email).";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO employees (employee_code, name, department, role, email, phone, status) 
                                   VALUES (:code, :name, :dept, :role, :email, :phone, :status)");
            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'dept' => $department,
                'role' => $role,
                'email' => $email,
                'phone' => $phone,
                'status' => $status
            ]);
            header("Location: index.php?msg=" . urlencode("Personnel '{$name}' registered successfully in Microservice!"));
            exit;
        } catch (Exception $e) {
            $error = "Failed to add employee: " . $e->getMessage();
        }
    }
}

// Handle Employee Edit / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $editId = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if ($editId <= 0 || empty($name) || empty($department) || empty($role) || empty($email)) {
        $error = "Please fill in all required fields to update personnel.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET name = :name, department = :dept, role = :role, email = :email, phone = :phone, status = :status WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'dept' => $department,
                'role' => $role,
                'email' => $email,
                'phone' => $phone,
                'status' => $status,
                'id' => $editId
            ]);
            header("Location: index.php?msg=" . urlencode("Personnel '{$name}' updated successfully!"));
            exit;
        } catch (Exception $e) {
            $error = "Failed to update employee: " . $e->getMessage();
        }
    }
}

// Handle employee deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
        $stmt->execute(['id' => $delId]);
        header("Location: index.php?msg=" . urlencode("Employee removed successfully."));
        exit;
    } catch (Exception $e) {
        $error = "Could not delete employee: " . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Fetch all employees for directory table
$employees = [];
$total_employees = 0;
$active_employees = 0;
try {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
    $employees = $stmt->fetchAll();
    $total_employees = count($employees);
    $active_employees = count(array_filter($employees, fn($e) => $e['status'] === 'Active'));
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Microservice API | Grand Hotel</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen flex flex-col antialiased selection:bg-blue-500 selection:text-white">
    
    <!-- Top Navigation -->
    <header class="border-b border-slate-800/80 bg-slate-900/90 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-sm font-bold text-white tracking-tight leading-tight">Employee Directory Microservice</h1>
                    <p class="text-[11px] text-slate-400">REST API Subsystem for Hotel Personnel</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md shadow-blue-600/30 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>+ Quick Add Staff</span>
                </button>
                <a href="http://localhost:80" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
                    <span>Main System</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        
        <!-- Feedback Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-sm font-medium flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <span>✅</span>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white text-xs">✕</button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-sm font-medium flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-white text-xs">✕</button>
            </div>
        <?php endif; ?>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-900/80 border border-slate-800 p-5 rounded-2xl flex items-center justify-between shadow-sm">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Personnel</span>
                    <span class="text-3xl font-extrabold text-white mt-1 block"><?= $total_employees ?></span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl font-bold">
                    👥
                </div>
            </div>

            <div class="bg-slate-900/80 border border-slate-800 p-5 rounded-2xl flex items-center justify-between shadow-sm">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Active for Dropdowns</span>
                    <span class="text-3xl font-extrabold text-emerald-400 mt-1 block"><?= $active_employees ?></span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold">
                    ✨
                </div>
            </div>

            <div class="bg-slate-900/80 border border-slate-800 p-5 rounded-2xl flex items-center justify-between shadow-sm">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">API Endpoint</span>
                    <a href="get_employees.php" target="_blank" class="text-xs font-mono font-bold text-blue-400 hover:underline mt-1 block">/get_employees.php ↗</a>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xs font-mono font-bold">
                    JSON
                </div>
            </div>
        </div>

        <!-- Easy Quick Add Form (Inline Card) -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl" id="add-staff-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-800">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>➕ Add Personnel</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Enter new staff or click a sample preset button below for 1-click autofill.</p>
                </div>
                <!-- 1-Click Preset Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] text-slate-400 font-medium">Quick Presets:</span>
                    <button type="button" onclick="fillPreset('Front Desk', 'inline')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">
                        + Front Desk
                    </button>
                    <button type="button" onclick="fillPreset('Concierge', 'inline')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">
                        + Concierge
                    </button>
                    <button type="button" onclick="fillPreset('Housekeeping', 'inline')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">
                        + Housekeeper
                    </button>
                    <button type="button" onclick="fillPreset('Guest Services', 'inline')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">
                        + Guest Services
                    </button>
                </div>
            </div>

            <form method="POST" action="index.php" id="staff-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <input type="hidden" name="add_employee" value="1">

                <div>
                    <label for="emp_name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Full Name <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="emp_name" name="name" required placeholder="e.g. Gabriel Santos" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all">
                </div>

                <div>
                    <label for="emp_department" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Department <span class="text-rose-400">*</span>
                    </label>
                    <select id="emp_department" name="department" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all">
                        <option value="Front Desk">Front Desk</option>
                        <option value="Concierge">Concierge</option>
                        <option value="Housekeeping">Housekeeping</option>
                        <option value="Guest Services">Guest Services</option>
                        <option value="Food & Beverage">Food & Beverage</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Security">Security</option>
                    </select>
                </div>

                <div>
                    <label for="emp_role" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Job Role / Title <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="emp_role" name="role" required placeholder="e.g. Senior Receptionist" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all">
                </div>

                <div>
                    <label for="emp_email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Email Address <span class="text-rose-400">*</span>
                    </label>
                    <input type="email" id="emp_email" name="email" required placeholder="e.g. gabriel.s@grandhotel.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all">
                </div>

                <div>
                    <label for="emp_phone" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Phone Number
                    </label>
                    <input type="text" id="emp_phone" name="phone" placeholder="e.g. +63 917 123 4567" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all">
                </div>

                <div>
                    <label for="emp_status" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Status
                    </label>
                    <select id="emp_status" name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all">
                        <option value="Active" selected>Active (Available for Reservations)</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-3 flex items-center justify-between pt-2">
                    <span class="text-xs text-slate-500 font-mono">Auto-generates unique Employee Code</span>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2 cursor-pointer">
                        <span>Save Personnel</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Staff Directory Table with Search & Filter -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <!-- Table Controls Bar -->
            <div class="p-5 border-b border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-950/60">
                <div class="flex items-center gap-2.5">
                    <h3 class="font-bold text-white text-sm">Personnel Directory</h3>
                    <span id="filtered-count" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-800 text-blue-400 font-mono border border-slate-700"><?= $total_employees ?></span>
                    <button type="button" id="reset-filters-btn" style="display: none;" class="text-[11px] font-semibold text-slate-400 hover:text-white px-2 py-0.5 rounded-md bg-slate-800/80 hover:bg-slate-700 transition-colors">
                        Reset Filters ✕
                    </button>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 sm:flex-initial">
                        <input type="text" id="employee-search" placeholder="Search name, role, email, code..." class="w-full sm:w-64 pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    <select id="department-filter" class="px-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-slate-300 focus:outline-none focus:border-blue-500 transition-all cursor-pointer">
                        <option value="all">All Departments</option>
                        <option value="Front Desk">Front Desk</option>
                        <option value="Concierge">Concierge</option>
                        <option value="Housekeeping">Housekeeping</option>
                        <option value="Guest Services">Guest Services</option>
                        <option value="Food & Beverage">Food & Beverage</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Security">Security</option>
                    </select>

                    <select id="status-filter-emp" class="px-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-slate-300 focus:outline-none focus:border-blue-500 transition-all cursor-pointer">
                        <option value="all">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="employee-table">
                    <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-xs border-b border-slate-800 tracking-wider font-mono">
                        <tr>
                            <th class="py-3.5 px-5">Code</th>
                            <th class="py-3.5 px-5">Personnel Name</th>
                            <th class="py-3.5 px-5">Department & Role</th>
                            <th class="py-3.5 px-5">Contact Details</th>
                            <th class="py-3.5 px-5">Dropdown Preview</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5 text-right">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($employees)): ?>
                            <tr class="empty-initial-row">
                                <td colspan="7" class="text-center py-12 text-slate-500">No staff registered yet. Use the form above to add personnel.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr class="hover:bg-slate-800/30 transition-colors" data-department="<?= htmlspecialchars($emp['department']) ?>" data-status="<?= htmlspecialchars($emp['status']) ?>">
                                    <td class="py-3.5 px-5 font-mono text-xs font-bold text-blue-400"><?= htmlspecialchars($emp['employee_code']) ?></td>
                                    <td class="py-3.5 px-5 font-bold text-white"><?= htmlspecialchars($emp['name']) ?></td>
                                    <td class="py-3.5 px-5">
                                        <div class="text-slate-200 font-semibold text-xs"><?= htmlspecialchars($emp['role']) ?></div>
                                        <div class="text-slate-400 text-xs"><?= htmlspecialchars($emp['department']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <div class="text-slate-300 font-mono text-xs"><?= htmlspecialchars($emp['email']) ?></div>
                                        <div class="text-slate-500 text-xs"><?= htmlspecialchars($emp['phone']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono bg-purple-950/60 text-purple-300 border border-purple-800/60">
                                            <span>👤</span>
                                            <span><?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['department']) ?>)</span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <?php if ($emp['status'] === 'Active'): ?>
                                            <a href="index.php?action=toggle_status&id=<?= $emp['id'] ?>" title="Click to toggle to On Leave" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-800/60 hover:bg-emerald-900/60 transition-colors">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                Active
                                            </a>
                                        <?php elseif ($emp['status'] === 'On Leave'): ?>
                                            <a href="index.php?action=toggle_status&id=<?= $emp['id'] ?>" title="Click to toggle to Active" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-950/80 text-amber-400 border border-amber-800/60 hover:bg-amber-900/60 transition-colors">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                                On Leave
                                            </a>
                                        <?php else: ?>
                                            <a href="index.php?action=toggle_status&id=<?= $emp['id'] ?>" title="Click to toggle status" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400 hover:bg-slate-700 transition-colors">
                                                Inactive
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-5 text-right space-x-2 whitespace-nowrap">
                                        <button type="button" onclick='openEditModal(<?= json_encode($emp) ?>)' class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-400 hover:text-blue-300 bg-blue-950/50 hover:bg-blue-950 border border-blue-800/40 transition-colors cursor-pointer">
                                            Edit
                                        </button>
                                        <a href="index.php?action=delete&id=<?= $emp['id'] ?>" onclick="return confirm('Delete employee <?= htmlspecialchars(addslashes($emp['name'])) ?> from Microservice?')" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold text-rose-400 hover:text-rose-300 bg-rose-950/50 hover:bg-rose-950 border border-rose-800/40 transition-colors">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Interactive API Live Tester Sandbox -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>⚡ Live API Sandbox & Endpoint Inspector</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Click any live test button to preview real-time JSON responses generated by this microservice.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="response-status" class="text-xs font-mono font-bold text-emerald-400 bg-slate-950 px-3 py-1 rounded-lg border border-slate-800">Status: Ready</span>
                    <span id="response-time" class="text-xs font-mono text-slate-400"></span>
                </div>
            </div>

            <!-- Endpoint Trigger Buttons -->
            <div class="flex flex-wrap gap-2.5 mb-4">
                <button type="button" onclick="testEndpoint('get_employees.php')" class="px-3 py-2 text-xs font-mono font-semibold rounded-xl bg-slate-800 hover:bg-blue-600 text-slate-200 hover:text-white border border-slate-700 hover:border-blue-500 transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="px-1.5 py-0.5 text-[10px] rounded bg-blue-500/20 text-blue-300 font-bold">GET</span>
                    <span>/get_employees.php (Active)</span>
                </button>
                <button type="button" onclick="testEndpoint('get_employees.php?status=all')" class="px-3 py-2 text-xs font-mono font-semibold rounded-xl bg-slate-800 hover:bg-blue-600 text-slate-200 hover:text-white border border-slate-700 hover:border-blue-500 transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="px-1.5 py-0.5 text-[10px] rounded bg-purple-500/20 text-purple-300 font-bold">GET</span>
                    <span>/get_employees.php?status=all</span>
                </button>
                <button type="button" onclick="testEndpoint('get_employees.php?department=Front+Desk')" class="px-3 py-2 text-xs font-mono font-semibold rounded-xl bg-slate-800 hover:bg-blue-600 text-slate-200 hover:text-white border border-slate-700 hover:border-blue-500 transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="px-1.5 py-0.5 text-[10px] rounded bg-cyan-500/20 text-cyan-300 font-bold">GET</span>
                    <span>?department=Front Desk</span>
                </button>
                <button type="button" onclick="testEndpoint('api.php')" class="px-3 py-2 text-xs font-mono font-semibold rounded-xl bg-slate-800 hover:bg-blue-600 text-slate-200 hover:text-white border border-slate-700 hover:border-blue-500 transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="px-1.5 py-0.5 text-[10px] rounded bg-emerald-500/20 text-emerald-300 font-bold">GET</span>
                    <span>/api.php</span>
                </button>
            </div>

            <!-- Code Preview Display -->
            <div class="relative rounded-xl bg-slate-950 border border-slate-800 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 bg-slate-900/90 border-b border-slate-800 text-xs text-slate-400 font-mono">
                    <span>JSON Payload Viewer</span>
                    <div class="flex items-center gap-2">
                        <button type="button" id="copy-json-btn" onclick="copyConsoleOutput()" class="text-[11px] px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors cursor-pointer">
                            Copy JSON
                        </button>
                        <button type="button" onclick="clearConsole()" class="text-[11px] px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors cursor-pointer">
                            Clear
                        </button>
                    </div>
                </div>
                <pre id="json-viewer" class="p-4 text-xs font-mono text-emerald-400 overflow-x-auto max-h-72 leading-relaxed whitespace-pre-wrap">// Click any endpoint test button above to preview live JSON response...</pre>
            </div>
        </div>

    </main>

    <!-- Quick Add Staff Modal -->
    <div id="add-staff-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950/70">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold text-sm">➕</span>
                    <div>
                        <h3 class="font-bold text-white text-base">Quick Register Staff</h3>
                        <p class="text-[11px] text-slate-400">Instantly creates and syncs personnel in the Microservice</p>
                    </div>
                </div>
                <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors text-sm">✕</button>
            </div>

            <!-- Modal Presets -->
            <div class="px-6 pt-4 pb-1 flex flex-wrap items-center gap-1.5 bg-slate-950/30 border-b border-slate-800/60">
                <span class="text-[11px] text-slate-400 font-semibold mr-1">Autofill:</span>
                <button type="button" onclick="fillPreset('Front Desk', 'modal')" class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">+ Front Desk</button>
                <button type="button" onclick="fillPreset('Concierge', 'modal')" class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">+ Concierge</button>
                <button type="button" onclick="fillPreset('Housekeeping', 'modal')" class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">+ Housekeeper</button>
                <button type="button" onclick="fillPreset('Guest Services', 'modal')" class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors cursor-pointer">+ Guest Services</button>
            </div>

            <form method="POST" action="index.php" class="p-6 space-y-4">
                <input type="hidden" name="add_employee" value="1">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="modal_emp_name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Full Name <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" id="modal_emp_name" name="name" required placeholder="e.g. Elena Ramos" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="modal_emp_department" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Department <span class="text-rose-400">*</span>
                        </label>
                        <select id="modal_emp_department" name="department" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                            <option value="Front Desk">Front Desk</option>
                            <option value="Concierge">Concierge</option>
                            <option value="Housekeeping">Housekeeping</option>
                            <option value="Guest Services">Guest Services</option>
                            <option value="Food & Beverage">Food & Beverage</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Security">Security</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="modal_emp_role" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Role / Title <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" id="modal_emp_role" name="role" required placeholder="e.g. Concierge Supervisor" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="modal_emp_status" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Status
                        </label>
                        <select id="modal_emp_status" name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                            <option value="Active" selected>Active (Available for Reservations)</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="modal_emp_email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Email Address <span class="text-rose-400">*</span>
                        </label>
                        <input type="email" id="modal_emp_email" name="email" required placeholder="e.g. elena.r@grandhotel.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="modal_emp_phone" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Phone Number
                        </label>
                        <input type="text" id="modal_emp_phone" name="phone" placeholder="e.g. +63 917 555 1234" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition-all cursor-pointer">
                        Save Personnel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div id="edit-staff-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-950/70">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-purple-600/20 text-purple-400 flex items-center justify-center font-bold text-sm">✏️</span>
                    <div>
                        <h3 class="font-bold text-white text-base">Edit Personnel Details</h3>
                        <p class="text-[11px] text-slate-400 font-mono">Code: <span id="edit_code_display" class="text-blue-400 font-bold"></span></p>
                    </div>
                </div>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors text-sm">✕</button>
            </div>

            <form method="POST" action="index.php" class="p-6 space-y-4">
                <input type="hidden" name="edit_employee" value="1">
                <input type="hidden" name="id" id="edit_id" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Full Name <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" id="edit_name" name="name" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="edit_department" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Department <span class="text-rose-400">*</span>
                        </label>
                        <select id="edit_department" name="department" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                            <option value="Front Desk">Front Desk</option>
                            <option value="Concierge">Concierge</option>
                            <option value="Housekeeping">Housekeeping</option>
                            <option value="Guest Services">Guest Services</option>
                            <option value="Food & Beverage">Food & Beverage</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Security">Security</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_role" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Role / Title <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" id="edit_role" name="role" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="edit_status" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Status
                        </label>
                        <select id="edit_status" name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                            <option value="Active">Active (Available for Reservations)</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Email Address <span class="text-rose-400">*</span>
                        </label>
                        <input type="email" id="edit_email" name="email" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>

                    <div>
                        <label for="edit_phone" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                            Phone Number
                        </label>
                        <input type="text" id="edit_phone" name="phone" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition-all cursor-pointer">
                        Update Personnel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <div>
                Employee Directory Microservice • Integrated with <strong>Main System</strong>
            </div>
            <div class="flex items-center gap-4">
                <a href="http://localhost:80" class="text-slate-300 hover:text-white transition-colors">← Main System</a>
                <a href="http://localhost:8080" target="_blank" class="text-slate-300 hover:text-white transition-colors">phpMyAdmin (Port 8080)</a>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
