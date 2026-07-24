<?php
require_once __DIR__ . '/blog-lib.php';
$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'])) : '';
$post = $slug ? blog_find_post($slug, true) : null;
if (!$post) {
    http_response_code(404);
}
$title = $post ? $post['title'] : 'Blog Post Not Found';
$description = $post ? blog_excerpt($post) : 'The requested Upper Crest blog post could not be found.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo blog_e($title); ?> | Upper Crest Blog</title>
  <meta name="description" content="<?php echo blog_e($description); ?>" />
  <meta name="robots" content="<?php echo $post ? 'index, follow' : 'noindex, follow'; ?>" />
  <?php if ($post): ?><link rel="canonical" href="https://theuppercrest.in/blog-post.php?slug=<?php echo blog_e($post['slug']); ?>" /><?php endif; ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-7STCW44646"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-7STCW44646');</script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="css/style.css?v=20260724-blog">
</head>
<body>
  <script src="js/components.js"></script>

  <main>
    <section class="blog-post-section">
      <div class="container">
        <?php if (!$post): ?>
          <article class="blog-post">
            <a class="blog-back" href="blog.php"><i class="fa-solid fa-arrow-left"></i> Back to Blog</a>
            <h1>Blog Post Not Found</h1>
            <p>The post you are looking for may have been removed or unpublished.</p>
          </article>
        <?php else: ?>
          <article class="blog-post">
            <a class="blog-back" href="blog.php"><i class="fa-solid fa-arrow-left"></i> Back to Blog</a>
            <div class="blog-date"><?php echo blog_e(blog_format_date($post['published_at'])); ?></div>
            <h1><?php echo blog_e($post['title']); ?></h1>
            <?php if (!empty($post['image'])): ?>
              <img class="blog-hero-img" src="<?php echo blog_e($post['image']); ?>" alt="<?php echo blog_e($post['title']); ?>" loading="lazy">
            <?php endif; ?>
            <div class="blog-content"><?php echo blog_content_html($post['content']); ?></div>
            <div class="blog-post-cta">
              <h2>Planning a stay near Kochi Airport?</h2>
              <p>Message Upper Crest Homestay and we will help you with availability and rates.</p>
              <a class="btn btn-primary" href="https://wa.me/919292025275?text=Hi%2C%20I%20read%20your%20blog%20and%20I%20am%20interested%20in%20booking%20Upper%20Crest%20Homestay." target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" style="margin-right:8px"></i>WhatsApp Us</a>
            </div>
          </article>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="js/main.js" defer></script>
</body>
</html>
