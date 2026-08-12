<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            // Si está autenticado, redirigir al panel de administración
            return redirect()->route('admin.dashboard');
        }
        
        // Si no está autenticado, mostrar el dashboard público
        return view('dashboard');
    }
}
