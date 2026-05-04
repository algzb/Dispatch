<?php
return [
    'site_url'  => 'https://example.com',
    'base_path' => '/',  // Change to '/blog/' if installed in a subfolder
    'blog_name' => 'Dispatch',
    'tagline' => 'A blog. No database required.',
    'short_name' => 'Dispatch',
    'author_name' => 'Website Author',
    'footer_text' => '© 2026 Dispatch. Built with PHP and Markdown.',
    'privacy_policy_link' => 'page/privacy',
    'terms_service_link'  => 'page/terms',
    'default_image' => 'https://images.pexels.com/photos/7035194/pexels-photo-7035194.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
    // Demo credentials — change these before deploying to production
    'admin_user' => 'admin',
    'admin_pass' => password_hash('demo', PASSWORD_DEFAULT),
];