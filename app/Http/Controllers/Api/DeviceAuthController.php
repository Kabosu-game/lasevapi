<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Session mobile sans mot de passe : l'app génère un identifiant d'appareil aléatoire
 * et obtient un jeton Sanctum. Les données (tirages, défis, séances) sont rattachées
 * au compte, et le code de transfert permet de les retrouver sur un nouveau téléphone.
 */
class DeviceAuthController extends Controller
{
    /** POST /auth/device : crée ou retrouve le compte de l'appareil et renvoie un jeton. */
    public function session(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|string|min:16|max:191',
            'name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
        ]);

        $user = User::where('device_id', $data['device_id'])->first();
        if ($user && $user->role === 'admin') {
            // Un compte administrateur ne peut pas être obtenu via un identifiant d'appareil.
            return response()->json(['success' => false, 'message' => 'Compte non autorisé.'], 403);
        }
        if (!$user) {
            $user = User::create([
                'device_id' => $data['device_id'],
                'name' => $data['name'] ?: 'Membre LASÈV',
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'role' => 'user',
            ]);
        } else {
            $user->fill(array_filter([
                'name' => $data['name'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
            ]))->save();
        }

        return response()->json([
            'success' => true,
            'token' => $user->createToken('mobile-device')->plainTextToken,
            'user' => $user->only(['id', 'name']),
        ]);
    }

    /** GET /auth/transfer-code : code à saisir sur le nouveau téléphone. */
    public function transferCode(Request $request)
    {
        $user = $request->user();
        if (!$user->transfer_code) {
            $user->transfer_code = $this->newCode();
            $user->save();
        }
        return response()->json(['success' => true, 'code' => $user->transfer_code]);
    }

    /** POST /auth/transfer : rattache le nouvel appareil au compte correspondant au code. */
    public function transfer(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:16',
            'device_id' => 'required|string|min:16|max:191',
        ]);

        $user = User::where('transfer_code', strtoupper(trim($data['code'])))->where('role', 'user')->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Code de transfert invalide.'], 422);
        }

        // Libère l'identifiant s'il appartenait à un compte vide créé sur le nouveau téléphone.
        User::where('device_id', $data['device_id'])->where('id', '!=', $user->id)
            ->update(['device_id' => 'released-' . Str::uuid()]);

        $user->tokens()->delete(); // l'ancien téléphone est déconnecté
        $user->device_id = $data['device_id'];
        $user->transfer_code = $this->newCode(); // code à usage unique
        $user->save();

        return response()->json([
            'success' => true,
            'token' => $user->createToken('mobile-device')->plainTextToken,
            'user' => $user->only(['id', 'name']),
        ]);
    }

    private function newCode(): string
    {
        do {
            // Sans caractères ambigus (0/O, 1/I).
            $code = '';
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            for ($i = 0; $i < 10; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (User::where('transfer_code', $code)->exists());
        return $code;
    }
}
