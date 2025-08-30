<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSetting;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ResponseTrait;
    public function show()
    {
        $user = User::withCount('books')
            ->withCount([
                'books as favourite_books_count' => fn($query) => $query->onlyFavourite(),
            ])
            ->with('setting')
            ->find(auth()->id());
        return $this->successResponse(data: $user);
    }

    public function settings(Request $request)
    {
        $validated = $request->validate([
            'language' => 'required|string|max:50',
            'voice' => 'required|string|max:100',
            'pitch' => 'required|numeric|between:0.5,2.0',
            'rate' => 'required|numeric|between:0.5,2.0',
        ]);

        UserSetting::updateOrCreate(['user_id' => auth()->id()], $validated);

        return $this->successMessageResponse('Settings Updated successfully');
    }

    public function getSettings()
    {
        $settings = UserSetting::where(['user_id' => auth()->id()])->first();

        return $this->successResponse('Settings retrieved successfully', $settings);
    }
}
