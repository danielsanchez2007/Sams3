<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FabricanteController extends Controller
{
    public function complete(Request $request)
    {
        $this->assertCanViewModule('fabricantes');
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $perPageRaw = (string) $request->query('per_page', '15');

        $perPageOptions = ['10', '15', '30', '50', '100', 'all'];
        $perPage = in_array($perPageRaw, $perPageOptions, true) ? $perPageRaw : '15';

        $query = Fabricante::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('contacto', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($status === 'active') {
            $query->where('activo', true);
        } elseif ($status === 'inactive') {
            $query->where('activo', false);
        }

        $query->orderBy('id', 'desc');

        if ($perPage === 'all') {
            $items = $query->get();
            $fabricantes = new LengthAwarePaginator(
                $items,
                $items->count(),
                $items->count() > 0 ? $items->count() : 1,
                1,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $fabricantes = $query->paginate((int) $perPage)->appends($request->query());
        }

        $stats = [
            'total' => Fabricante::count(),
            'active' => Fabricante::where('activo', true)->count(),
            'with_contact' => Fabricante::whereNotNull('email')->count(),
        ];

        return view('admin.fabricantes.complete', compact('fabricantes', 'stats', 'q', 'status', 'perPage'));
    }

    public function index()
    {
        $this->assertCanViewModule('fabricantes');
        $fabricantes = Fabricante::all();
        return view('admin.fabricantes.index', compact('fabricantes'));
    }

    public function create()
    {
        $this->assertCanEditModule('fabricantes');
        return view('admin.fabricantes.create');
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('fabricantes');
        $request->validate([
            'name' => 'required|string|max:255|unique:fabricantes,name',
            'description' => 'nullable|string',
            'contacto' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email'
        ]);

        $fabricante = Fabricante::create([
            'name' => $request->name,
            'description' => $request->description,
            'contacto' => $request->contacto,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'activo' => true
        ]);

        return redirect()->route('fabricantes.complete')->with('success', '✅ Fabricante creado exitosamente');
    }

    public function edit(Fabricante $fabricante)
    {
        $this->assertCanViewModule('fabricantes');
        return response()->json($fabricante->only([
            'id',
            'name',
            'description',
            'contacto',
            'telefono',
            'email',
            'activo'
        ]));
    }

    public function update(Request $request, Fabricante $fabricante)
    {
        $this->assertCanEditModule('fabricantes');
        $request->validate([
            'name' => 'required|string|max:255|unique:fabricantes,name,' . $fabricante->id,
            'description' => 'nullable|string',
            'contacto' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email'
        ]);

        $fabricante->update([
            'name' => $request->name,
            'description' => $request->description,
            'contacto' => $request->contacto,
            'telefono' => $request->telefono,
            'email' => $request->email
        ]);

        return redirect()->route('fabricantes.complete')->with('success', '✅ Fabricante actualizado exitosamente');
    }

    public function destroy(Fabricante $fabricante)
    {
        $this->assertCanEditModule('fabricantes');
        try {
            $fabricante->delete();
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el fabricante (puede tener equipos asociados).',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fabricante eliminado exitosamente.'
        ]);
    }

    public function toggleStatus(Fabricante $fabricante)
    {
        $this->assertCanEditModule('fabricantes');
        $fabricante->update(['activo' => !$fabricante->activo]);
        
        $status = $fabricante->activo ? 'activado' : 'desactivado';
        return response()->json([
            'success' => true,
            'message' => "✅ Fabricante {$status} exitosamente."
        ]);
    }
}
