<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    protected $service;

    public function __construct(MovieService $service)
    {
        $this->service = $service;
    }

    public function detail($type, $id)
    {
        if (!in_array($type, ['movie', 'tv'])) {
            return response()->json(['error' => 'Invalid type'], 400);
        }


        $result = $this->service->getDetail($type, $id);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json($result);
    }

    public function watch($type, $id, Request $request)
    {
        $season = $request->query('season') && (int)$request->query('season') > 0 ? (int)$request->query('season') : null;

        if (!in_array($type, ['movie', 'tv'])) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $result = $this->service->watch($type, $id, $season);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json($result);
    }
}
