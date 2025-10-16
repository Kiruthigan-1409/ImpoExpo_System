<?php
include '../authentication/auth.php';
include '../delivery_management/db.php';

function flash($type, $msg) {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');

    // ---- Create new admin (i.e., new row in users) ----
    if ($action === 'create') {
        $pass  = trim($_POST['password'] ?? '');
        $pass2 = trim($_POST['password_confirm'] ?? '');

        // Basic validations per schema
        if ($username === '' || strlen($username) > 20) {
            flash('error', 'Username is required and must be 1–20 characters.');
        } elseif (preg_match('/\s/', $username)) {
            flash('error', 'Username must not contain spaces.');
        } elseif ($pass === '' || strlen($pass) > 12) {
            flash('error', 'Password is required and must be 1–12 characters.');
        } elseif ($pass !== $pass2) {
            flash('error', 'Passwords do not match.');
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $pass);
            if ($stmt->execute()) {
                flash('success', "Admin “{$username}” created.");
            } else {
                // Duplicate username (primary key)
                if ($conn->errno == 1062) {
                    flash('error', 'That username already exists.');
                } else {
                    flash('error', 'Could not create admin. Please try again.');
                }
            }
            $stmt->close();
        }
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }

    // Disallow touching super admin in destructive actions
    if ($username === 'admin' && ($action === 'reset' || $action === 'delete')) {
        flash('error', 'The super admin account cannot be modified or deleted.');
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }

    // ---- Reset password ----
    if ($action === 'reset') {
        $newPass = trim($_POST['new_password'] ?? '');
        if ($newPass === '' || strlen($newPass) > 12) {
            flash('error', 'Password is required and must be 1–12 characters.');
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $newPass, $username);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                flash('success', "Password reset for “{$username}”.");
            } else {
                flash('error', 'Could not reset password. Please try again.');
            }
            $stmt->close();
        }
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }

    // ---- Delete user (except super admin) ----
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ? AND username <> 'admin'");
        $stmt->bind_param("s", $username);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash('success', "User “{$username}” deleted.");
        } else {
            flash('error', 'Could not delete user (it may not exist).');
        }
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
}

// Fetch all users (show super admin too, but lock its actions)
$users = [];
$res = $conn->query("SELECT username FROM users ORDER BY username ASC");
while ($row = $res->fetch_assoc()) { $users[] = $row['username']; }
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Management - Makgrow Impex</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../delivery_management/styles.css">
  <link rel="stylesheet" href="styles.css">
  <style>
    .users-card { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06); overflow:hidden; }
    .users-header { padding:14px 16px; border-bottom:1px solid #e5e7eb; display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .search-box { display:flex; align-items:center; gap:8px; background:#f1f5f9; padding:8px 10px; border-radius:10px; }
    .search-box input { border:none; outline:none; background:transparent; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:14px 16px; border-bottom:1px solid #e5e7eb; text-align:left; }
    th { background:#f9fafb; font-size:13px; font-weight:700; color:#374151; }
    tr:hover td { background:#f8fafc; }
    .badge { padding:6px 10px; background:#e9eefb; color:#1e3a8a; border-radius:9999px; font-weight:700; font-size:12px; }
    .badge.super { background:#fde68a; color:#92400e; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; }
    .btn { padding:10px 14px; border:none; border-radius:10px; cursor:pointer; font-weight:700; display:inline-flex; gap:8px; align-items:center; }
    .btn-primary { background:#2d6cdf; color:#fff; }
    .btn-secondary { background:#e9eefb; color:#1e3a8a; }
    .btn-danger { background:#fee2e2; color:#b91c1c; }
    .btn-ghost { background:transparent; color:#2d6cdf; }

    .modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.6); display:none; align-items:center; justify-content:center; padding:20px; z-index:50; }
    .modal-backdrop.show { display:flex; }
    .modal { background:#fff; width:100%; max-width:520px; border-radius:16px; padding:18px; box-shadow:0 20px 60px rgba(0,0,0,.25); }
    .modal header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
    .modal h3 { margin:0; font-size:18px; }
    .modal label { font-size:12px; color:#64748b; display:block; margin-bottom:6px; }
    .modal input { width:100%; padding:10px 12px; border:1px solid #e2e8f0; border-radius:10px; outline:none; }
    .modal .footer { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }

    .toast { position:fixed; bottom:16px; right:16px; z-index:60; padding:12px 14px; border-radius:10px; color:#fff; display:none; }
    .toast.show { display:block; }
    .toast.success { background:#22c55e; }
    .toast.error { background:#ef4444; }
  </style>
</head>
<body>
  <div class="app-container">
    <?php include '../layout/sidebar.php'; ?>

    <main class="main-content">
      <div class="page-header">
        <div class="page-title"><i class="fa-solid fa-users-gear"></i><span>User Management</span></div>
        <div class="toolbar">
          <button class="btn btn-primary" id="openCreate"><i class="fa-solid fa-user-plus"></i> Add Admin</button>
        </div>
      </div>

      <section class="users-card">
        <div class="users-header">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="search" placeholder="Search username...">
          </div>
          <span class="badge"><?php echo count($users); ?> users</span>
        </div>

        <div class="table-wrap">
          <table id="usersTable">
            <thead>
              <tr>
                <th style="width: 40%;">Username</th>
                <th style="width: 20%;">Role</th>
                <th style="width: 40%;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)) { ?>
                <tr><td colspan="3">No users found.</td></tr>
              <?php } else { foreach ($users as $u) {
                $isSuper = ($u === 'admin'); ?>
                <tr>
                  <td class="uname"><?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <?php if ($isSuper) { ?>
                      <span class="badge super">Super Admin</span>
                    <?php } else { ?>
                      <span class="badge">Admin</span>
                    <?php } ?>
                  </td>
                  <td>
                    <div class="actions">
                      <button class="btn btn-secondary btn-reset" data-username="<?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSuper ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-key"></i> Reset Password
                      </button>
                      <form method="POST" onsubmit="return confirmDelete('<?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?>');" style="margin:0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-danger" <?php echo $isSuper ? 'disabled' : ''; ?>>
                          <i class="fa-solid fa-user-xmark"></i> Delete
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php } } ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
  
  <div class="modal-backdrop" id="createBackdrop">
    <div class="modal">
      <header>
        <h3>Create Admin</h3>
        <button class="btn btn-ghost" id="closeCreate"><i class="fa-solid fa-xmark"></i> Close</button>
      </header>
      <form method="POST" id="createForm" autocomplete="off">
        <input type="hidden" name="action" value="create">
        <label>Username (max 20 chars)</label>
        <input type="text" name="username" id="create_username" maxlength="20" required>
        <label style="margin-top:10px;">Password (max 12 chars)</label>
        <input type="password" name="password" id="create_password" maxlength="12" required>
        <label style="margin-top:10px;">Confirm Password</label>
        <input type="password" name="password_confirm" id="create_password_confirm" maxlength="12" required>
        <div class="footer">
          <button type="button" class="btn btn-secondary" id="genCreatePass"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
        </div>
      </form>
    </div>
  </div>
  
  <div class="modal-backdrop" id="modalBackdrop">
    <div class="modal">
      <header>
        <h3>Reset Password</h3>
        <button class="btn btn-ghost" id="closeModal"><i class="fa-solid fa-xmark"></i> Close</button>
      </header>
      <form method="POST" id="resetForm">
        <input type="hidden" name="action" value="reset">
        <input type="hidden" name="username" id="modalUsername">
        <label for="new_password">New Password (max 12 chars)</label>
        <input type="password" name="new_password" id="new_password" maxlength="12" required>
        <div class="footer">
          <button type="button" class="btn btn-secondary" id="genPass"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
        </div>
      </form>
    </div>
  </div>
  
  <?php if ($flash) { ?>
    <div class="toast show <?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>" id="toast">
      <?php echo htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <script>setTimeout(()=>{ const t=document.getElementById('toast'); if(t) t.classList.remove('show'); }, 3000);</script>
  <?php } else { ?>
    <div class="toast" id="toast"></div>
  <?php } ?>

  <script>
    // search
    const search = document.getElementById('search');
    const rows = Array.from(document.querySelectorAll('#usersTable tbody tr'));
    search.addEventListener('input', () => {
      const q = search.value.trim().toLowerCase();
      rows.forEach(r => {
        const name = r.querySelector('.uname').textContent.toLowerCase();
        r.style.display = name.includes(q) ? '' : 'none';
      });
    });
    
    const resetBackdrop = document.getElementById('modalBackdrop');
    const closeModal = document.getElementById('closeModal');
    const resetBtns = document.querySelectorAll('.btn-reset');
    const modalUsername = document.getElementById('modalUsername');

    resetBtns.forEach(btn => {
      if (btn.hasAttribute('disabled')) return;
      btn.addEventListener('click', () => {
        modalUsername.value = btn.dataset.username;
        document.getElementById('new_password').value = '';
        resetBackdrop.classList.add('show');
      });
    });
    closeModal.addEventListener('click', () => resetBackdrop.classList.remove('show'));
    resetBackdrop.addEventListener('click', (e) => { if (e.target === resetBackdrop) resetBackdrop.classList.remove('show'); });

    // generate password
    function genPwd(len=12) {
      const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
      let out = '';
      for (let i=0;i<len;i++) out += chars[Math.floor(Math.random()*chars.length)];
      return out.slice(0,len);
    }
    document.getElementById('genPass').addEventListener('click', () => {
      document.getElementById('new_password').value = genPwd(12);
    });
    
    const createBackdrop = document.getElementById('createBackdrop');
    const openCreate = document.getElementById('openCreate');
    const closeCreate = document.getElementById('closeCreate');
    openCreate.addEventListener('click', () => {
      document.getElementById('create_username').value = '';
      document.getElementById('create_password').value = '';
      document.getElementById('create_password_confirm').value = '';
      createBackdrop.classList.add('show');
    });
    closeCreate.addEventListener('click', () => createBackdrop.classList.remove('show'));
    createBackdrop.addEventListener('click', (e) => { if (e.target === createBackdrop) createBackdrop.classList.remove('show'); });

    document.getElementById('genCreatePass').addEventListener('click', () => {
      const pwd = genPwd(12);
      document.getElementById('create_password').value = pwd;
      document.getElementById('create_password_confirm').value = pwd;
    });
    
    function confirmDelete(u) { return confirm(`Delete user “${u}”? This cannot be undone.`); }
    window.confirmDelete = confirmDelete;
  </script>
</body>
</html>
