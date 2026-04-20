<?php

namespace App\Http\Controllers;

use App\Models\Automobilis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutomobiliuController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id') ?? Auth::id();
        \Log::info('AutomobiliuController index', ['userId' => $userId, 'authId' => Auth::id()]);
        $automobiliai = Automobilis::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        \Log::info('AutomobiliuController query result', ['count' => $automobiliai->count(), 'data' => $automobiliai->toArray()]);
        return response()->json(['automobiliai' => $automobiliai->toArray()], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'marke' => 'required|string|max:255',
            'modelis' => 'required|string|max:255',
            'metai' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'spalva' => 'nullable|string|max:100',
            'vin' => 'nullable|string|max:17|unique:automobiliai,vin,NULL,id,user_id,' . Auth::id(),
            'variklis' => 'nullable|string|max:255',
            'kuras' => 'nullable|string|max:100',
            'aprasymas' => 'nullable|string',
            'nuotrauka' => 'nullable|image|max:10240',
        ]);

        $data = $validated;
        if ($request->hasFile('nuotrauka')) {
            $path = $request->file('nuotrauka')->store('automobiliai', 'public');
            $data['nuotrauka'] = '/storage/' . $path;
        }

        $automobilis = Auth::user()->automobiliai()->create($data);
        return response()->json(['automobilis' => $automobilis], 201);
    }

    public function show($id)
    {
        $automobilis = Automobilis::findOrFail($id);
        return response()->json(['automobilis' => $automobilis], 200);
    }

    public function update(Request $request, $id)
    {
        $automobilis = Automobilis::findOrFail($id);
        if ($automobilis->user_id !== Auth::id()) {
            return response()->json(['zinute' => 'Neturite teisių'], 403);
        }

        $validated = $request->validate([
            'marke' => 'sometimes|required|string|max:255',
            'modelis' => 'sometimes|required|string|max:255',
            'metai' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'spalva' => 'nullable|string|max:100',
            'vin' => 'nullable|string|max:17|unique:automobiliai,vin,' . $id . ',id,user_id,' . Auth::id(),
            'variklis' => 'nullable|string|max:255',
            'kuras' => 'nullable|string|max:100',
            'aprasymas' => 'nullable|string',
            'nuotrauka' => 'nullable|image|max:10240',
        ]);

        $data = $validated;
        if ($request->hasFile('nuotrauka')) {
            $path = $request->file('nuotrauka')->store('automobiliai', 'public');
            $data['nuotrauka'] = '/storage/' . $path;
        }

        $automobilis->update($data);
        return response()->json(['automobilis' => $automobilis], 200);
    }

    public function destroy($id)
    {
        $automobilis = Automobilis::findOrFail($id);
        if ($automobilis->user_id !== Auth::id()) {
            return response()->json(['zinute' => 'Neturite teisių'], 403);
        }
        $automobilis->delete();
        return response()->json(null, 204);
    }
}
