<?php

namespace App\Http\Controllers;

use App\Support\UploadedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Mostrar el perfil del usuario autenticado.
     */
    public function show()
    {
        $user = auth()->user();
        $user->load(['role', 'cargo', 'grupo']);

        return view('admin.profile.show', compact('user'));
    }

    /**
     * Actualizar el perfil (solo campos permitidos; grupo y cargo son solo lectura).
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:hombre,mujer,otro',
            'gender_other' => 'nullable|string|max:255|required_if:gender,otro',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'document_type' => 'required|string|in:CC,CE,TI,PP',
            'document_number' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'has_corporate_email' => 'boolean',
            'corporate_email' => 'nullable|email|required_if:has_corporate_email,1',
            'has_corporate_phone' => 'boolean',
            'corporate_phone' => 'nullable|string|max:20|required_if:has_corporate_phone,1',
            'birth_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'signature_drawn' => 'nullable|string|max:500000',
        ];

        $validated = $request->validate($rules);

        $hasCorporateEmail = $request->boolean('has_corporate_email', false);
        $hasCorporatePhone = $request->boolean('has_corporate_phone', false);

        $user->name = $validated['name'];
        $user->last_name = $validated['last_name'] ?? null;
        $user->gender = $validated['gender'] ?? null;
        $user->gender_other = $validated['gender'] === 'otro' ? ($validated['gender_other'] ?? null) : null;
        $user->email = $validated['email'];
        $user->document_type = $validated['document_type'];
        $user->document_number = $validated['document_number'];
        $user->phone = $validated['phone'] ?? null;
        $user->address = $validated['address'] ?? null;
        $user->has_corporate_email = $hasCorporateEmail;
        $user->corporate_email = $hasCorporateEmail ? ($validated['corporate_email'] ?? null) : null;
        $user->has_corporate_phone = $hasCorporatePhone;
        $user->corporate_phone = $hasCorporatePhone ? ($validated['corporate_phone'] ?? null) : null;
        $user->birth_date = isset($validated['birth_date']) && $validated['birth_date'] ? $validated['birth_date'] : null;

        if ($request->filled('password')) {
            $user->password = $request->password;
            $user->must_change_password = false;
        }

        if ($request->hasFile('photo')) {
            try {
                if ($user->photo) {
                    Storage::disk('public')->delete($user->photo);
                }
                $user->photo = UploadedFileStorage::storePublicImage($request->file('photo'), 'users/photos');
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors([
                    'photo' => 'No se pudo guardar la foto: ' . $e->getMessage(),
                ]);
            }
        }

        if ($request->hasFile('signature')) {
            try {
                if ($user->signature) {
                    Storage::disk('public')->delete($user->signature);
                }
                $user->signature = UploadedFileStorage::storePublicImage($request->file('signature'), 'users/signatures');
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors([
                    'signature' => 'No se pudo guardar la firma: ' . $e->getMessage(),
                ]);
            }
        } elseif ($request->filled('signature_drawn')) {
            $raw = (string) $request->input('signature_drawn');
            if (preg_match('/^data:image\/png;base64,/', $raw) === 1) {
                $b64 = substr($raw, strpos($raw, ',') + 1);
                $bin = base64_decode($b64, true);
                if ($bin !== false && strlen($bin) <= 512000 && str_starts_with($bin, "\x89PNG\r\n\x1a\n")) {
                    if ($user->signature) {
                        Storage::disk('public')->delete($user->signature);
                    }
                    $path = 'users/signatures/sign-' . $user->id . '-' . now()->format('YmdHis') . '.png';
                    Storage::disk('public')->put($path, $bin);
                    $user->signature = $path;
                }
            }
        }

        $user->save();

        return redirect()->route('profile.show')->with('success', 'Perfil actualizado correctamente.');
    }
}
