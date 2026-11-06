<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportLog;
use App\Support\WordPressImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin screen for copying blog / portfolio / products from an old WordPress site. */
class ImportController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ImportLog::query()
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('kind'), fn ($q, $k) => $q->where('kind', $k))
            ->latest('id')->paginate(40)->withQueryString();
        $stats = ImportLog::selectRaw('kind, status, count(*) as n')->groupBy('kind', 'status')->get()
            ->groupBy('kind')->map(fn ($g) => $g->pluck('n', 'status'));

        return view('admin.import.index', ['logs' => $logs, 'stats' => $stats, 'sites' => (array) setting('import.sites', ['https://brownfoxit.com', 'https://pabitra.net'])]);
    }

    protected function site(Request $request): string
    {
        $data = $request->validate(['site' => ['required', 'url:http,https', 'max:200']]);
        $host = (string) parse_url($data['site'], PHP_URL_HOST);
        // Never call private / local addresses from the server.
        $ip = gethostbyname($host);
        abort_if($host === '' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && ! app()->environment('local', 'testing'), 422, 'This address is not allowed.');

        return rtrim($data['site'], '/');
    }

    public function discover(Request $request): JsonResponse
    {
        return response()->json((new WordPressImporter($this->site($request)))->discover());
    }

    public function run(Request $request): JsonResponse
    {
        $site = $this->site($request);
        $data = $request->validate([
            'kind' => ['required', 'in:posts,portfolio,products'],
            'page' => ['required', 'integer', 'min:1', 'max:500'],
            'path' => ['nullable', 'string', 'max:120', 'regex:#^[a-z0-9_\-]+/v\d+/[a-z0-9_\-]+$#i'],
        ]);
        @set_time_limit(280);

        return response()->json((new WordPressImporter($site))->batch($data['kind'], (int) $data['page'], $data['path'] ?? null));
    }
}
