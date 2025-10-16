<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login - Makgrow Impex</title>
  <link rel="stylesheet" href="auth-style.css" />

  <style>
    /* Fullscreen background video */
    .bg-video {
      position: fixed;
      right: 0;
      bottom: 0;
      min-width: 100%;
      min-height: 100%;
      z-index: -1;
      object-fit: cover;
      filter: brightness(85%);
    }

    /* Make sure content stays above video */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      color: white;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .auth-wrapper {
      position: relative;
      z-index: 1;
    }
  </style>
</head>
<body>
  <!-- 🌌 Background Video -->
  <video autoplay muted loop class="bg-video">
    <source src="background.mp4" type="video/mp4" />
    Your browser does not support the video tag.
  </video>

  <main class="auth-wrapper">
    <section class="auth-box">
      <h2 class="auth-title">Login to Makgrow Impex</h2>
      <form action="login_process.php" method="POST" class="auth-form">
        <input type="text" name="username" placeholder="Username" required autofocus />
        <input type="password" name="password" placeholder="Password" required />
        <button type="submit" class="auth-btn">Login</button>
      </form>
    </section>
  </main>
</body>
</html>