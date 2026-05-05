<?php
return [
    // Full public URL of the site — no trailing slash.
    // Used for canonical tags, RSS feed links, and the sitemap.
    'site_url'  => 'https://example.com',

    // URL path prefix for this installation.
    // Leave as '/' if the blog is at the domain root.
    // Set to '/blog/' if installed in a subfolder, and update RewriteBase in .htaccess to match.
    'base_path' => '/',

    'blog_name'   => 'Dispatch',
    'tagline'     => 'A blog. No database required.',
    'short_name'  => 'Dispatch',        // Displayed in the navbar brand
    'author_name' => 'Website Author',
    'footer_text' => '© 2026 Dispatch. Built with PHP and Markdown.',

    // Paths to the privacy and terms pages, relative to base_path.
    // These slugs are also excluded from the auto-generated navigation menu.
    'privacy_policy_link' => 'page/privacy',
    'terms_service_link'  => 'page/terms',

    // Fallback image used on posts that don't define an image in their front matter.
    'default_image' => 'https://images.pexels.com/photos/7035194/pexels-photo-7035194.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',

    // Admin credentials. Change the password via Settings after first login.
    // Default password is 'demo'. The value stored here is always a bcrypt hash —
    // never replace it with a plaintext string.
    'admin_user' => 'admin',
    'admin_pass' => password_hash('demo', PASSWORD_DEFAULT),
];
