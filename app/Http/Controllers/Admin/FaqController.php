<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Decoration;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display a listing of FAQs for a decoration.
     */
    public function index($decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);
        $faqs = $decoration->faqs;

        return response()->json([
            'success' => true,
            'data' => $faqs
        ]);
    }

    /**
     * Store a newly created FAQ.
     */
    public function store(Request $request, $decorationId)
    {
        $decoration = Decoration::findOrFail($decorationId);

        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'order' => 'nullable|integer|min:0',
        ]);

        $faq = $decoration->faqs()->create([
            'question' => $request->question,
            'answer' => $request->answer,
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully',
            'data' => $faq
        ], 201);
    }

    /**
     * Display the specified FAQ.
     */
    public function show($decorationId, $id)
    {
        $faq = Faq::where('decoration_id', $decorationId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $faq
        ]);
    }

    /**
     * Update the specified FAQ.
     */
    public function update(Request $request, $decorationId, $id)
    {
        $faq = Faq::where('decoration_id', $decorationId)
            ->findOrFail($id);

        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'order' => 'nullable|integer|min:0',
        ]);

        $faq->update([
            'question' => $request->question,
            'answer' => $request->answer,
            'order' => $request->order ?? $faq->order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faq
        ]);
    }

    /**
     * Remove the specified FAQ.
     */
    public function destroy($decorationId, $id)
    {
        $faq = Faq::where('decoration_id', $decorationId)
            ->findOrFail($id);
            
        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully'
        ]);
    }
}
