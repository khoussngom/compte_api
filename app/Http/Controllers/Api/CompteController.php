<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Traits\ApiResponseTraits;
use App\Http\Controllers\Controller;
class CompteController extends Controller
{
    use ApiResponseTraits;
    protected $compteService;

    public function __construct()
    {
        $this->compteService = new CompteService();
    }

    public function index()
    {
        $comptes = $this->compteService->getAllComptes();
        $message = 'Liste des comptes récupérée avec succès.';

        return $comptes ? $this->successResponse($comptes, $message) : $this->notFoundResponse();
    }

}

