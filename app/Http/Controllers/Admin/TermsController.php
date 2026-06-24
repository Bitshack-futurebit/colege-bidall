<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsVersion;
use Illuminate\Http\Request;

class TermsController extends Controller
{
    public function index()
    {
        $terms = TermsVersion::orderByDesc('created_at')->get();

        return view('admin.terms.index', compact('terms'));
    }

    public function create()
    {
        return view('admin.terms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'in:bidder,auctioneer'],
            'content' => ['required', 'string'],
        ]);

        $termsVersion = TermsVersion::create([
            'version' => $validated['version'],
            'title' => $validated['title'],
            'role' => $validated['role'] ?? null,
            'content' => $validated['content'],
            'created_by' => auth()->id(),
        ]);

        if ($request->has('publish')) {
            $termsVersion->update(['published_at' => now()]);
            TermsVersion::clearCache();
        }

        return redirect()->route('admin.terms.index')
            ->with('success', 'Terms version created' . ($request->has('publish') ? ' and published.' : ' as draft.'));
    }

    public function edit(TermsVersion $term)
    {
        return view('admin.terms.edit', compact('term'));
    }

    public function update(Request $request, TermsVersion $term)
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'in:bidder,auctioneer'],
            'content' => ['required', 'string'],
        ]);

        $validated['role'] = $validated['role'] ?? null;

        $term->update($validated);

        if ($request->has('publish') && $term->isDraft()) {
            $term->update(['published_at' => now()]);
            TermsVersion::clearCache();
        }

        return redirect()->route('admin.terms.index')
            ->with('success', 'Terms version updated.');
    }

    public function destroy(TermsVersion $term)
    {
        if ($term->isPublished() && $term->acceptances()->count() > 0) {
            return back()->with('error', 'Cannot delete published terms that have been accepted by users.');
        }

        $term->delete();
        TermsVersion::clearCache();

        return redirect()->route('admin.terms.index')
            ->with('success', 'Terms version deleted.');
    }
}
