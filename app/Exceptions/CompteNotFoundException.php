<?php
namespace App\Exceptions;

use Exception;
use App\Traits\ApiResponseTrait;

use Illuminate\Http\JsonResponse;

class CompteNotFoundException extends Exception
{
    use ApiResponseTrait;

    public function __construct($numeroCompte)
    {
        parent::__construct("Le compte {$numeroCompte} est introuvable");
    }

    public function render(): JsonResponse
    {
        return $this->errorResponse($this->getMessage(), 404, ['code' => 'COMPTE_NOT_FOUND']);
    }
}
