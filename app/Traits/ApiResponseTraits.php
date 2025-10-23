<?php
namespace App\Traits;

trait ApiResponseTraits
{

    protected function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => 'success'
        ], $code);
    }

    protected function createdResponse($data, $message = null)
    {
        return $this->successResponse($data, $message, 201);
    }

    protected function errorResponse($code = 500, $message = null)
    {
        return response()->json([
            'message' => $message,
            'status' => 'error'
        ], $code);
    }

    protected function notFoundResponse($message = 'Resource not found')
    {
        return $this->errorResponse(404, $message);
    }

    protected function serverErrorResponse($message = 'Internal Server Error')
    {
        return $this->errorResponse(500, $message);
    }
}
