<?php

namespace App\Http\Controllers;

use App\Services\VendorService;
use Illuminate\Contracts\View\View;

class LandingPageController extends Controller
{
    public function __invoke(VendorService $vendorService): View
    {
        return view('landing_page.pages.home', [
            'categories' => $vendorService->getCategories(),
        ]);
    }
}
