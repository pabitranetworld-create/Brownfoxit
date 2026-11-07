<?php

namespace App\Content;

use App\Content\Blocks as B;
use App\Models\Service;

/**
 * Turns the copy of one service (database/content/services/*.php) into a
 * page-builder layout. Every section stays editable in the builder.
 */
class ServicePage
{
    public static function layout(array $s, array $all): array
    {
        B::begin('services/'.$s['slug']);
        $quote = '/start-a-project?service='.$s['slug'];
        $group = Service::GROUPS[$s['group']] ?? 'Services';
        $sections = [];

        // 1. Hero — white page with moving colours and floating shapes
        $sections[] = B::section([B::hero($group, $s['hero']['title'], $s['hero']['highlight'] ?? '', $s['hero']['text'], [
            B::btn('Request a quote', $quote),
            B::btn('How it works', '#process', 'ghost', 'fa-solid fa-arrow-down'),
        ], $s['image'] ?? '', 'split', self::visual($s))], B::top(['anchor' => 'top', 'fx' => self::backdrop($s)]));


        // 3. Overview + who it is for
        $sections[] = B::section([
            [55, [B::heading($s['overview']['title'], 'Overview'), B::text($s['overview']['html'], 'large')]],
            [45, [B::heading('Who this is for', '', 'm', 'h3'), B::list($s['audience'])], ['box' => 'card', 'pad' => 34, 'anim' => 'fade-left']],
        ], B::light(['valign' => 'start', 'fx' => 'aurora']));

        // 4. Problems — colourful numbered cards
        $sections[] = B::section([
            B::heading($s['problems_title'] ?? 'Problems this solves', 'The challenge', 'xl', 'h2', ['text_align' => 'center']),
            B::boxes($s['problems'], count($s['problems']) >= 4 ? '4' : '3', 'pop'),
        ], B::soft(['fx' => 'beams']));

        // 5. Benefits + deliverables
        $sections[] = B::section([
            [55, [B::heading('Key benefits', 'Why it pays off'), B::list($s['benefits'])]],
            [45, [B::heading('What you receive', '', 'm', 'h3'), B::list($s['deliverables'], 'arrow')], ['box' => 'glass', 'pad' => 34, 'anim' => 'fade-left']],
        ], B::light(['valign' => 'start', 'fx' => 'circuit']));

        // 6. Features
        $sections[] = B::section([
            B::heading($s['features_title'] ?? 'What is included', 'Features', 'xl', 'h2', ['text_align' => 'center']),
            B::boxes($s['features'], '3', 'card'),
        ], B::light(['pt' => 40, 'fx' => 'aurora']));

        // 7. Use cases
        $sections[] = B::section([
            B::heading('Practical use cases', 'Examples', 'xl', 'h2', ['text_align' => 'center']),
            B::tabs(array_map(fn ($u) => [$u[0], $u[1], $u[2] ?? ''], $s['use_cases'])),
        ], B::soft(['fx' => 'stars']));

        // 8. Workflow diagram (automation and integration services) on a moving colour band
        if (! empty($s['workflow'])) {
            $sections[] = B::section([
                B::heading($s['workflow']['title'], 'How the pieces connect', 'xl', 'h2', ['text_align' => 'center']),
                B::text('<p>'.$s['workflow']['text'].'</p>', 'normal', ['text_align' => 'center']),
                B::flow($s['workflow']['nodes']),
            ], B::vivid(['fx' => 'lines']));
        }

        // 9. Tools
        $sections[] = B::section([
            [40, [B::heading('Tools & technologies', 'Stack'), B::text('<p>'.($s['tools_note'] ?? 'We choose tools for each project based on what you already use, your budget and who will maintain the result. Typical choices:').'</p>')]],
            [60, [B::list($s['tools'], 'dot', '2')], ['box' => 'card', 'pad' => 34, 'anim' => 'fade-left']],
        ], B::light(['valign' => 'center']));

        // 10. Process
        $sections[] = B::section([
            B::heading('How the project runs', 'Process', 'xl', 'h2', ['text_align' => 'center']),
            B::steps(array_map(fn ($p) => [$p[0], $p[1]], $s['process']), count($s['process']) > 4 ? 'vertical' : 'horizontal'),
        ], B::soft(['anchor' => 'process', 'fx' => 'waves', 'pb' => 150]));

        // 11. Requirements + note
        $right = [B::heading($s['note']['title'], '', 'm', 'h3'), B::text($s['note']['html'])];
        $sections[] = B::section([
            [50, [B::heading('What we need from you', 'Before we start'), B::list($s['requirements'])]],
            [50, $right, ['box' => 'glow', 'pad' => 34, 'anim' => 'fade-left']],
        ], B::light(['valign' => 'start']));

        // 12. Related work (hidden automatically while there are no matching projects)
        if (! empty($s['project_category'])) {
            $sections[] = B::section([B::w('projects_grid', ['eyebrow' => 'Portfolio', 'heading' => 'Related work', 'count' => 3, 'filter' => false, 'category' => $s['project_category'], 'columns' => '3'], ['anim' => 'fade-up', 'anim_stagger' => 90])], B::light(['pt' => 30]));
        }

        // 13. FAQ
        $sections[] = B::section([
            [38, [B::heading('Questions clients ask', 'FAQ'), B::text('<p>Something else on your mind? <a href="/contact">Send us a message</a> and we will answer it directly.</p>')]],
            [62, [B::faq($s['faq'])], ['anim' => 'fade-left']],
        ], B::soft(['valign' => 'start', 'fx' => 'aurora']));

        // 14. Related services
        $related = array_values(array_filter(array_map(fn ($slug) => $all[$slug] ?? null, $s['related'])));
        if ($related) {
            $sections[] = B::section([
                B::heading('Related services', 'Often combined with', 'l', 'h2', ['text_align' => 'center']),
                B::boxes(array_map(fn ($r) => [$r['icon'], $r['title'], $r['short'], '/services/'.$r['slug'], 'View service'], $related), (string) min(3, count($related)), 'card'),
            ], B::light(['fx' => 'hexgrid']));
        }

        // 15. CTA
        $sections[] = B::section([B::cta($s['cta']['title'], $s['cta']['text'], [
            B::btn('Request a quote', $quote),
            B::btn('Contact us', '/contact', 'ghost', 'fa-regular fa-envelope'),
        ])], B::light(['pt' => 20, 'pb' => 110, 'fx' => 'confetti']));

        return B::layout(...$sections);
    }

    /** The animated hero scene that matches the service. */
    public static function visual(array $s): string
    {
        $map = [
            'ai-automation' => 'neural', 'ai-agents' => 'chat', 'ai-integration' => 'neural', 'n8n-automation' => 'workflow', 'automation-consulting' => 'workflow',
            'website-development' => 'browser', 'ecommerce-development' => 'shop', 'wordpress-development' => 'browser', 'landing-pages' => 'rocket', 'website-maintenance' => 'shield',
            'custom-software-development' => 'code', 'web-application-development' => 'code', 'business-management-software' => 'chart', 'api-integrations' => 'workflow', 'saas-mvp-development' => 'rocket',
            'motion-graphics' => 'film', 'video-editing' => 'film', 'explainer-videos' => 'film',
            'graphic-design' => 'design', 'ui-ux-design' => 'design', 'digital-marketing-assets' => 'design', 'data-dashboards' => 'chart',
        ];

        return $map[$s['slug']] ?? \App\PageBuilder\Visuals::guess($s['title'] ?? '', $s['slug']);
    }

    /** Section background that suits the service family. */
    public static function backdrop(array $s): string
    {
        return match (self::visual($s)) {
            'neural', 'chat' => 'matrix',
            'code', 'chart' => 'binary',
            'film', 'design' => 'scan',
            'shield' => 'hexgrid',
            default => 'circuit',
        };
    }
}
