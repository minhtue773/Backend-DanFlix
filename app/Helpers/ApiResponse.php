<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Trả response thành công
     *
     * @param array|object|null $data
     * @param string $message
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = null, string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null
        ], $status);
    }

    /**
     * Trả response lỗi
     *
     * @param array|string|null $errors
     * @param string $message
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($errors = null, string $message = 'Error', int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors
        ], $status);
    }
}