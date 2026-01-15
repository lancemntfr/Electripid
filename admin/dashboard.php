<?php
require_once 'admin_auth.php';
require_once '../connect.php';

$page = $_GET['page'] ?? 'users';

/* ================= USERS DATA ================= */
if ($page === 'users') {

    $totalUsers = mysqli_fetch_assoc(executeQuery(
        "SELECT COUNT(*) total FROM USER"
    ))['total'];

    $activeUsers = mysqli_fetch_assoc(executeQuery(
        "SELECT COUNT(*) total FROM USER WHERE acc_status='active'"
    ))['total'];

    $users = executeQuery("
        SELECT user_id, fname, lname, email, role, city, cp_number, acc_status, source_system, created_at
        FROM USER
        ORDER BY created_at DESC
    ");
}

/* ================= DONATIONS DATA ================= */
if ($page === 'donations') {

    $totalDonation = mysqli_fetch_assoc(executeQuery(
        "SELECT IFNULL(SUM(amount),0) total 
         FROM DONATION
         WHERE donation_status='completed'"
    ))['total'];

    $totalDonors = mysqli_fetch_assoc(executeQuery(
        "SELECT COUNT(DISTINCT user_id) total
         FROM DONATION
         WHERE donation_status='completed'"
    ))['total'];

    $donations = executeQuery("
        SELECT 
            d.donation_id,
            CONCAT(u.fname,' ',u.lname) AS donor_name,
            d.amount,
            d.donation_date
        FROM DONATION d
        JOIN USER u ON d.user_id = u.user_id
        WHERE d.donation_status='completed'
        ORDER BY d.donation_date DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/admin.css"> 
<style>
:root {
    --primary-color: #1E88E5;
    --secondary-color: #6c757d;
    --success-color: #10B981;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --light-bg: #E3F2FD;
}

body {
    background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    padding-bottom: 2rem;
}

.container {
    max-width: 1400px;
}

/* Navbar Styles - Match User Dashboard */
.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 0 !important;
    background: white !important;
}

.navbar-brand {
    font-weight: 700;
    color: #1E88E5 !important;
    font-size: 1.5rem;
}

/* Navigation Icon Buttons - Match User Dashboard */
.nav-icon-btn {
    background: transparent;
    border: none;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.nav-icon-btn:hover {
    color: #1E88E5;
    transform: scale(1.1);
}

.nav-icon-btn.active {
    color: #1E88E5;
}

.nav-icon-btn.active::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 3px;
    background: #1E88E5;
    border-radius: 2px;
}

/* Info Cards - Match User Dashboard Style */
.info-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}

.info-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 1rem;
    background: rgba(16, 185, 129, 0.1) !important;
    color: #10B981 !important;
}

.info-card-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.info-card-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 0;
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Static variant (no animation/hover) */
.info-card-static {
    transition: none !important;
}

.info-card-static:hover {
    transform: none !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
}

/* Chart Container - Match User Dashboard */
.chart-container {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    min-height: 260px;
}

.chart-container h5 {
    font-weight: 600;
    color: #1e3a5f;
    margin-bottom: 1rem;
}

.chart-container h5 i {
    color: var(--primary-color);
}

/* Table Styles - Improved */
.table {
    margin-bottom: 0;
}

/* Scrollable table container */
.table-responsive-scrollable {
    max-height: 600px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(30, 136, 229, 0.5) rgba(0, 0, 0, 0.1);
}

.table-responsive-scrollable::-webkit-scrollbar {
    width: 8px;
}

.table-responsive-scrollable::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
}

.table-responsive-scrollable::-webkit-scrollbar-thumb {
    background: rgba(30, 136, 229, 0.5);
    border-radius: 4px;
}

.table-responsive-scrollable::-webkit-scrollbar-thumb:hover {
    background: rgba(30, 136, 229, 0.7);
}

.table thead {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    color: #495057;
}

.table tbody tr {
    transition: background-color 0.2s;
}

.table tbody tr:hover {
    background-color: rgba(30, 136, 229, 0.05);
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
    text-align: center;
}

/* Badge Styles - Match User Dashboard */
.badge {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.badge.bg-success {
    background: #10B981 !important;
    color: white;
}

.badge.bg-secondary {
    background: #6c757d !important;
    color: white;
}

.badge.bg-primary {
    background: #1E88E5 !important;
    color: white;
}

.badge.bg-danger {
    background: #dc3545 !important;
    color: white;
}

/* Role Badge Styles - Enhanced Design */
.badge-role-admin {
    background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%) !important;
    color: white;
    box-shadow: 0 2px 4px rgba(124, 58, 237, 0.3);
    transition: all 0.3s ease;
}

.badge-role-admin:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(124, 58, 237, 0.4);
}

.badge-role-user {
    background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%) !important;
    color: white;
    box-shadow: 0 2px 4px rgba(30, 136, 229, 0.3);
    transition: all 0.3s ease;
}

.badge-role-user:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(30, 136, 229, 0.4);
}

/* Button Styles - Match User Dashboard */
.btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    transition: all 0.3s;
    border: none;
}

.btn-primary {
    background: #1E88E5;
    color: white;
}

.btn-primary:hover {
    background: #1565C0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 136, 229, 0.4);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.btn-outline-primary {
    color: #1E88E5;
    border: 2px solid #1E88E5;
    background: transparent;
}

.btn-outline-primary:hover {
    background: #1E88E5;
    border-color: #1E88E5;
    color: white;
    transform: translateY(-2px);
}

.btn-outline-danger {
    color: var(--danger-color);
    border: 2px solid var(--danger-color);
    background: transparent;
}

.btn-outline-danger:hover {
    background: var(--danger-color);
    border-color: var(--danger-color);
    color: white;
    transform: translateY(-2px);
}

.btn-outline-secondary {
    color: var(--secondary-color);
    border: 2px solid var(--secondary-color);
    background: transparent;
}

.btn-outline-secondary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
    color: white;
    transform: translateY(-2px);
}

/* Change Button Styles - Match User Dashboard */
.change-btn {
    white-space: nowrap;
    min-width: 40px;
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    border-radius: 8px;
    transition: all 0.3s;
    border: 2px solid;
}

.change-btn.btn-outline-primary {
    border-color: #6c757d;
    color: #6c757d;
    background: white;
}

.change-btn.btn-outline-primary:hover {
    background: #6c757d;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.change-btn.btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
    background: white;
}

.change-btn.btn-outline-danger:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Form Controls - Match User Dashboard */
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.6rem 1rem;
    transition: all 0.3s;
    font-size: 0.9rem;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(30, 136, 229, 0.25);
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Dropdown Menu */
.dropdown-menu {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
    border-radius: 8px;
    margin-top: 0.5rem;
}

.dropdown-item {
    padding: 0.75rem 1.25rem;
    transition: background-color 0.2s;
}

.dropdown-item:hover {
    background-color: rgba(30, 136, 229, 0.1);
}

.dropdown-item.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1);
}

/* Modal Styles */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.modal-header {
    border-bottom: 1px solid #f1f3f5;
    padding: 1.5rem;
}

.modal-title {
    font-weight: 600;
    color: #1e3a5f;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #f1f3f5;
    padding: 1rem 1.5rem;
}

/* Alert Styles */
.alert {
    border-radius: 8px;
    border: none;
    padding: 1rem;
    margin-top: 1rem;
}

.alert-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #c82333;
}

/* Chart Placeholder */
.chart-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    height: 100%;
    min-height: 200px;
    text-align: center;
}

.chart-placeholder i {
    font-size: 3rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.chart-placeholder p {
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.chart-placeholder small {
    color: #adb5bd;
}

/* Spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

/* Search Input Enhancement */
#searchUsers {
    border: 2px solid #e9ecef;
    transition: all 0.3s;
}

#searchUsers:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(30, 136, 229, 0.15);
}

/* No Results Message */
#noResults {
    padding: 3rem 1rem;
}

#noResults i {
    color: #dee2e6;
}

#noResults p {
    color: #6c757d;
    margin-top: 1rem;
}

/* Icons in text */
.bi {
    vertical-align: middle;
}

/* Text colors */
.text-success {
    color: #10B981 !important;
}

.text-primary {
    color: var(--primary-color) !important;
}

.text-danger {
    color: var(--danger-color) !important;
}

.text-muted {
    color: #6c757d !important;
}

.text-secondary {
    color: var(--secondary-color) !important;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.info-card, .chart-container {
    animation: fadeIn 0.5s ease-in-out;
}

/* Responsive */
@media (max-width: 992px) {
    .info-card-value {
        font-size: 1.75rem;
    }
}

@media (max-width: 768px) {
    .navbar-brand {
        font-size: 1.2rem;
    }
    
    .nav-icon-btn {
        font-size: 1.75rem !important;
    }

    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .table {
        font-size: 0.85rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .info-card-value {
        font-size: 1.5rem;
    }

    .change-btn {
        width: 38px;
        height: 38px;
        min-width: 38px;
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    h3 {
        font-size: 1.5rem;
    }
    
    h5 {
        font-size: 1.1rem;
    }

    .info-card {
        padding: 1rem;
    }

    .chart-container {
        padding: 1rem;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 1rem;
    }
}
  
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" style="border-radius: 0 !important;">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4" href="#" style="color: #1E88E5 !important;">
      <i class="bi bi-lightning-charge-fill me-2" style="color: #00bfa5;"></i>Electripid
    </a>
    <div class="d-flex align-items-center">
      <!-- Navigation Links -->
      <a href="?page=users"
         class="nav-icon-btn position-relative me-3 <?= $page==='users'?'active':'' ?>" 
         title="Users"
         style="font-size: 2rem;">
        <i class="bi bi-people"></i>
      </a>
      <a href="?page=donations"
         class="nav-icon-btn position-relative me-3 <?= $page==='donations'?'active':'' ?>" 
         title="Donations"
         style="font-size: 2rem;">
        <i class="bi bi-cash-coin"></i>
      </a>
      
      <!-- User Profile Dropdown -->
      <div class="dropdown ms-2">
        <button class="btn p-0 d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-circle" style="font-size: 2rem; color: #6c757d;"></i>
          <div class="ms-2 text-start d-none d-md-block">
            <div class="fw-semibold" style="font-size: 0.9rem; line-height: 1.2;">
              Admin
            </div>
            <div class="small text-muted" style="font-size: 0.75rem; line-height: 1.2;">
              Administrator
            </div>
          </div>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li class="d-block d-md-none px-3 pt-2 pb-1">
            <div class="fw-semibold">Admin</div>
            <div class="small text-muted">Administrator</div>
          </li>
          <li><hr class="dropdown-divider d-block d-md-none mb-0"></li>
          <li>
            <a class="dropdown-item text-danger" href="logout.php">
              <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>


<div class="container px-5 py-4 mt-4">

<?php if ($page === 'users'): ?>
<!-- ================= USERS VIEW ================= -->

<div class="row g-3 mb-4">
  <div class="col-lg-6 col-md-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="d-flex align-items-center mb-2">
          <div class="rounded-3 p-2 me-3" style="background: rgba(16, 185, 129, 0.1);">
            <i class="bi bi-people-fill text-success fs-4"></i>
          </div>
          <div>
            <h6 class="text-muted mb-1 small">Total Users</h6>
            <h4 class="mb-0 fw-bold"><?= $totalUsers ?></h4>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6 col-md-6">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="d-flex align-items-center mb-2">
          <div class="rounded-3 p-2 me-3" style="background: rgba(16, 185, 129, 0.1);">
            <i class="bi bi-check-circle-fill text-success fs-4"></i>
          </div>
          <div>
            <h6 class="text-muted mb-1 small">Active Users</h6>
            <h4 class="mb-0 fw-bold"><?= $activeUsers ?></h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="chart-container h-100 d-flex flex-column">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
      <input type="text" id="searchUsers" class="form-control" style="width:250px" placeholder="🔍 Search users...">
    </div>
  </div>

  <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
    <table class="table table-hover align-middle" id="usersTable">
      <thead class="table-light">
        <tr>
          <th class="text-center">ID</th>
          <th class="text-center">Name</th>
          <th class="text-center">Email</th>
          <th class="text-center">Role</th>
          <th class="text-center">Source</th>
          <th class="text-center">City</th>
          <th class="text-center">Contact</th>
          <th class="text-center">Status</th>
          <th class="text-center">Registered</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="usersTableBody">

      <?php while($u=mysqli_fetch_assoc($users)): ?>
        <tr data-user-id="<?= $u['user_id'] ?>" 
            data-name="<?= htmlspecialchars(strtolower($u['fname'].' '.$u['lname'])) ?>"
            data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>"
            data-role="<?= htmlspecialchars(strtolower($u['role'])) ?>"
            data-source="<?= htmlspecialchars(strtolower($u['source_system'])) ?>"
            data-city="<?= htmlspecialchars(strtolower($u['city'])) ?>"
            data-status="<?= htmlspecialchars(strtolower($u['acc_status'])) ?>"
            data-date="<?= strtotime($u['created_at']) ?>">
          <td class="text-center"><strong>#<?= $u['user_id'] ?></strong></td>
          <td class="text-center">
            <div>
              <strong><?= htmlspecialchars($u['fname'].' '.$u['lname']) ?></strong>
            </div>
          </td>
          <td class="text-center"><?= htmlspecialchars($u['email']) ?></td>
          <td class="text-center">
            <span class="badge badge-role-<?= $u['role'] ?>">
              <?= ucfirst($u['role']) ?>
            </span>
          </td>
          <td class="text-center"><?= htmlspecialchars($u['source_system']) ?></td>
          <td class="text-center"><?= htmlspecialchars($u['city']) ?></td>
          <td class="text-center"><?= htmlspecialchars($u['cp_number']) ?></td>
          <td class="text-center">
            <span class="badge bg-<?= $u['acc_status']=='active'?'success':'secondary' ?>">
              <i class="bi bi-<?= $u['acc_status']=='active'?'check-circle':'x-circle' ?>"></i>
              <?= ucfirst($u['acc_status']) ?>
            </span>
          </td>
          <td class="text-center"><small><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
          <td class="text-center">
            <button type="button" 
                    class="btn btn-outline-primary change-btn edit-user-btn" 
                    data-user-id="<?= $u['user_id'] ?>"
                    data-fname="<?= htmlspecialchars($u['fname']) ?>"
                    data-lname="<?= htmlspecialchars($u['lname']) ?>"
                    data-email="<?= htmlspecialchars($u['email']) ?>"
                    data-role="<?= htmlspecialchars($u['role']) ?>"
                    data-city="<?= htmlspecialchars($u['city']) ?>"
                    data-contact="<?= htmlspecialchars($u['cp_number']) ?>"
                    data-status="<?= htmlspecialchars($u['acc_status']) ?>"
                    data-name="<?= htmlspecialchars($u['fname'].' '.$u['lname']) ?>"
                    title="Edit User">
              <i class="bi-three-dots-vertical"></i>
            </button>
          </td>
        </tr>
      <?php endwhile; ?>

      </tbody>
    </table>
    <div id="noResults" class="text-center text-muted py-4" style="display: none;">
      <i class="bi bi-search fs-1 d-block mb-2"></i>
      <p>No users found matching your search criteria.</p>
    </div>
  </div>
</div>

<?php elseif ($page === 'donations'): ?>
<!-- ================= DONATIONS VIEW ================= -->

<div class="row g-3 mb-4">
  <div class="col-lg-4 col-md-12">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body p-3 text-center">
        <div class="rounded-3 p-2 mx-auto mb-2" style="background: white; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
          <i class="bi bi-cash-stack text-success fs-4"></i>
        </div>
        <h6 class="text-muted mb-1 small">Total Donation</h6>
        <h4 class="mb-0 fw-bold">₱<?= number_format($totalDonation,2) ?></h4>
      </div>
    </div>
    <div class="card border-0 shadow-sm">
      <div class="card-body p-3 text-center">
        <div class="rounded-3 p-2 mx-auto mb-2" style="background: white; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
          <i class="bi bi-people-fill text-success fs-4"></i>
        </div>
        <h6 class="text-muted mb-1 small">Total Donors</h6>
        <h4 class="mb-0 fw-bold"><?= $totalDonors ?></h4>
      </div>
    </div>
  </div>
  
  <div class="col-lg-8 col-md-12">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-3">
        <h5 class="mb-3"><i class="bi bi-graph-up me-2 text-primary"></i>Monthly Donation</h5>
        <div class="d-flex align-items-center justify-content-center h-100">
          <div class="text-center text-muted">
            <i class="bi bi-bar-chart-line display-4 mb-3 d-block opacity-25"></i>
            <p class="mb-1">Chart visualization will be displayed here</p>
            <small>Track donation patterns over time</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="chart-container h-100 d-flex flex-column">
  <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>List of Donors</h5>

  <div class="table-responsive table-responsive-scrollable">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th class="text-center">Transaction ID</th>
          <th class="text-center">Donor Name</th>
          <th class="text-center">Amount</th>
          <th class="text-center">Date</th>
        </tr>
      </thead>
      <tbody>

      <?php while($d=mysqli_fetch_assoc($donations)): ?>
        <tr>
          <td class="text-center"><strong>#<?= $d['donation_id'] ?></strong></td>
          <td class="text-center">
            <i class="bi bi-person-circle me-2 text-primary"></i>
            <?= htmlspecialchars($d['donor_name']) ?>
          </td>
          <td class="text-center">
            <strong class="text-success">₱<?= number_format($d['amount'],2) ?></strong>
          </td>
          <td class="text-center"><small><?= date('M d, Y - g:i A', strtotime($d['donation_date'])) ?></small></td>
        </tr>
      <?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-white border-bottom py-3">
        <h6 class="modal-title fw-bold mb-0 text-dark" id="editUserModalLabel">
          <i class="bi bi-person-gear me-2"></i>Edit User Information
        </h6>
      </div>
      
      <form id="editUserForm">
        <div class="modal-body p-3">
          <input type="hidden" id="editUserId" name="user_id">
          
          <!-- Personal Information -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted mb-2">
              <i class="bi bi-person-circle me-1"></i>PERSONAL INFORMATION
            </label>
            <div class="row g-2">
              <div class="col-6">
                <input type="text" class="form-control form-control-sm" id="editFname" name="fname" placeholder="First Name" required>
              </div>
              <div class="col-6">
                <input type="text" class="form-control form-control-sm" id="editLname" name="lname" placeholder="Last Name" required>
              </div>
            </div>
          </div>

          <!-- Contact Information -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted mb-2">
              <i class="bi bi-envelope-at me-1"></i>CONTACT INFORMATION
            </label>
            <input type="email" class="form-control form-control-sm mb-2" id="editEmail" name="email" placeholder="Email Address" required>
            <input type="text" class="form-control form-control-sm mb-2" id="editContact" name="cp_number" placeholder="Contact Number">
            <input type="text" class="form-control form-control-sm" id="editCity" name="city" placeholder="City" required>
          </div>

          <!-- Account Settings -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted mb-2">
              <i class="bi bi-gear me-1"></i>ACCOUNT SETTINGS
            </label>
            <select class="form-select form-select-sm mb-2" id="editRole" name="role" required>
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
            <select class="form-select form-select-sm" id="editStatus" name="acc_status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
            <input type="text" class="form-control form-control-sm mt-2" id="editSourceSystem" placeholder="Source system" readonly>
          </div>

          <!-- Alert Container -->
          <div id="editUserAlert"></div>
        </div>

        <div class="modal-footer bg-light border-0 py-2 d-flex justify-content-between">
          <button type="button" class="btn btn-danger btn-sm" id="deleteUserBtn">
            <i class="bi bi-trash me-1"></i>Delete 
          </button>
          <div>
            <button type="button" class="btn btn-secondary btn-sm me-1" data-bs-dismiss="modal">
              <i class=></i>Cancel
            </button>
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bi bi-check-circle me-1"></i>Save Changes
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div class="mb-3">
          <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center" 
               style="width: 80px; height: 80px; background: rgba(220, 53, 69, 0.1);">
            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2.5rem;"></i>
          </div>
        </div>
        <h5 class="fw-bold mb-2">Delete User?</h5>
        <p class="text-muted mb-0" id="deleteUserName">Are you sure you want to delete this user?</p>
        <p class="small text-danger mt-2 mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer border-0 pt-0 pb-3 px-4 justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i>Cancel
        </button>
        <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">
          <i class="bi bi-trash me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search functionality
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchUsers');
  const tableBody = document.getElementById('usersTableBody');
  
  if (!searchInput || !tableBody) return;

  // Search functionality
  searchInput.addEventListener('input', function() {
    filterTable();
  });

  function filterTable() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const rows = Array.from(tableBody.querySelectorAll('tr'));
    
    // Filter rows
    let visibleRows = rows.filter(row => {
      if (!searchTerm) return true;
      
      const name = row.getAttribute('data-name') || '';
      const email = row.getAttribute('data-email') || '';
      const role = row.getAttribute('data-role') || '';
      const source = row.getAttribute('data-source') || '';
      const city = row.getAttribute('data-city') || '';
      const status = row.getAttribute('data-status') || '';
      const userId = row.getAttribute('data-user-id') || '';
      
      return name.includes(searchTerm) || 
             email.includes(searchTerm) ||
             role.includes(searchTerm) ||
             source.includes(searchTerm) ||
             city.includes(searchTerm) || 
             status.includes(searchTerm) ||
             userId.includes(searchTerm);
    });
    
    // Hide all rows first
    rows.forEach(row => row.style.display = 'none');
    
    // Show filtered rows
    visibleRows.forEach(row => row.style.display = '');
    
    // Show/hide no results message
    const noResults = document.getElementById('noResults');
    if (noResults) {
      noResults.style.display = visibleRows.length === 0 ? 'block' : 'none';
    }
  }

  // Edit User functionality
  const editButtons = document.querySelectorAll('.edit-user-btn');
  const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
  let currentUserName = '';
  
  editButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      const userId = this.getAttribute('data-user-id');
      currentUserName = this.getAttribute('data-name');
      document.getElementById('editUserId').value = userId;
      document.getElementById('editFname').value = this.getAttribute('data-fname');
      document.getElementById('editLname').value = this.getAttribute('data-lname');
      document.getElementById('editEmail').value = this.getAttribute('data-email');
      document.getElementById('editRole').value = this.getAttribute('data-role');
      document.getElementById('editCity').value = this.getAttribute('data-city');
      document.getElementById('editContact').value = this.getAttribute('data-contact');
      document.getElementById('editStatus').value = this.getAttribute('data-status');
      const srcRaw = (this.getAttribute('data-source') || '').toLowerCase();
      const srcLabel = srcRaw ? srcRaw.charAt(0).toUpperCase() + srcRaw.slice(1) : '';
      document.getElementById('editSourceSystem').value = srcLabel;
      document.getElementById('editUserAlert').innerHTML = '';
      editModal.show();
    });
  });

  // Edit form submission
  document.getElementById('editUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const alertDiv = document.getElementById('editUserAlert');
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    
    try {
      const response = await fetch('update_user.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        alertDiv.innerHTML = '<div class="alert alert-success">User updated successfully!</div>';
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        alertDiv.innerHTML = `<div class="alert alert-danger">${result.error || 'Failed to update user.'}</div>`;
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    } catch (error) {
      alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Delete User functionality (inside edit modal)
  document.getElementById('deleteUserBtn').addEventListener('click', async function() {
    const userId = document.getElementById('editUserId').value;
    
    if (!confirm(`Are you sure you want to delete user ${currentUserName}? This action cannot be undone.`)) {
      return;
    }
    
    const alertDiv = document.getElementById('editUserAlert');
    const deleteBtn = this;
    const originalText = deleteBtn.innerHTML;
    
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
    
    try {
      const formData = new FormData();
      formData.append('user_id', userId);
      
      const response = await fetch('delete_user.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        alertDiv.innerHTML = '<div class="alert alert-success">User deleted successfully!</div>';
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        alertDiv.innerHTML = `<div class="alert alert-danger">${result.error || 'Failed to delete user.'}</div>`;
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalText;
      }
    } catch (error) {
      alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
      deleteBtn.disabled = false;
      deleteBtn.innerHTML = originalText;
    }
  });
});
</script>
</body>
</html>