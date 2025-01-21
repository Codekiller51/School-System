<?php
require_once '../../../config/database.php';
require_once '../../../includes/functions.php';
requireAdmin();

// Handle form submission for new fee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO fees (student_id, fee_type, amount, due_date, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['student_id'],
            $_POST['fee_type'],
            $_POST['amount'],
            $_POST['due_date'],
            'pending'
        ]);

        setFlashMessage('success', 'Fee added successfully!');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error adding fee: ' . $e->getMessage());
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all fees with student information
try {
    $stmt = $conn->query("
        SELECT f.*, 
               s.first_name, 
               s.last_name, 
               s.admission_number
        FROM fees f
        JOIN students s ON f.student_id = s.id
        ORDER BY f.due_date DESC
    ");
    $fees = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching fees: ' . $e->getMessage());
    $fees = [];
}

// Get all students for the dropdown
try {
    $stmt = $conn->query("
        SELECT id, first_name, last_name, admission_number 
        FROM students 
        WHERE status = 'active'
        ORDER BY first_name, last_name
    ");
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching students: ' . $e->getMessage());
    $students = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Management - School Management System</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar close">
        <!-- Sidebar content will be loaded via JavaScript -->
    </div>

    <section class="home-section">
        <div class="home-content">
            <i class='bx bx-menu'></i>
            <span class="text">Fee Management</span>
        </div>

        <div class="container-fluid px-4">
            <?php echo displayFlashMessage(); ?>
            
            <div class="card my-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Fee Records</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                        Add New Fee
                    </button>
                </div>
                <div class="card-body">
                    <table id="feesTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fees as $fee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['amount']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $fee['status'] === 'paid' ? 'success' : 
                                                ($fee['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($fee['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="markAsPaid(<?php echo $fee['id']; ?>)">
                                            Mark as Paid
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Add Fee Modal -->
    <div class="modal fade" id="addFeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . 
                                                  ' (' . $student['admission_number'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fee_type" class="form-label">Fee Type</label>
                            <select class="form-select" id="fee_type" name="fee_type" required>
                                <option value="tuition">Tuition Fee</option>
                                <option value="library">Library Fee</option>
                                <option value="laboratory">Laboratory Fee</option>
                                <option value="transport">Transport Fee</option>
                                <option value="other">Other Fee</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Fee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="../../js/script.js"></script>
    <script>
        $(document).ready(function() {
            $('#feesTable').DataTable({
                order: [[4, 'desc']]
            });
        });

        function markAsPaid(feeId) {
            if (confirm('Are you sure you want to mark this fee as paid?')) {
                fetch('mark_paid.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'fee_id=' + feeId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                });
            }
        }
    </script>
</body>
</html>
