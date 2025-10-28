<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

class UserController extends Controller
{
    use ApiResponseTrait;
    public function clients(Request $request)
    {
        $users = User::whereHas('client')->with('client')->paginate(15);
        return $this->paginatedResponse($users->items(), $users, 'Clients récupérés');
    }

    public function admins(Request $request)
    {
        $users = User::whereHas('admin')->with('admin')->paginate(15);
        return $this->paginatedResponse($users->items(), $users, 'Admins récupérés');
    }
}
