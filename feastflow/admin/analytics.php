<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); requireAdmin();

$pdo = getPDO();

// Revenue by month (last 12 months)
$monthly = $pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') AS month, MONTH(created_at) AS m, YEAR(created_at) AS y, COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY y,m ORDER BY y,m")->fetchAll();

// Category revenue
$catRevenue = $pdo->query("SELECT c.name, COALESCE(SUM(oi.subtotal),0) AS revenue, SUM(oi.quantity) AS qty FROM order_items oi JOIN products p ON oi.product_id=p.id JOIN categories c ON p.category_id=c.id GROUP BY c.id ORDER BY revenue DESC")->fetchAll();

// Orders by day of week
$byDay = $pdo->query("SELECT DAYNAME(created_at) AS day, COUNT(*) AS orders FROM orders GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at) ORDER BY DAYOFWEEK(created_at)")->fetchAll();

// Payment method split
$payments = $pdo->query("SELECT payment_method, COUNT(*) AS count FROM orders GROUP BY payment_method")->fetchAll();

// Key metrics
$avgOrder   = $pdo->query("SELECT AVG(total) FROM orders WHERE status='delivered'")->fetchColumn();
$repeatRate = $pdo->query("SELECT COUNT(*) FROM (SELECT user_id FROM orders GROUP BY user_id HAVING COUNT(*)>1) t")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

$pageTitle = 'Analytics';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="breadcrumb"><a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a><i class="ri-arrow-right-s-line"></i>Analytics</div>
    <h1 class="page-title">Business <span>Analytics</span></h1>
    <p class="page-subtitle">Detailed insights into your business performance</p>
  </div>
</div>

<!-- Key Metrics -->
<div class="stats-grid mb-24">
  <div class="stat-card" style="--accent-color:var(--amber)">
    <div class="stat-icon amber"><i class="ri-shopping-bag-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Avg Order Value</div>
      <div class="stat-value"><?= formatPrice((float)$avgOrder) ?></div>
    </div>
  </div>
  <div class="stat-card" style="--accent-color:var(--green)">
    <div class="stat-icon green"><i class="ri-repeat-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Repeat Customers</div>
      <div class="stat-value"><?= $repeatRate ?></div>
      <div class="stat-change up"><?= $totalUsers > 0 ? round($repeatRate/$totalUsers*100) : 0 ?>% of customers</div>
    </div>
  </div>
  <div class="stat-card" style="--accent-color:var(--blue)">
    <div class="stat-icon blue"><i class="ri-percent-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Conversion Rate</div>
      <div class="stat-value"><?= $totalUsers > 0 ? round($repeatRate/$totalUsers*100) : 0 ?>%</div>
    </div>
  </div>
  <div class="stat-card" style="--accent-color:var(--purple)">
    <div class="stat-icon purple"><i class="ri-user-star-line"></i></div>
    <div class="stat-info">
      <div class="stat-label">Total Customers</div>
      <div class="stat-value"><?= $totalUsers ?></div>
    </div>
  </div>
</div>

<!-- Monthly Revenue Chart -->
<div class="card mb-24">
  <div class="card-header"><div class="card-title"><i class="ri-bar-chart-2-line"></i>Monthly Revenue — Last 12 Months</div></div>
  <div class="card-body"><div class="chart-container" style="height:350px"><canvas id="monthlyChart"></canvas></div></div>
</div>

<div class="grid-2 mb-24" style="gap:18px">
  <!-- Category Revenue -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="ri-apps-2-line"></i>Revenue by Category</div></div>
    <div class="card-body"><div class="chart-container"><canvas id="catChart"></canvas></div></div>
  </div>

  <!-- Orders by Day -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="ri-calendar-line"></i>Orders by Day of Week</div></div>
    <div class="card-body"><div class="chart-container"><canvas id="dayChart"></canvas></div></div>
  </div>
</div>

<div class="grid-2 mb-24" style="gap:18px">
  <!-- Payment Methods -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="ri-bank-card-line"></i>Payment Methods</div></div>
    <div class="card-body"><div class="chart-container"><canvas id="paymentChart"></canvas></div></div>
  </div>

  <!-- Category Table -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="ri-trophy-line"></i>Category Performance</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Category</th><th>Revenue</th><th>Items Sold</th></tr></thead>
        <tbody>
        <?php foreach ($catRevenue as $c): ?>
        <tr>
          <td class="fw-bold"><?= sanitize($c['name']) ?></td>
          <td class="text-amber fw-bold"><?= formatPrice($c['revenue']) ?></td>
          <td><?= $c['qty'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
const grid   = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
const text   = isDark ? '#888' : '#666';
const fontFamily = 'Plus Jakarta Sans';

const monthly = <?= json_encode($monthly) ?>;
new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: monthly.map(d => d.month),
    datasets: [
      { label: 'Revenue (₹)', data: monthly.map(d => parseFloat(d.revenue)), backgroundColor: 'rgba(245,158,11,0.7)', borderRadius: 6, yAxisID: 'y' },
      { label: 'Orders', data: monthly.map(d => parseInt(d.orders)), backgroundColor: 'rgba(59,130,246,0.5)', borderRadius: 6, type: 'line', borderColor: '#3b82f6', yAxisID: 'y1', tension: 0.4 }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: text, font: { family: fontFamily } } } },
    scales: {
      x:  { grid:{color:grid}, ticks:{color:text} },
      y:  { grid:{color:grid}, ticks:{color:text, callback: v=>'₹'+v.toLocaleString()}, position:'left' },
      y1: { grid:{display:false}, ticks:{color:'#3b82f6'}, position:'right' }
    }
  }
});

const cats = <?= json_encode($catRevenue) ?>;
const catColors = ['#f59e0b','#ef4444','#8b5cf6','#10b981','#3b82f6','#ec4899'];
new Chart(document.getElementById('catChart'), {
  type: 'doughnut',
  data: { labels: cats.map(c=>c.name), datasets: [{ data: cats.map(c=>parseFloat(c.revenue)), backgroundColor: catColors, borderWidth:0, hoverOffset:8 }] },
  options: { responsive:true, maintainAspectRatio:false, cutout:'65%', plugins: { legend: { position:'bottom', labels:{color:text,font:{family:fontFamily},padding:10,boxWidth:12} } } }
});

const days = <?= json_encode($byDay) ?>;
new Chart(document.getElementById('dayChart'), {
  type: 'bar',
  data: { labels: days.map(d=>d.day), datasets: [{ label:'Orders', data: days.map(d=>parseInt(d.orders)), backgroundColor:'rgba(139,92,246,0.7)', borderRadius:6 }] },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{grid:{color:grid},ticks:{color:text}}, y:{grid:{color:grid},ticks:{color:text}} } }
});

const pay = <?= json_encode($payments) ?>;
new Chart(document.getElementById('paymentChart'), {
  type: 'pie',
  data: { labels: pay.map(p=>p.payment_method.toUpperCase()), datasets: [{ data: pay.map(p=>parseInt(p.count)), backgroundColor:['#f59e0b','#10b981','#3b82f6'], borderWidth:0, hoverOffset:8 }] },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom',labels:{color:text,font:{family:fontFamily},padding:12,boxWidth:12}}} }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
