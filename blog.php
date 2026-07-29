<?php
require_once __DIR__ . '/blog-lib.php';
$posts = blog_public_posts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blog | Upper Crest Homestay Near Kochi Airport</title>
  <meta name="description" content="Travel tips, homestay updates and local guides from Upper Crest Homestay near Kochi Airport." />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://theuppercrest.in/blog.php" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="css/style.css?v=20260724-blog">
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-N48QGRBB');</script>
  <!-- End Google Tag Manager -->
</head>
<body>
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N48QGRBB" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  <script src="js/components.js"></script>

  <main>
    <div class="page-hero">
      <div class="container">
        <h1>Upper Crest Blog</h1>
        <p>Helpful stay updates, local travel notes and Kochi Airport area guides.</p>
      </div>
    </div>

    <section class="light-section">
      <div class="container">
        <?php if (!$posts): ?>
          <div class="blog-empty">
            <h2>No blog posts yet</h2>
            <p>New Upper Crest updates and travel guides will appear here soon.</p>
          </div>
        <?php else: ?>
          <div class="blog-grid">
            <?php foreach ($posts as $post): ?>
              <article class="blog-card">
                <?php if (!empty($post['image'])): ?>
                  <a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>"><img src="<?php echo blog_e($post['image']); ?>" alt="<?php echo blog_e($post['title']); ?>" loading="lazy"></a>
                <?php endif; ?>
                <div class="blog-card-body">
                  <div class="blog-date"><?php echo blog_e(blog_format_date($post['published_at'])); ?></div>
                  <h2><a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>"><?php echo blog_e($post['title']); ?></a></h2>
                  <p><?php echo blog_e(blog_excerpt($post)); ?></p>
                  <a class="blog-read" href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>">Read More <i class="fa-solid fa-arrow-right"></i></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="js/main.js" defer></script>
</body>
</html>
