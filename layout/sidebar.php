<aside class="sidebar">
  <div>
    <div class="logo-section">
      <div class="logo"><i class="fas fa-cube"></i></div>
      <div class="company-info">
        <h3>Makgrow Impex</h3>
        <p>Import & Distribution</p>
      </div>
    </div>

    <nav class="nav-menu">
      <div class="nav-section">
        <h4>OPERATIONS</h4>
        <ul>
          <li onclick="window.location.href='<?= $baseURL ?>dashboard/dashboard.php'">
            <i class="fas fa-chart-line"></i><span>Dashboard</span>
          </li>
          <li onclick="window.location.href='<?= $baseURL ?>import_management/import_management.php'">
            <i class="fas fa-download"></i><span>Import Management</span>
          </li>
          <li onclick="window.location.href='<?= $baseURL ?>order_management/index.php'">
            <i class="fas fa-boxes"></i><span>Order Management</span>
          </li>
          <li onclick="window.location.href='<?= $baseURL ?>delivery_management/delivery.php'">
            <i class="fas fa-truck"></i><span>Delivery Management</span>
          </li>
          <li onclick="window.location.href='<?= $baseURL ?>suppliers_buyers/frontend/supplier.php'">
            <i class="fas fa-users"></i><span>Suppliers & Buyers</span>
          </li>
          <li onclick="window.location.href='<?= $baseURL ?>finance_management/Finance.php'">
            <i class="fas fa-dollar-sign"></i><span>Finance & Payments</span>
          </li>
          <li onclick="window.location.href='<?= $baseURL ?>recovery_management/index.php'">
            <i class="fas fa-undo"></i><span>Recovery Management</span>
          </li>
        </ul>
      </div>

      <?php if ($_SESSION['user_id'] === 'admin') { ?>
      <div class="nav-section">
        <h4>USER MANAGEMENT</h4>
        <ul>
          <li onclick="window.location.href='<?= $baseURL ?>authentication/user_management.php'">
            <i class="fas fa-user"></i><span>User Management</span>
          </li>
        </ul>
      </div>
      <?php } ?>

      <div class="nav-section">
        <h4>ANALYTICS</h4>
        <ul>
          <li data-href="setting/index.php">
            <i class="fas fa-cog"></i><span>System Settings</span>
          </li>
        </ul>
      </div>

      <div class="nav-section">
        <h4>LOGOUT</h4>
        <ul>
          <li onclick="window.location.href='<?= $baseURL ?>authentication/logout.php'">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
          </li>
        </ul>
      </div>
    </nav>
  </div>

  <div class="user-info">
    <div class="user-avatar">D</div>
    <div class="user-details">
      <h4>Devakumar Sharon</h4>
      <p>Business Owner</p>
    </div>
  </div>
</aside>

<script>
// Sidebar navigation helper:
// Build an application-root-aware URL at runtime so links work regardless of the include depth.
document.addEventListener('DOMContentLoaded', function () {
  try {
    const appFolder = 'ImpoExpo_System'; // project folder name (adjust if your deployment differs)
    const path = location.pathname;
    let appRoot = '/';

    const m = path.match(new RegExp('(.*/' + appFolder + '/)'));
    if (m && m[1]) {
      appRoot = m[1];
    } else {
      const parts = path.split('/').filter(Boolean);
      if (parts.length >= 2) appRoot = '/' + parts[0] + '/' + parts[1] + '/';
      else if (parts.length === 1) appRoot = '/' + parts[0] + '/';
    }

    document.querySelectorAll('.nav-menu li[data-href]').forEach(el => {
      el.style.cursor = 'pointer';
      el.addEventListener('click', function () {
        const target = el.getAttribute('data-href') || '';
        let url;
        if (target.startsWith('/')) url = location.origin + target;
        else url = location.origin + appRoot + target;
        window.location.href = url;
      });
    });
  } catch (err) {
    // fail silently; navigation will still work if onclicks exist
    console.error('Sidebar navigation helper error', err);
  }
});
</script>

