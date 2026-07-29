<?php
require_once __DIR__ . '/blog-lib.php';
blog_load_config();
session_start();

function blog_admin_password_ok($password) {
    if (defined('BLOG_ADMIN_PASSWORD_HASH') && BLOG_ADMIN_PASSWORD_HASH) {
        return password_verify($password, BLOG_ADMIN_PASSWORD_HASH);
    }
    if (defined('BLOG_ADMIN_PASSWORD') && BLOG_ADMIN_PASSWORD) {
        return hash_equals(BLOG_ADMIN_PASSWORD, $password);
    }
    return false;
}

function blog_admin_configured() {
    return (defined('BLOG_ADMIN_PASSWORD_HASH') && BLOG_ADMIN_PASSWORD_HASH) || (defined('BLOG_ADMIN_PASSWORD') && BLOG_ADMIN_PASSWORD);
}

function blog_admin_csrf() {
    if (empty($_SESSION['blog_csrf'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['blog_csrf'] = bin2hex(random_bytes(24));
        } else {
            $_SESSION['blog_csrf'] = bin2hex(openssl_random_pseudo_bytes(24));
        }
    }
    return $_SESSION['blog_csrf'];
}

function blog_admin_check_csrf() {
    return isset($_POST['csrf']) && hash_equals(blog_admin_csrf(), $_POST['csrf']);
}

$message = '';
$error = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['blog_admin']);
    header('Location: blog-admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (blog_admin_password_ok(isset($_POST['password']) ? $_POST['password'] : '')) {
        $_SESSION['blog_admin'] = true;
        header('Location: blog-admin.php');
        exit;
    }
    $error = 'Invalid password.';
}

$loggedIn = !empty($_SESSION['blog_admin']);

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    if (!blog_admin_check_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        $posts = blog_posts();
        $currentSlug = isset($_POST['current_slug']) ? preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['current_slug'])) : '';
        $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
        $content = trim(isset($_POST['content']) ? $_POST['content'] : '');
        if (!$title || !$content) {
            $error = 'Title and content are required.';
        } else {
            $slug = $currentSlug ? $currentSlug : blog_unique_slug($title, '');
            $post = array(
                'slug' => $slug,
                'title' => $title,
                'excerpt' => trim(isset($_POST['excerpt']) ? $_POST['excerpt'] : ''),
                'image' => trim(isset($_POST['image']) ? $_POST['image'] : ''),
                'content' => $content,
                'status' => isset($_POST['status']) && $_POST['status'] === 'draft' ? 'draft' : 'published',
                'published_at' => trim(isset($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d')),
                'updated_at' => date('c'),
            );
            $updated = false;
            foreach ($posts as $index => $existing) {
                if (isset($existing['slug']) && $existing['slug'] === $slug) {
                    if (isset($existing['created_at'])) {
                        $post['created_at'] = $existing['created_at'];
                    }
                    $posts[$index] = $post;
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $post['created_at'] = date('c');
                $posts[] = $post;
            }
            blog_save_posts($posts);
            $message = 'Blog post saved.';
        }
    }
}

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (!blog_admin_check_csrf()) {
        $error = 'Session expired. Please try again.';
    } else {
        $slug = isset($_POST['slug']) ? preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['slug'])) : '';
        $posts = array_values(array_filter(blog_posts(), function ($post) use ($slug) {
            return !isset($post['slug']) || $post['slug'] !== $slug;
        }));
        blog_save_posts($posts);
        $message = 'Blog post deleted.';
    }
}

$editSlug = isset($_GET['edit']) ? preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['edit'])) : '';
$editPost = $editSlug ? blog_find_post($editSlug, false) : null;
$posts = $loggedIn ? blog_posts() : array();
$csrf = blog_admin_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blog Admin | Upper Crest Homestay</title>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="css/style.css?v=20260724-blog">
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-N48QGRBB');</script>
  <!-- End Google Tag Manager -->
</head>
<body class="blog-admin-page">
  <main class="blog-admin-wrap">
    <div class="blog-admin-shell">
      <div class="blog-admin-head">
        <div>
          <div class="eyebrow">Upper Crest Homestay</div>
          <h1>Blog Admin</h1>
        </div>
        <?php if ($loggedIn): ?><a class="btn btn-light" href="blog-admin.php?logout=1">Logout</a><?php endif; ?>
      </div>

      <?php if (!blog_admin_configured()): ?>
        <div class="blog-admin-alert error">
          Blog admin is not configured. Copy <strong>blog-admin-config.example.php</strong> to <strong>blog-admin-config.php</strong> and set a strong password.
        </div>
      <?php elseif (!$loggedIn): ?>
        <form class="blog-admin-card blog-login" method="post">
          <h2>Sign In</h2>
          <?php if ($error): ?><div class="blog-admin-alert error"><?php echo blog_e($error); ?></div><?php endif; ?>
          <label>Password</label>
          <input type="password" name="password" required autofocus>
          <button class="btn btn-primary" type="submit" name="login" value="1">Open Dashboard</button>
        </form>
      <?php else: ?>
        <?php if ($message): ?><div class="blog-admin-alert success"><?php echo blog_e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="blog-admin-alert error"><?php echo blog_e($error); ?></div><?php endif; ?>

        <div class="blog-admin-grid">
          <form class="blog-admin-card blog-editor" method="post">
            <input type="hidden" name="csrf" value="<?php echo blog_e($csrf); ?>">
            <input type="hidden" name="current_slug" value="<?php echo blog_e($editPost ? $editPost['slug'] : ''); ?>">
            <h2><?php echo $editPost ? 'Edit Blog Post' : 'Create Blog Post'; ?></h2>
            <label>Title</label>
            <input type="text" name="title" value="<?php echo blog_e($editPost ? $editPost['title'] : ''); ?>" required>
            <label>Short Excerpt</label>
            <textarea name="excerpt" rows="3"><?php echo blog_e($editPost ? $editPost['excerpt'] : ''); ?></textarea>
            <label>Image URL</label>
            <input type="url" name="image" placeholder="https://theuppercrest.in/images/..." value="<?php echo blog_e($editPost ? $editPost['image'] : ''); ?>">
            <div class="blog-admin-row">
              <div>
                <label>Status</label>
                <select name="status">
                  <option value="published" <?php echo !$editPost || $editPost['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                  <option value="draft" <?php echo $editPost && $editPost['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
              </div>
              <div>
                <label>Publish Date</label>
                <input type="date" name="published_at" value="<?php echo blog_e($editPost ? $editPost['published_at'] : date('Y-m-d')); ?>">
              </div>
            </div>
            <label>Content</label>
            <textarea name="content" rows="16" required><?php echo blog_e($editPost ? $editPost['content'] : ''); ?></textarea>
            <div class="blog-admin-actions">
              <button class="btn btn-primary" type="submit" name="save_post" value="1">Save Post</button>
              <?php if ($editPost): ?><a class="btn btn-light" href="blog-admin.php">New Post</a><?php endif; ?>
            </div>
          </form>

          <aside class="blog-admin-card blog-post-list">
            <h2>Posts</h2>
            <?php if (!$posts): ?>
              <p class="blog-muted">No posts yet.</p>
            <?php else: ?>
              <?php foreach ($posts as $post): ?>
                <div class="blog-admin-post">
                  <div>
                    <strong><?php echo blog_e($post['title']); ?></strong>
                    <span><?php echo blog_e($post['status']); ?> · <?php echo blog_e(blog_format_date($post['published_at'])); ?></span>
                  </div>
                  <div class="blog-admin-post-actions">
                    <a href="blog-admin.php?edit=<?php echo urlencode($post['slug']); ?>">Edit</a>
                    <?php if ($post['status'] === 'published'): ?><a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" target="_blank" rel="noopener">View</a><?php endif; ?>
                    <form method="post" onsubmit="return confirm('Delete this post?');">
                      <input type="hidden" name="csrf" value="<?php echo blog_e($csrf); ?>">
                      <input type="hidden" name="slug" value="<?php echo blog_e($post['slug']); ?>">
                      <button type="submit" name="delete_post" value="1">Delete</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </aside>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
