<?php

function blog_load_config() {
    $config = __DIR__ . '/blog-admin-config.php';
    if (is_file($config)) {
        require_once $config;
    }
}

function blog_data_file() {
    blog_load_config();
    if (defined('BLOG_DATA_FILE') && BLOG_DATA_FILE) {
        return BLOG_DATA_FILE;
    }
    return __DIR__ . '/data/blog-posts.json';
}

function blog_ensure_data_file() {
    $file = blog_data_file();
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return;
    }
    if (!is_file($file)) {
        @file_put_contents($file, json_encode(array(), JSON_PRETTY_PRINT));
    }
}

function blog_posts() {
    blog_ensure_data_file();
    $file = blog_data_file();
    if (!is_readable($file)) {
        return array();
    }
    $json = file_get_contents($file);
    $posts = json_decode($json, true);
    if (!is_array($posts)) {
        return array();
    }
    usort($posts, function ($a, $b) {
        return strcmp(isset($b['published_at']) ? $b['published_at'] : '', isset($a['published_at']) ? $a['published_at'] : '');
    });
    return $posts;
}

function blog_save_posts($posts) {
    blog_ensure_data_file();
    @file_put_contents(blog_data_file(), json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function blog_public_posts() {
    return array_values(array_filter(blog_posts(), function ($post) {
        return isset($post['status']) && $post['status'] === 'published';
    }));
}

function blog_find_post($slug, $publicOnly) {
    foreach (blog_posts() as $post) {
        if (isset($post['slug']) && $post['slug'] === $slug) {
            if ($publicOnly && (!isset($post['status']) || $post['status'] !== 'published')) {
                return null;
            }
            return $post;
        }
    }
    return null;
}

function blog_slugify($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ? $slug : 'post';
}

function blog_unique_slug($title, $currentSlug) {
    $base = blog_slugify($title);
    $slug = $base;
    $i = 2;
    $used = array();
    foreach (blog_posts() as $post) {
        if (isset($post['slug']) && $post['slug'] !== $currentSlug) {
            $used[$post['slug']] = true;
        }
    }
    while (isset($used[$slug])) {
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

function blog_e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function blog_format_date($date) {
    $time = strtotime($date);
    return $time ? date('d M Y', $time) : '';
}

function blog_excerpt($post) {
    if (!empty($post['excerpt'])) {
        return $post['excerpt'];
    }
    $text = trim(strip_tags(isset($post['content']) ? $post['content'] : ''));
    return strlen($text) > 160 ? substr($text, 0, 157) . '...' : $text;
}

function blog_content_html($content) {
    $content = trim((string) $content);
    $paragraphs = preg_split('/\R{2,}/', $content);
    $html = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph !== '') {
            $html .= '<p>' . nl2br(blog_e($paragraph)) . '</p>';
        }
    }
    return $html;
}
