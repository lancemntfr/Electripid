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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">

<style>
body { background:#f4f6f9; }
.card { border:none; border-radius:18px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.nav-icon { text-decoration:none; color:#6c757d; transition:all 0.2s; }
.nav-icon:hover { color:#0d6efd; }
.nav-icon.active { color:#0d6efd; font-weight:600; }
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar bg-white shadow-sm mb-4">
  <div class="container-fluid px-4">
    <span class="fw-bold fs-5">
      <i class="bi bi-lightning-fill text-warning me-2"></i>Admin Dashboard
    </span>

    <div class="d-flex gap-4 align-items-center">
      <a href="?page=users"
         class="nav-icon <?= $page==='users'?'active':'' ?>">
        <i class="bi bi-people fs-5"></i>
        <small class="d-none d-md-inline ms-1">Users</small>
      </a>

      <a href="?page=donations"
         class="nav-icon <?= $page==='donations'?'active':'' ?>">
        <i class="bi bi-cash-coin fs-5"></i>
        <small class="d-none d-md-inline ms-1">Donations</small>
      </a>

      <a href="logout.php" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
      </a>
    </div>
  </div>
</nav>

<div class="container">

<?php if ($page === 'users'): ?>
<!-- ================= USERS VIEW ================= -->

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card p-4">
      <small class="text-muted">Total Users</small>
      <h3 class="mb-0 mt-2"><?= $totalUsers ?></h3>
      <small class="text-success"><i class="bi bi-people-fill"></i> All registered</small>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-4">
      <small class="text-muted">Active Users</small>
      <h3 class="mb-0 mt-2 text-success"><?= $activeUsers ?></h3>
      <small class="text-muted"><i class="bi bi-check-circle-fill"></i> Currently active</small>
    </div>
  </div>
</div>

<div class="card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people me-2"></i>User Management</h5>
    <div class="d-flex gap-2">
      <input class="form-control" style="width:250px" placeholder="🔍 Search users...">
      <button class="btn btn-outline-secondary">
        <i class="bi bi-funnel"></i> Filter
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

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card p-4">
      <small class="text-muted">Total Donations</small>
      <h3 class="mb-0 mt-2 text-success">₱<?= number_format($totalDonation,2) ?></h3>
      <small class="text-muted"><i class="bi bi-cash-stack"></i> All completed</small>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-4">
      <small class="text-muted">Total Donors</small>
      <h3 class="mb-0 mt-2"><?= $totalDonors ?></h3>
      <small class="text-muted"><i class="bi bi-people-fill"></i> Unique contributors</small>
    </div>
  </div>
</div>

<div class="card p-4 mb-4" style="min-height:260px">
  <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i>Monthly Donation Trends</h5>
  <div class="d-flex align-items-center justify-content-center h-100">
    <div class="text-center text-muted">
      <i class="bi bi-bar-chart-line fs-1 mb-3 d-block"></i>
      <p>Chart visualization will be displayed here</p>
      <small>Track donation patterns over time</small>
    </div>
  </div>
</div>

<div class="card p-4">
  <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>Donation History</h5>

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