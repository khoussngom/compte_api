<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Routing\Controller;
use App\Services\MessageServiceInterface;
use App\Traits\Validators\ValidationTrait;

class MessageController extends Controller
{
    use ValidationTrait;
    use ApiResponseTrait;
    private MessageServiceInterface $messageService;

    public function __construct(MessageServiceInterface $messageService)
    {
        $this->messageService = $messageService;
    }


    public function send(Request $request)
    {
        $data = $request->all();
        $errors = $this->validateMessagePayload($data);
        if (!empty($errors)) {
            return $this->errorResponse($errors, 400);
        }

        $ok = $this->messageService->sendMessage($data['to'], $data['message']);

        if (! $ok) {
            return $this->errorResponse('Envoi du message échoué', 500);
        }

        return $this->respondWithData(['sent' => true], 'Message envoyé');
    }
}
