<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccessibilityController extends Controller
{
    /**
     * Universal Accessibility Centre & WCAG 2.2 AA Engine.
     */
    public function index(Request $request)
    {
        $currentTheme = session('clearstay_contrast_theme', auth()->user()?->contrast_theme ?? 'standard');
        $currentFontScale = session('clearstay_font_scale', auth()->user()?->font_scale ?? 100);
        $currentReducedMotion = session('clearstay_reduced_motion', auth()->user()?->reduced_motion ?? false);

        $contrastThemes = [
            [
                'id' => 'standard',
                'name' => 'Standard Professional',
                'description' => 'Balanced dark navy (#0F1D2F) and teal (#287F79) palette with 4.8:1 text contrast.',
                'preview_bg' => '#F8FAFC',
                'preview_text' => '#0F172A',
                'preview_accent' => '#287F79',
            ],
            [
                'id' => 'high_contrast_light',
                'name' => 'High Contrast Light (WCAG 7:1+ AAA)',
                'description' => 'Pure black (#000000) typography on crisp white background with bold high-contrast borders.',
                'preview_bg' => '#FFFFFF',
                'preview_text' => '#000000',
                'preview_accent' => '#004D40',
            ],
            [
                'id' => 'high_contrast_dark',
                'name' => 'High Contrast Dark (Night / Low-Vision)',
                'description' => 'Deep obsidian (#050505) with pure white text and vivid amber (#FBBF24) focus indicators.',
                'preview_bg' => '#050505',
                'preview_text' => '#FFFFFF',
                'preview_accent' => '#FBBF24',
            ],
            [
                'id' => 'monochrome',
                'name' => 'Monochrome & E-Ink Friendly',
                'description' => 'High-clarity grayscale palette eliminating hue reliance for color-independent usability.',
                'preview_bg' => '#F1F5F9',
                'preview_text' => '#111827',
                'preview_accent' => '#374151',
            ],
            [
                'id' => 'colorblind',
                'name' => 'Colourblind-Friendly (Deuteranopia / Protanopia)',
                'description' => 'Replaces red/green indicators with cobalt blue (#2563EB), amber (#D97706), and distinct icon shapes.',
                'preview_bg' => '#F8FAFC',
                'preview_text' => '#0F172A',
                'preview_accent' => '#2563EB',
            ],
        ];

        $wcagChecklist = [
            [
                'principle' => '1. Perceivable',
                'criterion' => 'WCAG 1.4.3 & 1.4.6 (Contrast Minimum & Enhanced)',
                'status' => 'Compliant (AAA Supported)',
                'description' => 'Text has a minimum contrast ratio of 4.5:1 (Standard) and 7:1+ (High Contrast Mode). Text over images is rendered on solid backing.',
            ],
            [
                'principle' => '1. Perceivable',
                'criterion' => 'WCAG 1.4.4 & 1.4.10 (Resize Text & Reflow)',
                'status' => 'Compliant (Up to 300%)',
                'description' => 'Content reflows seamlessly up to 300% zoom without loss of information or functionality, and without requiring 2D scrolling.',
            ],
            [
                'principle' => '2. Operable',
                'criterion' => 'WCAG 2.1.1 & 2.4.1 (Keyboard Navigation & Bypass Blocks)',
                'status' => 'Compliant',
                'description' => 'All functionality accessible via keyboard (Tab, Enter, Space, Escape, Arrows). Skip to Main Content link available on all screens.',
            ],
            [
                'principle' => '2. Operable',
                'criterion' => 'WCAG 2.2.2 & 2.3.3 (Animation & Motion Reduction)',
                'status' => 'Compliant',
                'description' => 'Respects prefers-reduced-motion media query and provides in-app toggle to instantly disable all animated transitions.',
            ],
            [
                'principle' => '2. Operable',
                'criterion' => 'WCAG 2.5.5 & 2.5.8 (Target Size Minimum & Enhanced)',
                'status' => 'Compliant (52px+ in Easy Mode)',
                'description' => 'Interactive controls exceed minimum 24×24px touch bounds in standard mode and 52×52px in Senior / Easy Mode.',
            ],
            [
                'principle' => '3. Understandable',
                'criterion' => 'WCAG 3.2.1 & 3.3.2 (On Focus & Labels / Instructions)',
                'status' => 'Compliant',
                'description' => 'Focusing controls does not trigger unexpected context shifts. Form inputs provide clear visible labels and inline error descriptions.',
            ],
            [
                'principle' => '4. Robust',
                'criterion' => 'WCAG 4.1.2 & 4.1.3 (Name, Role, Value & Status Messages)',
                'status' => 'Compliant',
                'description' => 'Semantic HTML5 structure with ARIA tags (role="dialog", aria-live="polite", aria-expanded) for screen readers.',
            ],
        ];

        return view('accessibility.index', compact(
            'contrastThemes',
            'currentTheme',
            'currentFontScale',
            'currentReducedMotion',
            'wcagChecklist'
        ));
    }

    /**
     * Update user accessibility and contrast preferences.
     */
    public function update(Request $request)
    {
        $request->validate([
            'contrast_theme' => 'required|string|in:standard,high_contrast_light,high_contrast_dark,monochrome,colorblind',
            'font_scale' => 'required|integer|in:100,125,150,200,300',
            'reduced_motion' => 'nullable|boolean',
        ]);

        $theme = $request->input('contrast_theme');
        $fontScale = (int) $request->input('font_scale');
        $reducedMotion = $request->boolean('reduced_motion');

        session([
            'clearstay_contrast_theme' => $theme,
            'clearstay_font_scale' => $fontScale,
            'clearstay_reduced_motion' => $reducedMotion,
        ]);

        if ($user = $request->user()) {
            $user->contrast_theme = $theme;
            $user->font_scale = $fontScale;
            $user->reduced_motion = $reducedMotion;
            $user->save();
        }

        return redirect()->route('accessibility.index')
            ->with('success', 'Accessibility preferences updated successfully. Theme and scaling applied across entire application.');
    }
}
