<?php

namespace App\Http\Controllers\Api\V1\Presenter;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Http\Resources\PresenterResource;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    use ResolvesAuthenticatedPresenter;

    public function __invoke(): JsonResponse
    {
        $presenter = $this->authenticatedPresenter()->load('category');
        $user = auth()->user();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'presenter' => new PresenterResource($presenter),
        ]);
    }
}
