<?php

/*
 * Starter drafts of legal pages. They are created UNPUBLISHED with a review notice at the top.
 * They are not legal advice: the owner (and ideally a lawyer) must review, complete the
 * [bracketed] items, remove the notice and publish them in Admin → Pages.
 */

use App\Content\Blocks as B;

$notice = B::w('html', ['code' => '<div style="border:2px dashed #f59e0b;background:#fffbeb;color:#78350f;border-radius:14px;padding:16px 20px;font-size:15px;line-height:1.6"><strong>Draft — requires owner and legal review.</strong> This starter text is not legal advice and is not guaranteed to meet the laws that apply to your business or customers. Review it, complete the items in [brackets], then delete this box and publish the page.</div>', 'css' => '', 'js' => '', 'wrap' => 'normal'], ['anim' => 'none']);

$page = fn (string $html) => B::layout(B::section([$notice, B::text($html, 'normal', ['anim' => 'none', 'mt' => 20])], B::light(['pt' => 50, 'pb' => 110, 'width' => 'narrow'])));

$updated = '<p><em>Last updated: [date]</em></p>';
$who = 'Brown Fox IT (“we”, “us”), powered by Pabitra.net, Noibari, Vadun, Pubail, Gazipur, Bangladesh. Contact: <a href="mailto:hello@pabitra.net">hello@pabitra.net</a>';

return [

    'privacy-policy' => [
        'title' => 'Privacy Policy',
        'seo_description' => 'How Brown Fox IT collects, uses and protects personal information on this website.',
        'layout' => fn () => $page($updated.'
<h2>1. Who we are</h2><p>This policy explains how '.$who.' handles personal information when you visit this website, contact us, request a quote or buy from our store.</p>
<h2>2. Information we collect</h2><ul><li><strong>Information you give us:</strong> your name, email address, phone number, company, project details and anything else you write in our forms or emails.</li><li><strong>Customer and order information</strong> (when the store is active): billing details, order history and download records. Card details are processed by the payment provider; we do not store full card numbers or security codes.</li><li><strong>Technical information:</strong> IP address, browser type, pages visited and similar data collected by our server logs and, if enabled, analytics tools.</li><li><strong>Cookies:</strong> small files needed for the website to work (for example session and security cookies) and, if enabled, analytics cookies. [List the analytics or marketing tools you use.]</li></ul>
<h2>3. How we use information</h2><ul><li>To answer enquiries and prepare proposals.</li><li>To deliver services and products you ordered, including digital downloads.</li><li>To send order and service emails.</li><li>To keep the website secure and prevent spam and abuse.</li><li>To understand how the website is used and improve it (if analytics is enabled).</li></ul><p>[If you send marketing emails, describe how consent is collected and how people can unsubscribe.]</p>
<h2>4. Legal bases</h2><p>Where laws such as the EU/UK GDPR apply, we process information to take steps at your request before a contract, to perform a contract, to meet legal obligations, for our legitimate interests (such as security and improving our services) or with your consent. [Confirm with your adviser.]</p>
<h2>5. Sharing</h2><p>We do not sell personal information. We share it only with service providers who help us run the website and business — for example hosting, email delivery, payment processing and, where used, AI or automation services — and only as needed. [List the main providers.] We may disclose information where required by law.</p>
<h2>6. International transfers</h2><p>We are based in Bangladesh and some of our providers may store data in other countries. Where required, we use appropriate safeguards for such transfers. [Review with your adviser.]</p>
<h2>7. How long we keep information</h2><p>Enquiries are kept for as long as needed to answer them and for a reasonable period afterwards. Order and invoice records are kept as long as required for accounting and tax purposes. [Specify periods.]</p>
<h2>8. Your rights</h2><p>Depending on where you live, you may have the right to access, correct, delete or export your personal information, to object to or restrict certain processing, and to withdraw consent. Contact us at <a href="mailto:hello@pabitra.net">hello@pabitra.net</a>. You may also complain to your local data protection authority.</p>
<h2>9. Security</h2><p>We use reasonable technical and organisational measures to protect information, including encrypted connections (HTTPS), access controls and regular updates. No method of transmission or storage is completely secure.</p>
<h2>10. Children</h2><p>This website is not directed at children and we do not knowingly collect their personal information.</p>
<h2>11. Changes</h2><p>We may update this policy. The date at the top shows the latest version.</p>'),
    ],

    'terms-and-conditions' => [
        'title' => 'Terms and Conditions',
        'seo_description' => 'Terms for using the Brown Fox IT website, our services and our online store.',
        'layout' => fn () => $page($updated.'
<h2>1. About these terms</h2><p>These terms apply to the use of this website and to services and products provided by '.$who.'. Specific projects are also governed by the written proposal or agreement for that project; if there is a conflict, the project agreement applies.</p>
<h2>2. Using the website</h2><p>You may use this website for lawful purposes. You must not attempt to disrupt it, gain unauthorised access, or copy its content for commercial use without permission.</p>
<h2>3. Services</h2><ul><li><strong>Proposals:</strong> scope, deliverables, timeline, price and payment schedule are set out in a written proposal. Work starts after acceptance and any agreed deposit.</li><li><strong>Your responsibilities:</strong> you provide accurate information, timely feedback, access and materials, and confirm you have the rights to materials you supply (text, images, footage, logos, data).</li><li><strong>Changes:</strong> work outside the agreed scope is estimated and approved before it starts.</li><li><strong>Revisions:</strong> the number of revision rounds is defined in the proposal.</li><li><strong>Third-party services:</strong> hosting, domains, plugins, stock assets, AI services and other third-party products are subject to their own terms and fees.</li></ul>
<h2>4. Payments</h2><p>Invoices are payable as stated in the proposal or at checkout. We may pause work if payments are overdue. [Add currency, late-payment and tax terms.]</p>
<h2>5. Ownership and licences</h2><p>After full payment, you own the deliverables created specifically for you, except for third-party components (such as open-source software, fonts, stock assets or plugins), which remain under their own licences. We may show non-confidential work in our portfolio unless you ask us not to. Products bought in our store are licensed as described on the product page.</p>
<h2>6. Store purchases</h2><p>Prices, product descriptions and licences are shown on each product page. Orders are confirmed only after payment is verified. Refunds are handled according to our <a href="/refund-policy">Refund Policy</a> and digital delivery according to our <a href="/digital-product-delivery-policy">Digital Product Delivery Policy</a>.</p>
<h2>7. Warranties and liability</h2><p>We provide our services with reasonable skill and care. Except where the law does not allow it, we are not liable for indirect or consequential losses, and our total liability for a project is limited to the amount paid for that project. [Review with your adviser.]</p>
<h2>8. Confidentiality</h2><p>We keep your confidential information private and use it only for the project. We are happy to sign a separate non-disclosure agreement.</p>
<h2>9. Termination</h2><p>Either party may end a project as described in the project agreement. Work completed up to that point is payable.</p>
<h2>10. Governing law</h2><p>[State the governing law and place of jurisdiction.]</p>
<h2>11. Contact</h2><p>Questions about these terms: <a href="mailto:hello@pabitra.net">hello@pabitra.net</a>.</p>'),
    ],

    'refund-policy' => [
        'title' => 'Refund & Return Policy',
        'seo_description' => 'Refunds and returns for Brown Fox IT services and store products.',
        'layout' => fn () => $page($updated.'
<h2>1. Services</h2><p>For services, deposits and milestone payments cover work that is reserved and carried out for you. If a project is cancelled, work completed up to that point is payable; unused prepaid amounts are refunded or credited as stated in the project agreement. [Confirm your rules.]</p>
<h2>2. Digital products</h2><p>Because digital products can be downloaded immediately, purchases are generally non-refundable once the files have been downloaded, except where the law gives you a right to a refund or where the product is faulty or not as described. If a product does not work as described and we cannot fix it within a reasonable time, we will refund the purchase price. [Review consumer-law requirements, e.g. EU withdrawal rights for digital content.]</p>
<h2>3. Physical products</h2><p>[Only applies if physical products are sold. Describe the return window, condition of items, who pays return shipping, and how refunds are issued.]</p>
<h2>4. How to request a refund</h2><p>Email <a href="mailto:hello@pabitra.net">hello@pabitra.net</a> with your order number and the reason for your request. We aim to reply within [number] business days.</p>
<h2>5. How refunds are paid</h2><p>Approved refunds are made to the original payment method where possible. Processing times depend on the payment provider. When a refund is issued, access to related downloads is revoked.</p>'),
    ],

    'digital-product-delivery-policy' => [
        'title' => 'Digital Product Delivery Policy',
        'seo_description' => 'How digital products from the Brown Fox IT store are delivered and accessed.',
        'layout' => fn () => $page($updated.'
<h2>1. Delivery</h2><p>Digital products are delivered as downloads. After your payment is confirmed by the payment provider, download links appear on the order confirmation page, in your account under “My Downloads” and in the order email.</p>
<h2>2. Download links</h2><ul><li>Download links are personal and linked to your order.</li><li>Links may expire and may have a download limit, as shown on the product page and in your account. [Set your defaults.]</li><li>Please do not share download links; access may be revoked if links are misused.</li></ul>
<h2>3. Pending or failed payments</h2><p>Downloads are only available after payment has been verified. Orders with pending, failed or cancelled payments do not include download access.</p>
<h2>4. Updates</h2><p>When a product receives an update, the latest version is made available in your account where the product licence includes updates. [Describe your update policy.]</p>
<h2>5. Problems with a download</h2><p>If a link does not work or a file is damaged, contact <a href="mailto:hello@pabitra.net">hello@pabitra.net</a> with your order number and we will help.</p>
<h2>6. Refunds and revoked access</h2><p>If an order is refunded or cancelled, download access for that order is removed. See our <a href="/refund-policy">Refund Policy</a>.</p>'),
    ],

    'shipping-policy' => [
        'title' => 'Shipping Policy',
        'seo_description' => 'Shipping of physical products from the Brown Fox IT store.',
        'layout' => fn () => $page($updated.'
<p><strong>Note:</strong> the store currently sells digital products only. Publish this page only if physical products are added.</p>
<h2>1. Where we ship</h2><p>[List the countries or regions you ship to.]</p>
<h2>2. Processing time</h2><p>[How long it takes to prepare an order before it is shipped.]</p>
<h2>3. Shipping methods and costs</h2><p>[Carriers, delivery estimates and how shipping costs are calculated at checkout.]</p>
<h2>4. Customs and duties</h2><p>[Who pays import duties and taxes for international orders.]</p>
<h2>5. Tracking</h2><p>[How customers receive tracking information.]</p>
<h2>6. Lost or damaged parcels</h2><p>[What customers should do and how claims are handled.]</p>'),
    ],
];
