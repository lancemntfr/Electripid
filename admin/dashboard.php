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
        SELECT user_id, fname, lname, city, cp_number, acc_status, created_at
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
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" style="border-radius: 0 !important;">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4" href="#" style="color: #1E88E5 !important;">
      <i class="bi bi-lightning-charge-fill me-2" style="color: #00bfa5;"></i>Admin
    </a>
    <div class="d-flex align-items-center">
      <a href="?page=users"
         class="nav-icon position-relative me-3 <?= $page==='users'?'active':'' ?>" 
         title="Users">
        <i class="bi bi-people"></i>
        <small class="d-none d-md-inline ms-1">Users</small>
      </a>
      <a href="?page=donations"
         class="nav-icon position-relative me-3 <?= $page==='donations'?'active':'' ?>" 
         title="Donations">
        <i class="bi bi-cash-coin"></i>
        <small class="d-none d-md-inline ms-1">Donation</small>
      </a>
      <a href="logout.php" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
      </a>
    </div>
  </div>
</nav>

<div class="container px-5 py-4 mt-4">

<?php if ($page === 'users'): ?>
<!-- ================= USERS VIEW ================= -->

<div class="row g-4 mb-4">
  <div class="col-lg-6 col-md-6">
    <div class="info-card h-100 d-flex flex-column">
      <div class="info-card-icon bg-success bg-opacity-10 text-success">
        <i class="bi bi-people-fill"></i>
      </div>
      <h6 class="text-muted mb-1">Total Users</h6>
      <h4 class="mb-0"><?= $totalUsers ?></h4>
    </div>
  </div>
  <div class="col-lg-6 col-md-6">
    <div class="info-card h-100 d-flex flex-column">
      <div class="info-card-icon bg-success bg-opacity-10 text-success">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <h6 class="text-muted mb-1">Active Users</h6>
      <h4 class="mb-0 text-success"><?= $activeUsers ?></h4>
    </div>
  </div>
</div>

<div class="chart-container h-100 d-flex flex-column">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-search me-2"></i>Search Users</h5>
    <div class="d-flex gap-2">
      <input class="form-control" style="width:250px" placeholder="🔍 Search users...">
      <button class="btn btn-outline-secondary">
        <i class="bi bi-funnel"></i> SORT
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>City</th>
          <th>Contact</th>
          <th>Status</th>
          <th>Registered</th>
          <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>

      <?php while($u=mysqli_fetch_assoc($users)): ?>
        <tr>
          <td><strong>#<?= $u['user_id'] ?></strong></td>
          <td>
            <div>
              <strong><?= htmlspecialchars($u['fname'].' '.$u['lname']) ?></strong>
            </div>
          </td>
          <td><?= htmlspecialchars($u['city']) ?></td>
          <td><?= htmlspecialchars($u['cp_number']) ?></td>
          <td>
            <span class="badge bg-<?= $u['acc_status']=='active'?'success':'secondary' ?>">
              <i class="bi bi-<?= $u['acc_status']=='active'?'check-circle':'x-circle' ?>"></i>
              <?= ucfirst($u['acc_status']) ?>
            </span>
          </td>
          <td><small><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
          <td class="text-center">
            <a href="?page=users&edit=<?= $u['user_id'] ?>" 
               class="btn btn-sm btn-warning" 
               title="Edit User">
              <i class="bi bi-pencil-square"></i>
            </a>
            <a href="?page=users&delete=<?= $u['user_id'] ?>"
               onclick="return confirm('Are you sure you want to delete this user?')"
               class="btn btn-sm btn-danger"
               title="Delete User">
              <i class="bi bi-trash"></i>
            </a>
          </td>
        </tr>
      <?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>

<?php elseif ($page === 'donations'): ?>
<!-- ================= DONATIONS VIEW ================= -->

<div class="row g-4 mb-4">
  <div class="col-lg-4 col-md-12">
    <div class="d-flex flex-column h-100">
      <div class="info-card d-flex flex-column py-5" style="flex: 1;">
        <div class="info-card-icon bg-success bg-opacity-10 text-success">
          <i class="bi bi-cash-stack"></i>
        </div>
        <h6 class="text-muted mb-1">Total Donation</h6>
        <h4 class="mb-0 text-success">₱<?= number_format($totalDonation,2) ?></h4>
      </div>
      <div class="info-card d-flex flex-column py-5 mb-0" style="flex: 1;">
        <div class="info-card-icon bg-success bg-opacity-10 text-success">
          <i class="bi bi-people-fill"></i>
        </div>
        <h6 class="text-muted mb-1">Total Donors</h6>
        <h4 class="mb-0"><?= $totalDonors ?></h4>
      </div>
    </div>
  </div>
  
  <div class="col-lg-8 col-md-12">
    <div class="chart-container h-100 d-flex flex-column">
      <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i>Monthly Donation</h5>
      <div class="d-flex align-items-center justify-content-center h-100">
        <div class="text-center text-muted">
          <i class="bi bi-bar-chart-line fs-1 mb-3 d-block"></i>
          <p>Chart visualization will be displayed here</p>
          <small>Track donation patterns over time</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="chart-container h-100 d-flex flex-column">
  <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>List of Donors</h5>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Transaction ID</th>
          <th>Donor Name</th>
          <th>Amount</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>

      <?php while($d=mysqli_fetch_assoc($donations)): ?>
        <tr>
          <td><strong>#<?= $d['donation_id'] ?></strong></td>
          <td>
            <i class="bi bi-person-circle me-2 text-primary"></i>
            <?= htmlspecialchars($d['donor_name']) ?>
          </td>
          <td>
            <strong class="text-success">₱<?= number_format($d['amount'],2) ?></strong>
          </td>
          <td><small><?= date('M d, Y - g:i A', strtotime($d['donation_date'])) ?></small></td>
        </tr>
      <?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>