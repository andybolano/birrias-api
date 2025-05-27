<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/email/verification-notification",
     *     tags={"Authentication"},
     *     summary="Reenviar notificación de verificación de email",
     *     description="Envía una nueva notificación de verificación de email al usuario autenticado",
     *     security={{"apiAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Notificación de verificación enviada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="verification-link-sent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Email ya verificado - redirección al dashboard",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email already verified")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['status' => 'verification-link-sent']);
    }
}
