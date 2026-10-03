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
        'careers_link' => ['label' => '"Join the crew" link', 'type' => 'text', 'help' => 'URL ba mailto:jobs@… — khali thakle Contact page e jabe'],
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
    'popup' => ['label' => 'Offer bar & popup', 'fields' => [
        'bar_on'       => ['label' => 'Top offer bar dekhabe', 'type' => 'check'],
        'bar_text'     => ['label' => 'Bar text', 'type' => 'text', 'help' => 'Jemon: Free marketing audit — this month only'],
        'bar_link_text'=> ['label' => 'Bar link text', 'type' => 'text', 'help' => 'Jemon: Book now'],
        'bar_link'     => ['label' => 'Bar link', 'type' => 'text', 'help' => 'Jemon: contact ba https://…'],
        'popup_on'     => ['label' => 'Popup dekhabe', 'type' => 'check'],
        'popup_title'  => ['label' => 'Popup title', 'type' => 'text'],
        'popup_text'   => ['label' => 'Popup text', 'type' => 'textarea'],
        'popup_image'  => ['label' => 'Popup image (optional)', 'type' => 'image'],
        'popup_button' => ['label' => 'Button text', 'type' => 'text'],
        'popup_link'   => ['label' => 'Button link', 'type' => 'text', 'help' => 'Jemon: contact'],
        'popup_delay'  => ['label' => 'Koto second por dekhabe', 'type' => 'number'],
        'popup_every'  => ['label' => 'Ekjon visitor ke abar dekhabe', 'type' => 'select', 'options' => ['session' => 'Proti visit e ekbar', 'day' => 'Dine ekbar', 'week' => 'Shoptahe ekbar', 'once' => 'Shudhu ekbar']],
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

/* Collections in the admin sidebar */
const ADMIN_MENU = [
    'Content' => ['projects', 'team', 'testimonials', 'clients', 'stats', 'faqs'],
    'Sections' => ['services', 'packages', 'industries', 'process', 'values', 'timeline'],
];

const LEAD_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'proposal' => 'Proposal sent', 'won' => 'Won', 'lost' => 'Lost'];

/* admin: everything. editor: content, pages, blog, media, messages — no settings, users or backup. */
const ROLES = ['admin' => 'Admin', 'editor' => 'Editor'];

/*
 * Editable text of each page. Stored in settings as "<page>.<field>".
 * heading fields: *word* = green highlight, a new line = line break.
 */
const SEO_FIELDS = ['seo_title' => 'SEO title (Google e je title dekhabe)', 'seo_desc' => 'SEO description (Google e title er niche)'];

const PAGES = [
    'global' => ['label' => 'Shared sections', 'fields' => [
        'stats_title'   => ['heading', 'Stats section heading', "Small team energy.\nBig brand results."],
        'header_button' => ['text', 'Header button', "Let's talk"],
        'footer_title'  => ['heading', 'Footer heading', "Got a brand\nto grow?"],
        'footer_button' => ['text', 'Footer button', 'Start a project'],
    ]],
    'home' => ['label' => 'Home', 'path' => '', 'fields' => [
        'seo_title'     => ['text', SEO_FIELDS['seo_title'], 'Dakpion IMC — 360° Marketing Solutions Agency in Bangladesh'],
        'seo_desc'      => ['textarea', SEO_FIELDS['seo_desc'], 'Dakpion IMC is a 360° marketing agency in Dhaka — strategy, creative, social media, performance ads, SEO, video, media, PR and activation under one roof.'],
        'hero_kicker'   => ['text', 'Hero — small text above headline', 'Dakpion IMC — 360° Marketing'],
        'hero_line1'    => ['text', 'Hero — headline first line', 'We make brands'],
        'hero_words'    => ['tags', 'Hero — changing words (comma diye)', 'loved, seen, shared, talked about, chosen'],
        'hero_sub'      => ['textarea', 'Hero — sub text', 'Strategy, creative, digital, media and activation — one team, one plan, one set of numbers.'],
        'hero_cta'      => ['text', 'Hero — main button', 'Start a project'],
        'hero_cta2'     => ['text', 'Hero — second button', 'Our services'],
        'manifesto'     => ['textarea', '"Who we are" statement (*word* = highlight)', 'We’re Dakpion — a 360° marketing agency from Dhaka. We don’t sell channels. We build brands that show up everywhere your customer looks — on screens, streets, shelves and feeds — with *one idea, one voice and one goal: growth.*'],
        'manifesto_foot'=> ['textarea', '"Who we are" small text', 'Strategists, designers, writers, filmmakers, media planners, developers and activation crews — under one roof, working from one plan.'],
        'services_title'=> ['heading', 'Services heading', "Eight ways\nwe grow *brands.*"],
        'services_lead' => ['textarea', 'Services text', 'Use one, or plug them all together. Every service is built to work as part of a single 360° plan.'],
        'work_title'    => ['heading', 'Selected work heading', "Campaigns that\n*moved* numbers."],
        'process_title' => ['heading', 'Process heading', "Brief to\nresults in five."],
        'process_lead'  => ['textarea', 'Process text', "A clear, accountable process — you always know what's happening, why, and what it's delivering."],
        'team_title'    => ['heading', 'Team heading', "People behind\nthe *360°.*"],
        'quotes_title'  => ['heading', 'Testimonials heading', "Don't take\nour word for it."],
        'blog_title'    => ['heading', 'Latest blog heading', "Fresh from\nthe *blog.*"],
        'faq_title'     => ['heading', 'FAQ heading', "Good\nquestions."],
        'faq_lead'      => ['textarea', 'FAQ text', "Can't find your answer? The first consultation is free."],
        'cta_title'     => ['heading', 'Bottom CTA heading', "Let’s make\nsome *noise.*"],
        'cta_text'      => ['textarea', 'Bottom CTA text', 'Book a free marketing audit. We’ll review your brand, channels and numbers — and show you exactly where the growth is.'],
        'cta_button'    => ['heading', 'Bottom CTA round button', "Start a\nproject"],
    ]],
    'about' => ['label' => 'About', 'path' => 'about', 'fields' => [
        'seo_title'     => ['text', SEO_FIELDS['seo_title'], 'About Us & Team — Dakpion IMC'],
        'seo_desc'      => ['textarea', SEO_FIELDS['seo_desc'], 'Dakpion IMC is a 360° integrated marketing communications agency in Dhaka, Bangladesh. Meet the team behind strategy, creative, digital, media and activation.'],
        'hero_title'    => ['heading', 'Page heading', "Cultivating ideas.\nCrafting *success.*"],
        'hero_lead'     => ['textarea', 'Page intro', 'An integrated marketing communications agency from Dhaka, built on one belief: brands grow fastest when every touchpoint works together.'],
        'story_title'   => ['heading', 'Story heading', "From studio\nto *360°.*"],
        'story_1'       => ['textarea', 'Story — paragraph 1', 'Dakpion started as a small creative and digital studio, and grew by helping healthcare professionals across Bangladesh build trusted brands online. Then our clients asked for more — TV commercials, billboards, events, media buying.'],
        'story_2'       => ['textarea', 'Story — paragraph 2', 'So we built it. Today strategists, designers, writers, filmmakers, media planners, developers and activation crews work under one roof for brands in healthcare, FMCG, real estate, education, e-commerce and more.'],
        'values_title'  => ['heading', 'Values heading', "Four rules\nwe live by."],
        'journey_title' => ['heading', 'Journey heading', "How we\ngot here."],
        'team_title'    => ['heading', 'Team heading', "The crew\nbehind the *360°.*"],
        'team_lead'     => ['textarea', 'Team text', 'Specialists in every discipline — one team, sitting together, working on your brand.'],
        'cta_title'     => ['heading', 'Bottom CTA heading', "Let’s build\nsomething *bold.*"],
        'cta_text'      => ['textarea', 'Bottom CTA text', 'Launch, rebrand or year-round growth — we’d love to hear about it.'],
        'cta_button'    => ['heading', 'Bottom CTA round button', "Start a\nproject"],
    ]],
    'services' => ['label' => 'Services', 'path' => 'services', 'fields' => [
        'seo_title'     => ['text', SEO_FIELDS['seo_title'], 'Services — 360° Marketing Solutions | Dakpion IMC'],
        'seo_desc'      => ['textarea', SEO_FIELDS['seo_desc'], 'Brand strategy, creative, social media, performance marketing, SEO, video production, web, ATL media, PR and BTL activation — integrated marketing services by Dakpion IMC.'],
        'hero_title'    => ['heading', 'Page heading', "Every channel.\nOne *plan.*"],
        'hero_lead'     => ['textarea', 'Page intro', 'Strategy, creative, digital, production, media and activation — eight disciplines working as one team, so your brand speaks with one voice everywhere.'],
        'packages_title'=> ['heading', 'Packages heading', "Start small.\nGo *360°.*"],
        'packages_lead' => ['textarea', 'Packages text', 'Every package is tailored after a free discovery call — these are the most common ways brands start with us.'],
        'cta_title'     => ['heading', 'Bottom CTA heading', "Not sure\nwhere to *start?*"],
        'cta_text'      => ['textarea', 'Bottom CTA text', 'Tell us your goal. We’ll recommend the right channel mix and a realistic plan for your budget — free.'],
        'cta_button'    => ['heading', 'Bottom CTA round button', "Talk to a\nstrategist"],
    ]],
    'industries' => ['label' => 'Industries', 'path' => 'industries', 'fields' => [
        'seo_title'     => ['text', SEO_FIELDS['seo_title'], 'Industries We Serve — Dakpion IMC'],
        'seo_desc'      => ['textarea', SEO_FIELDS['seo_desc'], 'Integrated marketing for healthcare, FMCG, real estate, e-commerce, education, corporate, hospitality and fashion brands in Bangladesh.'],
        'hero_title'    => ['heading', 'Page heading', "Different\nmarkets. One *360°.*"],
        'hero_lead'     => ['textarea', 'Page intro', 'Every industry buys differently. We bring category know-how, proven channel mixes and compliant creative to the sectors we know best.'],
        'cta_title'     => ['heading', 'Bottom CTA heading', "Don’t see\nyour *industry?*"],
        'cta_text'      => ['textarea', 'Bottom CTA text', 'Our 360° approach adapts to any category. Tell us about your business and we’ll show you what’s possible.'],
        'cta_button'    => ['heading', 'Bottom CTA round button', "Start a\nconversation"],
    ]],
    'work' => ['label' => 'Work', 'path' => 'portfolio', 'fields' => [
        'seo_title'     => ['text', SEO_FIELDS['seo_title'], 'Our Work — Case Studies | Dakpion IMC'],
        'seo_desc'      => ['textarea', SEO_FIELDS['seo_desc'], 'Integrated campaigns, brand identities, performance marketing and activations delivered by Dakpion IMC.'],
        'hero_title'    => ['heading', 'Page heading', "Ideas that\n*performed.*"],
        'hero_lead'     => ['textarea', 'Page intro', 'A selection of integrated campaigns, brand builds and performance programs across industries.'],
        'cta_title'     => ['heading', 'Bottom CTA heading', "Want results\nlike *these?*"],
        'cta_text'      => ['textarea', 'Bottom CTA text', 'Share your goals and we’ll put together a tailored 360° plan and proposal.'],
        'cta_button'    => ['heading', 'Bottom CTA round button', "Get a\nproposal"],
    ]],
    'blog' => ['label' => 'Blog', 'path' => 'blog', 'fields' => [
        'seo_title'     => ['text', SEO_FIELDS['seo_title'], 'Blog — Marketing Insights | Dakpion IMC'],
        'seo_desc'      => ['textarea', SEO_FIELDS['seo_desc'], 'Marketing insights, campaign stories and practical playbooks from the Dakpion IMC team in Dhaka.'],
        'hero_title'    => ['heading', 'Page heading', "Ideas worth\n*sharing.*"],
        'hero_lead'     => ['textarea', 'Page intro', 'Marketing insights, campaign stories and practical playbooks from the Dakpion team.'],
        'cta_title'     => ['heading', 'Bottom CTA heading (post er niche)', "Like what\nyou *read?*"],
        'cta_text'      => ['textarea', 'Bottom CTA text', 'Let’s put these ideas to work for your brand. The first consultation is free.'],
        'cta_button'    => ['heading', 'Bottom CTA round button', "Start a\nproject"],
    ]],
    'contact' => ['label' => 'Contact', 'path' => 'contact', 'fields' => [
        'seo_title'     => ['text', SEO_FIELDS['seo_title'], 'Contact — Dakpion IMC'],
        'seo_desc'      => ['textarea', SEO_FIELDS['seo_desc'], 'Get in touch with Dakpion IMC for a free marketing audit and a tailored 360° marketing proposal.'],
        'hero_title'    => ['heading', 'Page heading', "Let’s talk\n*growth.*"],
        'hero_lead'     => ['textarea', 'Page intro', 'Tell us about your brand and goals. A strategist will reply within one business day — with next steps and a free marketing audit.'],
        'form_button'   => ['text', 'Form button', 'Send message'],
    ]],
];

/* Keys that lived in settings before page text existed: old key => new key */
const MOVED_SETTINGS = [
    'hero_kicker' => 'home.hero_kicker', 'hero_line1' => 'home.hero_line1', 'hero_words' => 'home.hero_words',
    'hero_sub' => 'home.hero_sub', 'manifesto' => 'home.manifesto', 'story_1' => 'about.story_1', 'story_2' => 'about.story_2',
];
