<?php
/**
 * Content model for the site and the admin panel.
 *
 * SETTINGS are single values (phone, email, social links…), grouped into admin tabs.
 * COLLECTIONS are repeatable items (team, projects, FAQ…). Each collection gets its own
 * SQLite table (c_<key>) with one TEXT column per field; tables and new columns are
 * created automatically, so adding a field here is all it takes.
 *
 * Field types: text, textarea, email, url, number, image, check, select, multi, lines, tags
 *   lines = one item per line, tags = comma separated.
 */

const ICONS = [
    'target' => 'Target', 'brand' => 'Brand / pen', 'social' => 'Social', 'ads' => 'Ads', 'seo' => 'SEO',
    'video' => 'Video', 'web' => 'Web', 'tv' => 'TV', 'flag' => 'Flag', 'users' => 'Users', 'chart' => 'Chart',
    'health' => 'Health', 'building' => 'Building', 'cart' => 'Cart', 'edu' => 'Education', 'bag' => 'Bag',
    'briefcase' => 'Briefcase', 'food' => 'Food', 'plane' => 'Plane', 'bolt' => 'Bolt', 'layers' => 'Layers',
    'play' => 'Play', 'star' => 'Star',
];

const WORK_CATEGORIES = [
    'integrated' => '360° Integrated', 'branding' => 'Branding', 'digital' => 'Digital & Social',
    'performance' => 'Performance', 'production' => 'Production', 'activation' => 'Activation',
];

const SETTINGS = [
    'contact' => ['label' => 'Contact info', 'fields' => [
        'email'         => ['label' => 'Email', 'type' => 'email'],
        'phone'         => ['label' => 'Phone number', 'type' => 'text', 'help' => 'Jemon: +880 1711-000000'],
        'whatsapp'      => ['label' => 'WhatsApp number', 'type' => 'text', 'help' => 'Country code soho, jemon 8801711000000. Dile site e WhatsApp button dekhabe.'],
        'address'       => ['label' => 'Full address', 'type' => 'textarea'],
        'address_short' => ['label' => 'Short address (menu te)', 'type' => 'text', 'help' => 'Jemon: Dhanmondi, Dhaka'],
        'map_url'       => ['label' => 'Google Maps link', 'type' => 'url'],
        'hours'         => ['label' => 'Office hours', 'type' => 'text'],
        'response_time' => ['label' => 'Reply time', 'type' => 'text'],
    ]],
    'social' => ['label' => 'Social links', 'fields' => [
        'facebook'  => ['label' => 'Facebook', 'type' => 'url'],
        'instagram' => ['label' => 'Instagram', 'type' => 'url'],
        'linkedin'  => ['label' => 'LinkedIn', 'type' => 'url'],
        'youtube'   => ['label' => 'YouTube', 'type' => 'url'],
        'tiktok'    => ['label' => 'TikTok', 'type' => 'url'],
    ]],
    'company' => ['label' => 'Company', 'fields' => [
        'site_name'  => ['label' => 'Brand name', 'type' => 'text'],
        'legal_name' => ['label' => 'Legal name (footer ©)', 'type' => 'text'],
        'tagline'    => ['label' => 'Tagline (footer)', 'type' => 'text'],
        'story_1'    => ['label' => 'About — our story, paragraph 1', 'type' => 'textarea'],
        'story_2'    => ['label' => 'About — our story, paragraph 2', 'type' => 'textarea'],
        'careers_link' => ['label' => '"Join the crew" link', 'type' => 'text', 'help' => 'URL ba mailto:jobs@… — khali thakle Contact page e jabe'],
    ]],
    'home' => ['label' => 'Home hero', 'fields' => [
        'hero_kicker' => ['label' => 'Small text above headline', 'type' => 'text'],
        'hero_line1'  => ['label' => 'Headline — first line', 'type' => 'text'],
        'hero_words'  => ['label' => 'Headline — changing words', 'type' => 'tags', 'help' => 'Comma diye, scroll korle ekta ekta kore ashe'],
        'hero_sub'    => ['label' => 'Sub text', 'type' => 'textarea'],
        'manifesto'   => ['label' => '"Who we are" statement', 'type' => 'textarea', 'help' => '*star er moddhe* lekha highlight hobe'],
    ]],
    'form' => ['label' => 'Contact form', 'fields' => [
        'notify_email'  => ['label' => 'Notun message ashle kon email e jabe', 'type' => 'email', 'help' => 'Khali thakle shudhu admin panel e joma hobe'],
        'budgets'       => ['label' => 'Budget options', 'type' => 'lines', 'help' => 'Proti line e ekta'],
        'success_title' => ['label' => 'Success title', 'type' => 'text'],
        'success_text'  => ['label' => 'Success message', 'type' => 'textarea'],
    ]],
    'seo' => ['label' => 'SEO & tracking', 'fields' => [
        'og_image'      => ['label' => 'Share image (1200×630)', 'type' => 'image'],
        'ga_id'         => ['label' => 'Google Analytics 4 ID', 'type' => 'text', 'help' => 'Jemon G-XXXXXXXXXX'],
        'pixel_id'      => ['label' => 'Meta Pixel ID', 'type' => 'text'],
        'gsc_verify'    => ['label' => 'Google Search Console verification code', 'type' => 'text', 'help' => 'Shudhu content="…" er value ta'],
        'show_notes'    => ['label' => '"Placeholder" note gula dekhabe', 'type' => 'check', 'help' => 'Ashol data dewar por off kore din'],
    ]],
];

const COLLECTIONS = [
    'stats' => ['label' => 'Stats', 'singular' => 'Stat', 'list' => ['value', 'suffix', 'label'], 'fields' => [
        'value'  => ['label' => 'Number', 'type' => 'number', 'required' => true],
        'suffix' => ['label' => 'After number', 'type' => 'text', 'help' => 'Jemon +, %, x'],
        'label'  => ['label' => 'Text', 'type' => 'text', 'required' => true],
    ]],
    'services' => ['label' => 'Services', 'singular' => 'Service', 'list' => ['name', 'slug'], 'fields' => [
        'name'   => ['label' => 'Name', 'type' => 'text', 'required' => true],
        'slug'   => ['label' => 'Page anchor', 'type' => 'text', 'help' => 'Choto hater, space chara — jemon strategy'],
        'icon'   => ['label' => 'Icon', 'type' => 'select', 'options' => ICONS],
        'short'  => ['label' => 'Short text (Home card)', 'type' => 'textarea'],
        'tags'   => ['label' => 'Tags (Home card)', 'type' => 'tags'],
        'lead'   => ['label' => 'Description (Services page)', 'type' => 'textarea'],
        'points' => ['label' => 'What we do', 'type' => 'lines', 'help' => 'Proti line e ekta'],
        'kpi1'   => ['label' => 'Highlight 1 — big text', 'type' => 'text'],
        'kpi1_label' => ['label' => 'Highlight 1 — small text', 'type' => 'text'],
        'kpi2'   => ['label' => 'Highlight 2 — big text', 'type' => 'text'],
        'kpi2_label' => ['label' => 'Highlight 2 — small text', 'type' => 'text'],
        'cta'    => ['label' => 'Button text', 'type' => 'text'],
    ]],
    'projects' => ['label' => 'Portfolio', 'singular' => 'Project', 'list' => ['image', 'title', 'industry', 'kpi', 'featured'], 'fields' => [
        'title'    => ['label' => 'Project name', 'type' => 'text', 'required' => true],
        'client'   => ['label' => 'Client name', 'type' => 'text', 'help' => 'Optional — site e dekhabe na, shudhu apnar reference'],
        'industry' => ['label' => 'Industry', 'type' => 'text'],
        'kind'     => ['label' => 'Type of work (small label)', 'type' => 'text', 'help' => 'Jemon: 360° Launch'],
        'summary'  => ['label' => 'What we did (1 line)', 'type' => 'textarea'],
        'kpi'      => ['label' => 'Result — big number', 'type' => 'text', 'help' => 'Jemon +212%'],
        'kpi_label'=> ['label' => 'Result — what it means', 'type' => 'text'],
        'tags'     => ['label' => 'Tags', 'type' => 'tags'],
        'cats'     => ['label' => 'Filter categories', 'type' => 'multi', 'options' => WORK_CATEGORIES],
        'image'    => ['label' => 'Cover image (landscape)', 'type' => 'image'],
        'theme'    => ['label' => 'Colour style (chobi na thakle)', 'type' => 'select', 'options' => ['1' => 'Lime', '2' => 'Grey', '3' => 'Paper', '4' => 'Dark', '5' => 'Soft lime', '6' => 'White']],
        'video'    => ['label' => 'Video / case link', 'type' => 'url', 'help' => 'Dile card e click korle eta khulbe'],
        'featured' => ['label' => 'Home page e dekhabe (max 5)', 'type' => 'check'],
    ]],
    'team' => ['label' => 'Team', 'singular' => 'Member', 'list' => ['photo', 'role', 'name', 'home'], 'fields' => [
        'name'      => ['label' => 'Name', 'type' => 'text'],
        'role'      => ['label' => 'Designation', 'type' => 'text', 'required' => true],
        'dept'      => ['label' => 'Department', 'type' => 'text'],
        'photo'     => ['label' => 'Photo (3:4)', 'type' => 'image'],
        'initials'  => ['label' => 'Initials (chobi na thakle)', 'type' => 'text'],
        'linkedin'  => ['label' => 'LinkedIn', 'type' => 'url'],
        'instagram' => ['label' => 'Instagram', 'type' => 'url'],
        'home'      => ['label' => 'Home page e dekhabe (max 4)', 'type' => 'check'],
    ]],
    'testimonials' => ['label' => 'Testimonials', 'singular' => 'Testimonial', 'list' => ['name', 'role', 'quote'], 'fields' => [
        'quote'    => ['label' => 'Quote', 'type' => 'textarea', 'required' => true],
        'name'     => ['label' => 'Client name', 'type' => 'text', 'required' => true],
        'role'     => ['label' => 'Designation, company', 'type' => 'text'],
        'photo'    => ['label' => 'Photo / logo (square)', 'type' => 'image'],
        'initials' => ['label' => 'Initials (chobi na thakle)', 'type' => 'text'],
    ]],
    'clients' => ['label' => 'Client logos', 'singular' => 'Client', 'list' => ['logo', 'name'], 'fields' => [
        'name' => ['label' => 'Client name', 'type' => 'text', 'required' => true],
        'logo' => ['label' => 'Logo (PNG transparent)', 'type' => 'image', 'required' => true],
        'url'  => ['label' => 'Website', 'type' => 'url'],
    ]],
    'packages' => ['label' => 'Packages', 'singular' => 'Package', 'list' => ['name', 'price', 'highlight'], 'fields' => [
        'name'      => ['label' => 'Name', 'type' => 'text', 'required' => true],
        'summary'   => ['label' => 'Short description', 'type' => 'text'],
        'price'     => ['label' => 'Price', 'type' => 'text', 'help' => 'Jemon: From ৳50,000/month ba Monthly retainer'],
        'features'  => ['label' => 'Features', 'type' => 'lines', 'help' => 'Proti line e ekta'],
        'button'    => ['label' => 'Button text', 'type' => 'text'],
        'highlight' => ['label' => 'Highlight (most popular)', 'type' => 'check'],
        'badge'     => ['label' => 'Badge text', 'type' => 'text'],
    ]],
    'industries' => ['label' => 'Industries', 'singular' => 'Industry', 'list' => ['name', 'slug'], 'fields' => [
        'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
        'slug' => ['label' => 'Page anchor', 'type' => 'text'],
        'icon' => ['label' => 'Icon', 'type' => 'select', 'options' => ICONS],
        'text' => ['label' => 'Description', 'type' => 'textarea'],
        'tags' => ['label' => 'Tags', 'type' => 'tags'],
    ]],
    'faqs' => ['label' => 'FAQ', 'singular' => 'Question', 'list' => ['question'], 'fields' => [
        'question' => ['label' => 'Question', 'type' => 'text', 'required' => true],
        'answer'   => ['label' => 'Answer', 'type' => 'textarea', 'required' => true],
    ]],
    'process' => ['label' => 'Process steps', 'singular' => 'Step', 'list' => ['title', 'tags'], 'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
        'text'  => ['label' => 'Text', 'type' => 'textarea'],
        'tags'  => ['label' => 'Tags', 'type' => 'tags'],
    ]],
    'values' => ['label' => 'Values (About)', 'singular' => 'Value', 'list' => ['title'], 'fields' => [
        'icon'  => ['label' => 'Icon', 'type' => 'select', 'options' => ICONS],
        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
        'text'  => ['label' => 'Text', 'type' => 'textarea'],
    ]],
    'timeline' => ['label' => 'Journey (About)', 'singular' => 'Milestone', 'list' => ['year', 'text'], 'fields' => [
        'year' => ['label' => 'Year', 'type' => 'text', 'required' => true],
        'text' => ['label' => 'Text', 'type' => 'textarea'],
    ]],
];

/* Contact form error codes → messages */
const FORM_ERRORS = [
    'required' => 'Please fill in all required fields.',
    'email' => 'Please enter a valid email address.',
    'rate' => 'Too many messages from your network. Please email or call us instead.',
];

/* Admin sidebar order */
const ADMIN_MENU = [
    'Content' => ['projects', 'team', 'testimonials', 'clients', 'stats', 'faqs'],
    'Pages'   => ['services', 'packages', 'industries', 'process', 'values', 'timeline'],
];
