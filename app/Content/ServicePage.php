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

        // 1. Hero
        $sections[] = B::section([B::hero($group, $s['hero']['title'], $s['hero']['highlight'] ?? '', $s['hero']['text'], [
            B::btn('Request a quote', $quote),
            B::btn('How it works', '#process', 'ghost', 'fa-solid fa-arrow-down'),
        ], $s['image'] ?? '', 'split')], B::dark(['anchor' => 'top']));

        // 2. Overview + who it is for
        $sections[] = B::section([
            [55, [B::heading($s['overview']['title'], 'Overview'), B::text($s['overview']['html'], 'large')]],
            [45, [B::heading('Who this is for', '', 'm', 'h3'), B::list($s['audience'])], ['box' => 'card', 'pad' => 34, 'anim' => 'fade-left']],
        ], B::light(['valign' => 'start']));

        // 3. Problems
        $sections[] = B::section([
            B::heading($s['problems_title'] ?? 'Problems this solves', 'The challenge', 'xl', 'h2', ['text_align' => 'center']),
            B::boxes($s['problems'], count($s['problems']) >= 4 ? '4' : '3'),
        ], B::soft());

        // 4. Benefits + deliverables
        $sections[] = B::section([
            [55, [B::heading('Key benefits', 'Why it pays off'), B::list($s['benefits'])]],
            [45, [B::heading('What you receive', '', 'm', 'h3'), B::list($s['deliverables'], 'arrow')], ['box' => 'glass', 'pad' => 34, 'anim' => 'fade-left']],
        ], B::light(['valign' => 'start']));

        // 5. Features
        $sections[] = B::section([
            B::heading($s['features_title'] ?? 'What is included', 'Features', 'xl', 'h2', ['text_align' => 'center']),
            B::boxes($s['features'], '3', 'card'),
        ], B::light(['pt' => 40]));

        // 6. Use cases
        $sections[] = B::section([
            B::heading('Practical use cases', 'Examples', 'xl', 'h2', ['text_align' => 'center']),
            B::tabs(array_map(fn ($u) => [$u[0], $u[1], $u[2] ?? ''], $s['use_cases'])),
        ], B::soft());

        // 7. Workflow diagram (automation and integration services)
        if (! empty($s['workflow'])) {
            $sections[] = B::section([
                B::heading($s['workflow']['title'], 'How the pieces connect', 'xl', 'h2', ['text_align' => 'center']),
                B::text('<p>'.$s['workflow']['text'].'</p>', 'normal', ['text_align' => 'center']),
                B::flow($s['workflow']['nodes']),
            ], B::dark(['pt' => 100, 'pb' => 100, 'fx' => 'lines']));
        }

        // 8. Tools
        $sections[] = B::section([
            [40, [B::heading('Tools & technologies', 'Stack'), B::text('<p>'.($s['tools_note'] ?? 'We choose tools for each project based on what you already use, your budget and who will maintain the result. Typical choices:').'</p>')]],
            [60, [B::list($s['tools'], 'dot', '2')], ['anim' => 'fade-left']],
        ], B::light(['valign' => 'center']));

        // 9. Process
        $sections[] = B::section([
            B::heading('How the project runs', 'Process', 'xl', 'h2', ['text_align' => 'center']),
            B::steps(array_map(fn ($p) => [$p[0], $p[1]], $s['process']), count($s['process']) > 4 ? 'vertical' : 'horizontal'),
        ], B::soft(['anchor' => 'process']));

        // 10. Requirements + note
        $right = [B::heading($s['note']['title'], '', 'm', 'h3'), B::text($s['note']['html'])];
        $sections[] = B::section([
            [50, [B::heading('What we need from you', 'Before we start'), B::list($s['requirements'])]],
            [50, $right, ['box' => 'card', 'pad' => 34, 'anim' => 'fade-left']],
        ], B::light(['valign' => 'start']));

        // 11. Related work (hidden automatically while there are no matching projects)
        if (! empty($s['project_category'])) {
            $sections[] = B::section([B::w('projects_grid', ['eyebrow' => 'Portfolio', 'heading' => 'Related work', 'count' => 3, 'filter' => false, 'category' => $s['project_category'], 'columns' => '3'], ['anim' => 'fade-up', 'anim_stagger' => 90])], B::light(['pt' => 30]));
        }

        // 12. FAQ
        $sections[] = B::section([
            [38, [B::heading('Questions clients ask', 'FAQ'), B::text('<p>Something else on your mind? <a href="/contact">Send us a message</a> and we will answer it directly.</p>')]],
            [62, [B::faq($s['faq'])], ['anim' => 'fade-left']],
        ], B::soft(['valign' => 'start']));

        // 13. Related services
        $related = array_values(array_filter(array_map(fn ($slug) => $all[$slug] ?? null, $s['related'])));
        if ($related) {
            $sections[] = B::section([
                B::heading('Related services', 'Often combined with', 'l', 'h2', ['text_align' => 'center']),
                B::boxes(array_map(fn ($r) => [$r['icon'], $r['title'], $r['short'], '/services/'.$r['slug'], 'View service'], $related), (string) min(3, count($related)), 'card'),
            ], B::light());
        }

        // 14. CTA
        $sections[] = B::section([B::cta($s['cta']['title'], $s['cta']['text'], [
            B::btn('Request a quote', $quote),
            B::btn('Contact us', '/contact', 'ghost', 'fa-regular fa-envelope'),
        ])], B::light(['pt' => 20, 'pb' => 110]));

        return B::layout(...$sections);
    }
}
