<?php
require_once 'admin_auth.php';
require_once '../connect.php';

$userCount = mysqli_fetch_assoc(
    executeQuery("SELECT COUNT(*) AS total FROM USER")
)['total'];

$householdCount = mysqli_fetch_assoc(
    executeQuery("SELECT COUNT(*) AS total FROM HOUSEHOLD")
)['total'];

$anomalyCount = mysqli_fetch_assoc(
    executeQuery("SELECT COUNT(*) AS total FROM ANOMALY WHERE status='pending'")
)['total'];

$donationTotal = mysqli_fetch_assoc(
    executeQuery("SELECT IFNULL(SUM(amount),0) AS total FROM DONATION WHERE donation_status='completed'")
)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<!-- GLASS NAVBAR -->
<div class="container-fluid glass-navbar px-3 pt-3">
    <nav class="glass shadow d-flex align-items-center justify-content-between px-4 py-3">
        <span class="fw-bold text-primary fs-5">
            ⚡ Electripid Admin Dashboard
        </span>
        
        <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</div>

<div class="container mt-4">
    
    <!-- DASHBOARD CARDS -->
    <div class="row g-4">
        <div class="col-md-3">
            <div class="glass shadow text-center p-4 h-100">
                <i class="bi bi-people-fill fs-3 text-primary"></i>
                <h6 class="mt-2">Users</h6>
                <h2><?= htmlspecialchars($userCount) ?></h2>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="glass shadow text-center p-4 h-100">
                <i class="bi bi-house-door-fill fs-3 text-success"></i>
                <h6 class="mt-2">Households</h6>
                <h2><?= htmlspecialchars($householdCount) ?></h2>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="glass shadow text-center p-4 h-100">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
                <h6 class="mt-2">Pending Anomalies</h6>
                <h2><?= htmlspecialchars($anomalyCount) ?></h2>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="glass shadow text-center p-4 h-100">
                <i class="bi bi-cash-coin fs-3 text-warning"></i>
                <h6 class="mt-2">Donations</h6>
                <h2>₱<?= number_format($donationTotal, 2) ?></h2>
            </div>
        </div>
    </div>
    
    <!-- RECENT USERS -->
    <div class="glass shadow mt-5">
        <div class="px-4 py-3 fw-bold">
            Recent Users
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-muted">
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $users = executeQuery(
                    "SELECT fname, lname, email, acc_status, role 
                     FROM USER 
                     ORDER BY created_at DESC 
                     LIMIT 5"
                );
                
                while ($row = mysqli_fetch_assoc($users)):
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['fname'].' '.$row['lname']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $row['acc_status']=='active'?'success':'secondary' ?>">
                                <?= ucfirst(htmlspecialchars($row['acc_status'])) ?>
                            </span>
                        </td>
                        <td><?= ucfirst(htmlspecialchars($row['role'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

</body>
</html>