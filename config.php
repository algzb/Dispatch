<?php
return [
    // Full public URL of the site — no trailing slash.
    // Used for canonical tags, RSS feed links, and the sitemap.
    'site_url'  => 'https://example.com',

    // URL path prefix for this installation.
    // Leave as '/' if the site is at the domain root.
    // Set to '/mysite/' if installed in a subfolder, and update RewriteBase in .htaccess to match.
    'base_path' => '/',

    'blog_name'   => 'Dispatch',
    'tagline'     => 'Your website, no database required.',
    'short_name'  => 'Dispatch',        // Displayed in the navbar brand
    'author_name' => 'Website Author',
    'footer_text' => '© 2026 Dispatch. Built with PHP and Markdown.',

    // Paths to the privacy and terms pages, relative to base_path.
    // These slugs are also excluded from the auto-generated navigation menu.
    'privacy_policy_link' => 'page/privacy',
    'terms_service_link'  => 'page/terms',

    // Fallback image used on posts that don't define an image in their front matter.
    'default_image' => 'https://images.pexels.com/photos/7035194/pexels-photo-7035194.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',

    // Homepage hero banner.
    'hero_active'      => '1',
    'hero_title'       => 'Welcome to Dispatch',
    'hero_description' => 'Fast, simple websites — no database required.',
    'hero_image'       => 'https://images.pexels.com/photos/7035194/pexels-photo-7035194.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1',
    'hero_button_text' => 'Learn more',
    'hero_button_url'  => '',

    // Features section. Set features_active to '' to hide the whole section.
    'features_active'   => '1',
    'features_heading'  => 'A better way to build your online presence.',
    'feature_1_icon'    => 'bi-building',
    'feature_1_title'   => 'Professional',
    'feature_1_text'    => 'A clean, responsive website that represents your business on any device.',
    'feature_2_icon'    => 'bi-speedometer2',
    'feature_2_title'   => 'Fast Setup',
    'feature_2_text'    => 'Get your site live in hours, not weeks. No developer required.',
    'feature_3_icon'    => 'bi-pencil-square',
    'feature_3_title'   => 'Easy to Edit',
    'feature_3_text'    => 'Update content anytime from the browser-based admin panel.',
    'feature_4_icon'    => 'bi-shield-check',
    'feature_4_title'   => 'Secure',
    'feature_4_text'    => 'No database to breach, CSRF protection, and safe file handling.',

    // Testimonial section.
    'testimonial_active' => '1',
    'testimonial_quote'  => 'This website helped our business establish a professional online presence quickly and without any hassle.',
    'testimonial_author' => 'Jane Doe',
    'testimonial_role'   => 'CEO, Example Co.',
    'testimonial_avatar' => '',

    // Articles preview section.
    'articles_active'   => '1',
    'articles_heading'  => 'Latest from us',
    'articles_subtitle' => 'News, updates, and insights from our team.',

    // CTA banner below articles.
    'cta_active'      => '1',
    'cta_title'       => 'Ready to get started?',
    'cta_text'        => 'Get in touch and we will build something great together.',
    'cta_button_text' => 'Contact us',
    'cta_button_url'  => '',

    // Contact page.
    'contact_active'   => '1',
    'contact_title'    => 'Contact Us',
    'contact_subtitle' => 'Fill out the form and we\'ll get back to you as soon as possible.',
    'contact_email'    => '',   // Recipient address for form submissions
    'contact_success'  => 'Thank you! Your message has been sent.',
    'mail_from_name'   => '',   // From name in outgoing emails; falls back to site name
    'recaptcha_site_key'   => '',   // reCAPTCHA v2 site key (public)
    'recaptcha_secret_key' => '',   // reCAPTCHA v2 secret key (keep private)

    // Custom colors. Set colors_active to '1' to apply overrides.
    'colors_active'  => '',
    'color_primary'  => '#0d6efd',   // Bootstrap default blue
    'color_dark'     => '#212529',   // Bootstrap default dark

    // Admin credentials. Change the password via Settings after first login.
    // The shipped default password is the plaintext 'demo'. As soon as you set a
    // new password in Settings it is stored here as a bcrypt hash (starts with $2).
    // The login code accepts either form, so we never call password_hash() on every
    // request (doing so here would run bcrypt on every public page load — a DoS).
    'admin_user' => 'admin',
    'admin_pass' => 'demo',
];
