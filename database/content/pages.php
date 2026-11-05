<?php

/*
 * Core pages of the Brown Fox IT website (About, Services, Process, FAQ, Contact, …).
 * Used only to create pages that do not exist yet — edits made in Admin → Pages are never overwritten.
 * Facts about the company come from the existing brownfoxit.com site; nothing here is invented.
 */

use App\Content\Blocks as B;

$quote = B::btn('Start a project', '/start-a-project');
$contact = B::btn('Contact us', '/contact', 'ghost', 'fa-regular fa-envelope');

return [

    /* ------------------------------------------------------------------ About */
    'about' => [
        'title' => 'About Brown Fox IT',
        'seo_title' => 'About Brown Fox IT — Web, Software, AI Automation & Video',
        'seo_description' => 'Brown Fox IT, powered by Pabitra.net, helps businesses with websites, custom software, AI automation, design and video — with clear communication and honest advice.',
        'template' => 'full_width',
        'layout' => fn () => B::layout(
            B::section([B::hero('About us', 'Digital work done thoughtfully, from idea to launch', 'from idea to launch', 'Brown Fox IT helps businesses build websites, software, automations, designs and videos that are useful, well made and easy to maintain.', [$quote, B::btn('Our services', '/services', 'ghost')])], B::dark()),

            B::section([
                [55, [
                    B::heading('Who we are', 'Brown Fox IT'),
                    B::text('<p>Brown Fox IT is a digital solutions studio powered by <a href="https://pabitra.net" target="_blank" rel="noopener">Pabitra.net</a>. It was founded by Arjun Kumar (Pabitra) — Pabitra Sarkar — a full stack web developer and digital solutions expert with more than 15 years of experience in web development, design and video.</p><p>We bring strategy, design and technology together: websites that explain your business clearly, software that fits your processes, automations that remove repetitive work, and visual content that makes your brand recognisable.</p><p>We work remotely, so your location and time zone are not a barrier, and we keep communication simple: clear proposals, regular updates and honest answers about what will and will not work.</p>', 'large'),
                ]],
                [45, [B::image('uploads/2026/05/Arjun-Kumar-Pabitra-007.png', 'Arjun Kumar (Pabitra), founder of Brown Fox IT')], ['anim' => 'fade-left']],
            ], B::light(['valign' => 'center'])),

            B::section([
                B::heading('What we focus on', 'Services', 'xl', 'h2', ['text_align' => 'center']),
                B::text('<p>Four main areas, plus design, marketing assets, data and support services around them.</p>', 'normal', ['text_align' => 'center']),
                B::boxes([
                    ['fa-solid fa-robot', 'AI Automation', 'Workflow automation, n8n integrations, AI agents and AI features for existing software.', '/services/ai-automation'],
                    ['fa-solid fa-laptop-code', 'Website Development', 'Business websites, WordPress, e-commerce, landing pages and website care.', '/services/website-development'],
                    ['fa-solid fa-code', 'Software Development', 'Custom software, web applications, business systems, APIs and MVPs.', '/services/custom-software-development'],
                    ['fa-solid fa-film', 'Motion Graphics & Video', 'Motion graphics, video editing, explainer and product demo videos.', '/services/motion-graphics'],
                ], '4'),
                B::w('buttons', ['buttons' => [B::btn('See all services', '/services', 'ghost')], 'size' => 'm'], ['anim' => 'fade-up', 'text_align' => 'center', 'mt' => 30]),
            ], B::soft()),

            B::section([
                B::heading('How we like to work', 'Our approach', 'xl', 'h2', ['text_align' => 'center']),
                B::boxes([
                    ['fa-solid fa-compass', 'Goals before tools', 'We start with what you want to achieve, then choose the technology — not the other way around.'],
                    ['fa-solid fa-comments', 'Clear communication', 'Written proposals, regular progress updates and one place for feedback.'],
                    ['fa-solid fa-scale-balanced', 'Honest advice', 'If something is not worth building, or a cheaper option fits better, we say so.'],
                    ['fa-solid fa-layer-group', 'Small, visible steps', 'You see work early and often, so there are no surprises at the end.'],
                    ['fa-solid fa-shield-halved', 'Quality and security', 'Tested work, sensible security and code that others can maintain.'],
                    ['fa-solid fa-key', 'You own the result', 'Source files, code and accounts belong to you once the project is paid for.'],
                ], '3'),
            ], B::light()),

            B::section([
                B::heading('From first message to launch', 'Process', 'xl', 'h2', ['text_align' => 'center']),
                B::steps([
                    ['Talk', 'You tell us about the project; we ask questions and suggest options.'],
                    ['Proposal', 'You receive scope, timeline and cost in writing.'],
                    ['Build', 'We work in stages and share progress regularly.'],
                    ['Launch & support', 'We launch, hand over and stay available for support.'],
                ]),
                B::w('buttons', ['buttons' => [B::btn('How we work in detail', '/our-process', 'link')], 'size' => 'm'], ['anim' => 'fade-up', 'text_align' => 'center', 'mt' => 20]),
            ], B::soft()),

            B::section([
                B::heading('What clients say', 'Testimonials', 'xl', 'h2', ['text_align' => 'center']),
                B::w('testimonials', ['count' => 6, 'style' => 'slider'], ['anim' => 'fade-up']),
            ], B::light()),

            B::section([B::cta('Let’s talk about your project', 'Tell us what you want to build or improve. We reply with questions, ideas and next steps.', [$quote, $contact])], B::light(['pt' => 20, 'pb' => 110]))
        ),
    ],

    /* --------------------------------------------------------------- Services */
    'services' => [
        'title' => 'Services',
        'seo_title' => 'Services — AI Automation, Websites, Software, Design & Video | Brown Fox IT',
        'seo_description' => 'Explore Brown Fox IT services: AI automation and n8n, website and e-commerce development, custom software and web apps, UI/UX and graphic design, motion graphics and video.',
        'template' => 'full_width',
        'layout' => fn () => B::layout(
            B::section([B::hero('What we do', 'Services for building and growing online', 'growing online', 'From automating repetitive work to launching websites, software and videos — choose a service or tell us the problem and we will recommend the right mix.', [$quote, B::btn('Not sure? Ask us', '/contact', 'ghost', 'fa-regular fa-comment')])], B::dark()),

            B::section([B::heading('AI Automation', 'Automate & integrate', 'l'), B::services('ai')], B::light(['anchor' => 'ai'])),
            B::section([B::heading('Website Development', 'Websites & stores', 'l'), B::services('web')], B::soft(['anchor' => 'web'])),
            B::section([B::heading('Software Development', 'Custom software', 'l'), B::services('software')], B::light(['anchor' => 'software'])),
            B::section([B::heading('Motion Graphics & Video', 'Video & animation', 'l'), B::services('media')], B::soft(['anchor' => 'media'])),
            B::section([
                [50, [B::heading('Design', 'Brand & interface', 'l'), B::services('design', 0, '', '1')]],
                [50, [B::heading('Marketing, data & support', 'Grow & improve', 'l'), B::services('growth', 0, '', '1')]],
            ], B::light(['valign' => 'start', 'anchor' => 'more'])),
            B::section([B::services('none', 0, '', '3', 'More services', 'Also available')], B::light(['pt' => 0, 'pb' => 0])),

            B::section([
                B::heading('Not sure which service you need?', 'Help me choose', 'l', 'h2', ['text_align' => 'center']),
                B::text('<p>Many projects combine several services — a website with automation, or software with a product video. Describe your goal and we will suggest the simplest route to get there.</p>', 'normal', ['text_align' => 'center']),
                B::w('buttons', ['buttons' => [B::btn('Describe your project', '/start-a-project'), B::btn('Read the FAQ', '/faq', 'ghost')], 'size' => 'm'], ['anim' => 'fade-up', 'text_align' => 'center']),
            ], B::soft(['pt' => 80, 'pb' => 80])),

            B::section([B::cta('Ready when you are', 'Share your idea, your deadline and your budget range. We will reply with a clear proposal.', [$quote, $contact])], B::light(['pt' => 60, 'pb' => 110]))
        ),
    ],

    /* ------------------------------------------------------------- Our process */
    'our-process' => [
        'title' => 'Our Process',
        'header_subtitle' => 'How a project with Brown Fox IT runs, from the first message to support after launch.',
        'seo_title' => 'Our Process — How We Work | Brown Fox IT',
        'seo_description' => 'How Brown Fox IT runs projects: discovery, written proposal, design, development in stages, testing, launch, handover and support — with clear communication throughout.',
        'layout' => fn () => B::layout(
            B::section([
                [50, [B::heading('A simple, transparent way of working', 'How we work'), B::text('<p>Good projects are rarely about speed alone. They depend on a shared understanding of the goal, clear decisions and regular feedback. Our process is designed to make all three easy — whether the project is a landing page or a business system.</p><p>The steps below are the same for every project; the depth of each step scales with the size of the work.</p>', 'large')]],
                [50, [B::flow([
                    ['fa-regular fa-comments', 'Discovery', 'Goals & scope', 'orange'],
                    ['fa-solid fa-file-signature', 'Proposal', 'Scope, time, cost', 'purple'],
                    ['fa-solid fa-code', 'Build', 'In stages', 'blue'],
                    ['fa-solid fa-rocket', 'Launch', 'Handover & support', 'green'],
                ])], ['anim' => 'fade-left']],
            ], B::light(['valign' => 'center'])),

            B::section([
                B::heading('Step by step', 'The details', 'xl', 'h2', ['text_align' => 'center']),
                B::steps([
                    ['Discovery', 'We talk about your goals, users, existing tools and constraints. For larger projects this can be a short paid workshop with a written summary.'],
                    ['Proposal', 'You receive a written proposal: scope, deliverables, what is not included, timeline, cost, payment schedule and how changes are handled.'],
                    ['Design', 'Structure, flows and visual direction are agreed before production starts — wireframes, style frames or storyboards depending on the service.'],
                    ['Build in stages', 'Work is delivered in milestones. You can review progress on a staging site, prototype or draft video at each stage.'],
                    ['Testing & review', 'We test on real devices and data, fix issues, and you approve the result before launch.'],
                    ['Launch & handover', 'We launch or deliver final files, transfer accounts and source files, and show your team how to use what we built.'],
                    ['Support', 'We stay available for questions and fixes after launch, and offer maintenance plans for ongoing care.'],
                ], 'vertical'),
            ], B::soft()),

            B::section([
                B::heading('What you can expect from us', 'Principles', 'xl', 'h2', ['text_align' => 'center']),
                B::boxes([
                    ['fa-solid fa-file-contract', 'Everything in writing', 'Scope, changes and approvals are documented, so nothing depends on memory.'],
                    ['fa-solid fa-clock', 'Regular updates', 'You know what was done, what is next and whether anything is blocked.'],
                    ['fa-solid fa-code-compare', 'Change requests handled fairly', 'New ideas are welcome; we estimate their impact before work starts.'],
                    ['fa-solid fa-earth-americas', 'Remote-friendly', 'We work across time zones with calls at agreed times and written updates in between.'],
                    ['fa-solid fa-user-shield', 'Confidentiality', 'Your information stays private; we are happy to sign a reasonable NDA.'],
                    ['fa-solid fa-box-archive', 'Clean handover', 'Documentation, source files and access are handed over properly.'],
                ], '3'),
            ], B::light()),

            B::section([
                [38, [B::heading('Process questions', 'FAQ')]],
                [62, [B::faq([
                    ['How do payments work?', 'Projects are usually split into milestones, with a deposit before work starts and the remaining payments tied to agreed deliverables. The exact schedule is in the proposal.'],
                    ['How do we communicate?', 'By email, video calls and a shared channel or project board for larger projects — whatever suits your team.'],
                    ['What if we want changes during the project?', 'Small adjustments within scope are part of the normal review rounds. Larger changes are estimated first so you can decide.'],
                    ['Can you work with our in-house team?', 'Yes. We can follow your tools and conventions and work alongside your developers, designers or marketers.'],
                ])], ['anim' => 'fade-left']],
            ], B::soft(['valign' => 'start'])),

            B::section([B::cta('Start with a conversation', 'Tell us about your project. The first step is simply understanding what you need.', [$quote, $contact])], B::light(['pt' => 60, 'pb' => 110]))
        ),
    ],

    /* --------------------------------------------------------------------- FAQ */
    'faq' => [
        'title' => 'Frequently Asked Questions',
        'header_subtitle' => 'Answers about working with Brown Fox IT: projects, pricing, communication, ownership and support.',
        'seo_title' => 'FAQ — Working with Brown Fox IT',
        'seo_description' => 'Frequently asked questions about Brown Fox IT: how projects start, pricing and payments, timelines, communication, ownership of work, support and confidentiality.',
        'layout' => fn () => B::layout(
            B::section([
                [35, [B::heading('Getting started', 'FAQ', 'l'), B::text('<p>How projects begin and what we need from you.</p>')]],
                [65, [B::faq([
                    ['How do I start a project?', 'Fill in the <a href="/start-a-project">project form</a> or <a href="/contact">send us a message</a>. Describe your goal, any deadline and your budget range. We reply with questions or a proposal.'],
                    ['Do you work with clients outside Bangladesh?', 'Yes. We work remotely and welcome clients from any country, including the USA and Europe, and plan calls around your time zone.'],
                    ['What information should I prepare?', 'Your goals, examples you like, existing materials (brand files, content, access to current systems) and any deadline. If you are unsure, start with a short description — we will ask the rest.'],
                    ['Can you sign an NDA?', 'Yes, we are happy to sign a reasonable non-disclosure agreement before you share sensitive details.'],
                ])], ['anim' => 'fade-left']],
            ], B::light(['valign' => 'start'])),

            B::section([
                [35, [B::heading('Pricing & payment', '', 'l'), B::text('<p>How costs are set and paid.</p>')]],
                [65, [B::faq([
                    ['How much does a project cost?', 'It depends on scope and complexity, so we quote each project individually after understanding your needs. You receive a written proposal before any work starts.'],
                    ['Do you work with fixed prices or hourly?', 'Clearly defined projects are usually quoted at a fixed price per milestone. Ongoing work, maintenance and open-ended tasks can be billed hourly or as a monthly plan.'],
                    ['How are payments made?', 'Usually a deposit before work begins and the rest tied to milestones. Accepted payment methods are listed in the proposal.'],
                    ['Are there hidden costs?', 'No. Third-party costs such as hosting, domains, paid plugins, stock assets or AI usage are listed separately in the proposal so you know about them up front.'],
                ])], ['anim' => 'fade-left']],
            ], B::soft(['valign' => 'start'])),

            B::section([
                [35, [B::heading('During the project', '', 'l'), B::text('<p>Timelines, communication and changes.</p>')]],
                [65, [B::faq([
                    ['How long will my project take?', 'Timelines depend on scope and how quickly feedback and materials are available. The proposal includes a realistic schedule with milestones.'],
                    ['How will we communicate?', 'Through email and video calls, plus a shared project board or chat channel for larger projects. You always know who to contact.'],
                    ['How many revisions are included?', 'The number of revision rounds is defined in the proposal for each deliverable, so expectations are clear.'],
                    ['What if I need changes that were not planned?', 'We estimate the extra time and cost first; nothing is added without your approval.'],
                ])], ['anim' => 'fade-left']],
            ], B::light(['valign' => 'start'])),

            B::section([
                [35, [B::heading('After launch', '', 'l'), B::text('<p>Ownership, support and maintenance.</p>')]],
                [65, [B::faq([
                    ['Who owns the work?', 'Once the project is paid in full, you own the deliverables created for you — source code, design files and videos as agreed. Third-party components keep their own licences.'],
                    ['Do you offer support after launch?', 'Yes. There is a support period after launch for fixes, and optional maintenance plans for updates, monitoring and improvements.'],
                    ['Can another developer continue the work later?', 'Yes. We document what we build and hand over access and files, so you are never locked in.'],
                    ['Do you offer hosting?', 'We help you choose and set up hosting in your own name, so you stay in control of your website and data.'],
                ])], ['anim' => 'fade-left']],
            ], B::soft(['valign' => 'start'])),

            B::section([B::cta('Still have a question?', 'Ask us directly — we are happy to help, even if you are not sure yet what you need.', [B::btn('Contact us', '/contact'), B::btn('Start a project', '/start-a-project', 'ghost')])], B::light(['pt' => 60, 'pb' => 110]))
        ),
    ],

    /* ---------------------------------------------------- Tools & technologies */
    'tools-and-technologies' => [
        'title' => 'Tools & Technologies',
        'header_subtitle' => 'The platforms, languages and tools we use — chosen per project for what you already have and who will maintain the result.',
        'seo_title' => 'Tools & Technologies | Brown Fox IT',
        'seo_description' => 'Technologies Brown Fox IT works with: WordPress, Laravel and PHP, n8n and AI model APIs, Figma and Adobe Creative Cloud, analytics and hosting platforms.',
        'layout' => fn () => B::layout(
            B::section([
                B::heading('The right tool for the job', 'Our stack', 'xl', 'h2', ['text_align' => 'center']),
                B::text('<p>We do not force one technology onto every project. We prefer proven, well-documented tools that your team or another developer can maintain, and we start from the tools you already use and pay for.</p>', 'large', ['text_align' => 'center']),
            ], B::light(['pb' => 30])),
            B::section([
                [50, [B::heading('Websites & e-commerce', '', 'm', 'h3'), B::list(['WordPress and Elementor', 'WooCommerce and Shopify', 'HTML5, CSS3 and JavaScript', 'Custom PHP / Laravel websites', 'Google Analytics, Tag Manager and Search Console'], 'dot')], ['box' => 'card', 'pad' => 30]],
                [50, [B::heading('Software & integrations', '', 'm', 'h3'), B::list(['PHP and Laravel', 'MySQL / MariaDB and PostgreSQL', 'REST APIs, webhooks and OAuth', 'Git, GitHub and automated deployment', 'Linux servers, cPanel and cloud hosting'], 'dot')], ['box' => 'card', 'pad' => 30]],
            ], B::soft(['pt' => 50, 'valign' => 'start'])),
            B::section([
                [50, [B::heading('Automation & AI', '', 'm', 'h3'), B::list(['n8n (cloud and self-hosted)', 'Make and Zapier for existing setups', 'OpenAI and other language model APIs', 'Embeddings and vector search', 'Google Workspace and Microsoft 365 integrations'], 'dot')], ['box' => 'card', 'pad' => 30]],
                [50, [B::heading('Design, motion & video', '', 'm', 'h3'), B::list(['Figma and FigJam', 'Adobe Illustrator, Photoshop and InDesign', 'Adobe After Effects and Premiere Pro', 'DaVinci Resolve for colour', 'Lottie for lightweight web animation'], 'dot')], ['box' => 'card', 'pad' => 30]],
            ], B::soft(['pt' => 0, 'valign' => 'start'])),
            B::section([B::cta('Already have a stack?', 'We are happy to work with the tools you use today. Tell us what you have and what you need.', [B::btn('Talk to us', '/contact'), B::btn('All services', '/services', 'ghost')])], B::light(['pt' => 80, 'pb' => 110]))
        ),
    ],

    /* ----------------------------------------------------------------- Contact */
    'contact' => [
        'title' => 'Contact',
        'header_subtitle' => 'Questions, ideas or a project in mind? Send a message and we will get back to you.',
        'seo_title' => 'Contact Brown Fox IT',
        'seo_description' => 'Contact Brown Fox IT by email, WhatsApp or the contact form. Tell us about your website, software, automation, design or video project.',
        'layout' => fn () => B::layout(
            B::section([
                [40, [
                    B::heading('Get in touch', 'Contact'),
                    B::text('<p>Use the form or reach us directly. For a new project, the <a href="/start-a-project">project form</a> helps us prepare a better first answer.</p>'),
                    B::boxes([
                        ['fa-regular fa-envelope', 'Email', 'hello@pabitra.net', 'mailto:hello@pabitra.net', 'Write an email'],
                        ['fa-brands fa-whatsapp', 'WhatsApp', '+880 1305 641548', 'https://wa.me/message/TFMPI57DW4PUM1', 'Chat on WhatsApp'],
                        ['fa-solid fa-location-dot', 'Location', 'Noibari, Vadun, Pubail, Gazipur, Bangladesh — working remotely with clients worldwide.'],
                        ['fa-brands fa-linkedin', 'LinkedIn', 'linkedin.com/in/pabitra-sarkar', 'https://www.linkedin.com/in/pabitra-sarkar/', 'View profile'],
                    ], '1', 'plain'),
                ]],
                [60, [B::form('contact', 'Send message')], ['anim' => 'fade-left']],
            ], B::light(['valign' => 'start'])),
            B::section([
                B::heading('Before you write', 'Good to know', 'l', 'h2', ['text_align' => 'center']),
                B::boxes([
                    ['fa-solid fa-briefcase', 'Planning a project?', 'Use the project form to share scope, budget range and timeline.', '/start-a-project', 'Start a project'],
                    ['fa-solid fa-circle-question', 'Common questions', 'Pricing, ownership, revisions and support are covered in the FAQ.', '/faq', 'Read the FAQ'],
                    ['fa-solid fa-route', 'How we work', 'See what happens after your first message.', '/our-process', 'See the process'],
                ], '3'),
            ], B::soft())
        ),
    ],

    /* --------------------------------------------------------- Start a project */
    'start-a-project' => [
        'title' => 'Start a Project',
        'header_subtitle' => 'Tell us what you need. We will reply with questions or a proposal — no obligation.',
        'seo_title' => 'Start a Project / Request a Quote | Brown Fox IT',
        'seo_description' => 'Request a quote from Brown Fox IT for AI automation, websites, software, design or video. Describe your project, budget range and timeline.',
        'layout' => fn () => B::layout(
            B::section([
                [62, [B::form('quote', 'Send project details', 'Project details')]],
                [38, [
                    B::heading('What happens next', '', 'm', 'h3'),
                    B::steps([
                        ['We read your request', 'and check that we are a good fit for the work.'],
                        ['We reply', 'with questions, suggestions, or a call invitation if useful.'],
                        ['You receive a proposal', 'with scope, timeline and cost in writing.'],
                        ['We start', 'once you approve the proposal and the first payment.'],
                    ], 'vertical'),
                    B::text('<p><strong>Prefer to talk first?</strong> Write to <a href="mailto:hello@pabitra.net">hello@pabitra.net</a> or message us on <a href="https://wa.me/message/TFMPI57DW4PUM1" target="_blank" rel="noopener">WhatsApp</a>.</p>'),
                ], ['box' => 'card', 'pad' => 32, 'anim' => 'fade-left']],
            ], B::light(['valign' => 'start'])),
            B::section([
                [38, [B::heading('Before you send', 'FAQ', 'l')]],
                [62, [B::faq([
                    ['I am not sure about the budget. What should I choose?', 'Choose “Not sure yet”. We can suggest options at different levels once we understand the goal.'],
                    ['Is my information kept confidential?', 'Yes. Your details are only used to answer your request. We can sign an NDA before you share sensitive information.'],
                    ['Can I attach files?', 'Add a link to a shared folder or document in the reference field. We will ask for anything else we need.'],
                ])], ['anim' => 'fade-left']],
            ], B::soft(['valign' => 'start']))
        ),
    ],

    /* ---------------------------------------------------------------- 404 page */
    'page-not-found' => [
        'title' => 'Page not found',
        'header_subtitle' => 'The page you were looking for has moved or no longer exists.',
        'seo_title' => 'Page not found',
        'noindex' => true,
        'layout' => fn () => B::layout(
            B::section([
                B::text('<p>Try searching the website, or go to one of the most visited pages below.</p>', 'large', ['text_align' => 'center']),
                B::search(),
                B::divider(30),
                B::boxes([
                    ['fa-solid fa-house', 'Home', 'Start from the beginning.', '/', 'Go home'],
                    ['fa-solid fa-layer-group', 'Services', 'Everything we can help with.', '/services', 'View services'],
                    ['fa-solid fa-briefcase', 'Start a project', 'Tell us what you need.', '/start-a-project', 'Start now'],
                    ['fa-regular fa-envelope', 'Contact', 'Ask us directly.', '/contact', 'Contact us'],
                ], '4'),
            ], B::light(['pt' => 40, 'pb' => 110])),
        ),
    ],
];
