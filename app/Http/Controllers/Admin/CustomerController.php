<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $items = User::where('role', User::ROLE_CUSTOMER)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->withCount('orders')->withSum(['orders as spent' => fn ($o) => $o->where('payment_status', 'paid')], 'total')
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.customers.index', ['items' => $items, 'q' => $q]);
    }
}
