<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

$stats   = getDashboardStats();
$orders  = getOrders(['limit' => 8]);
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="#">Home</a><i class="ri-arrow-right-s-line"></i>Dashboard</div>
    <h1 class="page-title">Dashboard <span>Overview</span></h1>
    <p class="page-subtitle">Welcome back, <?= sanitize($_SESSION['user_name']) ?>! Here's what's happening today.</p>
  </div>
  <a href="<?= APP_URL ?>/admin/products.php?action=add" class="btn btn-primary">
    <i class="ri-add-line"></i> Add Product
  </a>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
  <div class="stat-card" style="--accent-color:var(--amber)">
    <div class="stat-icon amber"><i class="ri-money-rupee-circle-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value" data-target="<?= (int)$stats['total_revenue'] ?>" data-type="price">₹0</div>
      <div class="stat-change up"><i class="ri-arrow-up-line"></i> Today: <?= formatPrice($stats['today_revenue']) ?></div>
    </div>
  </div>
  <div class="stat-card" style="--accent-color:var(--blue)">
    <div class="stat-icon blue"><i class="ri-file-list-3-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value" data-target="<?= $stats['total_orders'] ?>">0</div>
      <div class="stat-change up"><i class="ri-arrow-up-line"></i> Today: <?= $stats['today_orders'] ?> orders</div>
    </div>
  </div>
  <div class="stat-card" style="--accent-color:var(--green)">
    <div class="stat-icon green"><i class="ri-group-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Total Customers</div>
      <div class="stat-value" data-target="<?= $stats['total_users'] ?>">0</div>
      <div class="stat-change up"><i class="ri-arrow-up-line"></i> <?= $stats['active_users'] ?> active</div>
    </div>
  </div>
  <div class="stat-card" style="--accent-color:var(--purple)">
    <div class="stat-icon purple"><i class="ri-restaurant-2-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Active Products</div>
      <div class="stat-value" data-target="<?= $stats['total_products'] ?>">0</div>
      <div class="stat-change"><i class="ri-time-line"></i> <?= $stats['pending_orders'] ?> pending orders</div>
    </div>
  </div>
</div>

<!-- Charts Row -->
<div class="grid-2 mb-24" style="gap:18px">
  <!-- Revenue Chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-line-chart-line"></i>Revenue — Last 7 Days</div>
    </div>
    <div class="card-body">
      <div class="chart-container">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Order Status Donut -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-pie-chart-line"></i>Order Status Breakdown</div>
    </div>
    <div class="card-body">
      <div class="chart-container">
        <canvas id="statusChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Row -->
<div class="grid-2" style="gap:18px">
  <!-- Recent Orders -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-file-list-3-line"></i>Recent Orders</div>
      <a href="<?= APP_URL ?>/admin/orders.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
        <tr data-searchable="<?= $o['order_number'] . ' ' . $o['customer_name'] ?>">
          <td>
            <a href="<?= APP_URL ?>/admin/view-order.php?id=<?= $o['id'] ?>" class="text-amber fw-bold"><?= sanitize($o['order_number']) ?></a>
            <div class="text-sm text-muted"><?= timeAgo($o['created_at']) ?></div>
          </td>
          <td><?= sanitize($o['customer_name']) ?></td>
          <td class="fw-bold"><?= formatPrice($o['total']) ?></td>
          <td><?= statusBadge($o['status']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Products -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="ri-trophy-line"></i>Top Products</div>
      <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php foreach ($stats['top_products'] as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="width:28px;height:28px;background:var(--amber-glow);border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:12px;color:var(--amber)"><?= $i+1 ?></div>
        <div style="flex:1">
          <div style="font-size:13.5px;font-weight:600"><?= sanitize($p['name']) ?></div>
          <div class="text-sm text-muted"><?= $p['total_orders'] ?> orders · <?= formatPrice($p['price']) ?></div>
        </div>
        <div style="width:80px;height:6px;background:var(--bg-input);border-radius:3px;overflow:hidden">
          <div style="height:100%;width:<?= min(100, ($p['total_orders'] / max(1, $stats['top_products'][0]['total_orders'])) * 100) ?>%;background:var(--amber);border-radius:3px"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Revenue Chart
const weeklyData = <?= json_encode($stats['weekly_data']) ?>;
const labels = weeklyData.map(d => {
  const date = new Date(d.date);
  return date.toLocaleDateString('en-IN', {weekday:'short', day:'numeric'});
});
const revenues = weeklyData.map(d => parseFloat(d.revenue));

const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
const textColor = isDark ? '#888' : '#666';

new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label: 'Revenue (₹)',
      data: revenues,
      borderColor: '#f59e0b',
      backgroundColor: 'rgba(245,158,11,0.1)',
      borderWidth: 2.5,
      pointBackgroundColor: '#f59e0b',
      pointRadius: 4,
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Plus Jakarta Sans' } } },
      y: { grid: { color: gridColor }, ticks: { color: textColor, callback: v => '₹' + v.toLocaleString() } }
    }
  }
});

// Status Donut Chart
const statusData = <?= json_encode($stats['order_status']) ?>;
const statusColors = { pending:'#f59e0b', confirmed:'#3b82f6', preparing:'#8b5cf6', out_for_delivery:'#ec4899', delivered:'#10b981', cancelled:'#ef4444' };
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: statusData.map(d => d.status.replace('_',' ')),
    datasets: [{
      data: statusData.map(d => d.count),
      backgroundColor: statusData.map(d => statusColors[d.status] || '#888'),
      borderWidth: 0,
      hoverOffset: 6
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Plus Jakarta Sans', size: 12 }, padding: 12, boxWidth: 12 } }
    },
    cutout: '68%'
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
