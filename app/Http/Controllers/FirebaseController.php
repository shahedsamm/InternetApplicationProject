

<?php

use Illuminate\Http\Request;

use App\Http\Requests\SaveFcmTokenRequest;

 function saveToken(SaveFcmTokenRequest $request)
{
    $user = auth()->user();

    $user->fcm_token = $request->fcm_token;
    $user->save();

    return response()->json([
        'status' => true,
        'message' => 'تم حفظ التوكن بنجاح'
    ]);
}

