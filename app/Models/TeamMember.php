<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use Sortable;

    protected $fillable = ['name', 'role', 'photo', 'bio', 'links', 'show_on_home', 'sort_order', 'is_active'];

    protected $casts = ['links' => 'array', 'show_on_home' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public const LINK_TYPES = [
        'linkedin' => ['LinkedIn', 'fa-brands fa-linkedin-in'],
        'facebook' => ['Facebook', 'fa-brands fa-facebook-f'],
        'behance' => ['Behance', 'fa-brands fa-behance'],
        'dribbble' => ['Dribbble', 'fa-brands fa-dribbble'],
        'github' => ['GitHub', 'fa-brands fa-github'],
        'x' => ['X (Twitter)', 'fa-brands fa-x-twitter'],
        'instagram' => ['Instagram', 'fa-brands fa-instagram'],
        'youtube' => ['YouTube', 'fa-brands fa-youtube'],
        'website' => ['Website', 'fa-solid fa-globe'],
    ];

    /** Only links with a real address (empty or "#" placeholders are hidden). */
    public function socials(): array
    {
        $out = [];
        foreach ((array) $this->links as $l) {
            $url = trim((string) ($l['url'] ?? ''));
            $type = (string) ($l['type'] ?? 'website');
            if ($url === '' || $url === '#' || ! preg_match('#^https?://#i', $url)) {
                continue;
            }
            [$label, $icon] = self::LINK_TYPES[$type] ?? self::LINK_TYPES['website'];
            $out[] = ['url' => $url, 'label' => $label, 'icon' => $icon];
        }

        return $out;
    }

    public static function forDisplay(bool $homeOnly = false, int $limit = 0)
    {
        return static::query()->where('is_active', true)->when($homeOnly, fn ($q) => $q->where('show_on_home', true))
            ->orderBy('sort_order')->orderBy('id')->when($limit > 0, fn ($q) => $q->take($limit))->get();
    }
}
