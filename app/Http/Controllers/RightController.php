<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\EmailCheckRequest;
use App\Http\Resources\CoauthorsResource;
use App\Models\File;
use App\Models\Right;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RightController extends Controller
{
    //Добавление доступа к файл.
    public function add(EmailCheckRequest $request, $file_id)
    {

        // Проверка аутентификации пользователя
        $file = File::where("file_id",$file_id )->first();
        if (!$file){
            throw new ApiException(404, 'File not found');
        }

        // Проверяем, является ли текущий пользователь владельцем файла
        if ($file->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied. You are not the owner of this file.'
            ], 403);
        }
        $author = User::find(auth()->id());

        // Находим пользователя по email
        $coAuthor = User::where('email', $request->email)->first();

        $right =Right::create(['file_id'=>$file->id,
                'user_id'=>$coAuthor->id
                ]
        );

        $rights = Right::where('file_id', $file->id)->get();

        $data[]=[
            'fullname' => $author->first_name. ' ' . $author->last_name,
            'email' => $author->email,
            'type' => 'author',
            'code' => 200,
        ];
        $data2 = array(...CoauthorsResource::collection($rights));


return response(array_merge($data , $data2));

    }
//Удаление доступа
    public function destroy(EmailCheckRequest $request, $file_id)
    {


        // Проверка аутентификации пользователя
        $file = File::where("file_id",$file_id )->first();
        if (!$file){
            throw new ApiException(404, 'File not found');
        }
        $author = User::find(auth()->id());
        // Проверяем, является ли текущий пользователь владельцем файла
        if ($file->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied. You are not the owner of this file.'
            ], 403);
        }

        // Находим пользователя по email
        $coAuthor = User::where('email', $request->email)->first();

        //Ищем пользователя и файл, и удаляем

        Right::where('user_id', $coAuthor->id)->where('file_id', $file->id)->delete();

        $rights = Right::where('file_id', $file->id)->get();

        $data[]=[
            'fullname' => $author->first_name. ' ' . $author->last_name,
            'email' => $author->email,
            'type' => 'author',
            'code' => 200,
        ];
        $data2 = array(...CoauthorsResource::collection($rights));


        return response(array_merge($data , $data2));


    }

}
