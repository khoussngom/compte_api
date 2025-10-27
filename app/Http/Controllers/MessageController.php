<?php

namespace App\Http\Controllers;

use App\Services\MessageServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Traits\Validators\ValidationTrait;

class MessageController extends Controller
{
    use ValidationTrait;
    private MessageServiceInterface $messageService;

    public function __construct(MessageServiceInterface $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Generic send endpoint. Accepts JSON { to, message } and uses the bound service.
     */
    public function send(Request $request)
    {
        $data = $request->all();
        $errors = $this->validateMessagePayload($data);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'errors' => $errors], 400);
        }

        $ok = $this->messageService->sendMessage($data['to'], $data['message']);

        return response()->json(['success' => $ok], $ok ? 200 : 500);
    }
}
