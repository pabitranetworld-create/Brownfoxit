<?php

namespace App\PageBuilder;

/** Chooses the animated technology scene that fits a page, from words in its title / address. */
class Visuals
{
    protected const RULES = [
        'neural' => '/\b(ai|a\.i|artificial|neural|machine learning|agents?|gpt|llm|chatgpt|intelligen)/i',
        'workflow' => '/(automat|workflow|n8n|integrat|process|zapier|make\.com|pipeline|consult)/i',
        'shop' => '/(shop|store|cart|checkout|product|e-?commerce|order|pricing|buy)/i',
        'film' => '/(video|motion|film|animat|explainer|editing|youtube|reel)/i',
        'design' => '/(design|brand|logo|ui\b|ux\b|graphic|creative|asset)/i',
        'chart' => '/(chart|data|analytic|dashboard|marketing|seo|report|insight|blog|news)/i',
        'code' => '/(software|code|develop|api|saas|mvp|app\b|application|program|management)/i',
        'browser' => '/(website|web\b|wordpress|landing|portfolio|maintenance|hosting|page)/i',
        'shield' => '/(privacy|terms|security|secure|policy|legal|refund|cookie|account|login|password|not found|404)/i',
        'rocket' => '/(start|launch|quote|project|begin|grow)/i',
        'chat' => '/(faq|chat|support|help|question|message|contact)/i',
        'globe' => '/(about|global|world|team|company|who we are|search)/i',
        'chip' => '/(tool|tech|stack|technolog|hardware|server)/i',
    ];

    public static function guess(?string ...$texts): string
    {
        $text = trim(implode(' ', array_filter($texts)));
        foreach (self::RULES as $scene => $re) {
            if (preg_match($re, $text)) {
                return $scene;
            }
        }

        return 'chip';
    }
}
