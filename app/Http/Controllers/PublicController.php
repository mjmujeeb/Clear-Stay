<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SecurityIncident;
use App\Services\BotDefenseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicController extends Controller
{
    protected BotDefenseService $botDefenseService;

    public function __construct(BotDefenseService $botDefenseService)
    {
        $this->botDefenseService = $botDefenseService;
    }

    public function home()
    {
        $hotels = Hotel::where('is_active', true)->with('roomTypes')->get();
        $featuredRooms = Room::with(['roomType', 'hotel'])->take(6)->get();

        return view('public.home', compact('hotels', 'featuredRooms'));
    }

    public function rooms()
    {
        $roomTypes = RoomType::with(['hotel', 'rooms'])->get();
        return view('public.rooms', compact('roomTypes'));
    }

    public function features()
    {
        return view('public.features');
    }

    public function contact()
    {
        $hotels = Hotel::where('is_active', true)->get();
        return view('public.contact', compact('hotels'));
    }

    /**
     * Submit Contact Form with Bot Defense & Honeypot Validation.
     */
    public function submitContact(Request $request)
    {
        // Bot & Honeypot Check
        $botCheck = $this->botDefenseService->verifyForm($request, false);
        if (!$botCheck['success']) {
            return back()->with('error', $botCheck['message'])->withInput();
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'hotel_id' => 'required|exists:hotels,id',
            'message' => 'required|string|max:1500',
        ]);

        AuditLog::record('public.contact_inquiry', null, null, [
            'name' => $request->name,
            'email' => $request->email,
            'hotel_id' => $request->hotel_id,
        ]);

        SecurityIncident::log('contact.human_verified_inquiry', 'low', [
            'name' => $request->name,
            'email' => $request->email,
        ], $request);

        return back()->with('success', __('clearstay.security.contact_submitted_success'));
    }

    /**
     * SEO sitemap.xml generator.
     */
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => url('/rooms'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => url('/features'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => url('/contact'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => url('/login'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        foreach ($urls as $u) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($u['loc']) . '</loc>';
            $xml .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
            $xml .= '<changefreq>' . $u['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $u['priority'] . '</priority>';
            // hreflang alternatives
            $xml .= '<xhtml:link rel="alternate" hreflang="en" href="' . htmlspecialchars($u['loc']) . '?lang=en"/>';
            $xml .= '<xhtml:link rel="alternate" hreflang="ja" href="' . htmlspecialchars($u['loc']) . '?lang=ja"/>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * robots.txt generator.
     */
    public function robots(): Response
    {
        $content = "User-agent: *\n";
        $content .= "Disallow: /dashboard\n";
        $content .= "Disallow: /board\n";
        $content .= "Disallow: /reservations\n";
        $content .= "Disallow: /housekeeping\n";
        $content .= "Disallow: /analytics\n";
        $content .= "Disallow: /csv-import\n";
        $content .= "Disallow: /security\n";
        $content .= "Disallow: /guest/portal/\n";
        $content .= "Allow: /\n";
        $content .= "Allow: /rooms\n";
        $content .= "Allow: /features\n";
        $content .= "Allow: /contact\n\n";
        $content .= "Sitemap: " . url('/sitemap.xml') . "\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
