<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\FileStoreRequest;
use App\Http\Requests\NameCheckRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Right;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


use Illuminate\Support\Facades\Validator;


class FileController extends Controller
{

public function upload(Request $request)
{
    // Проведем валидацию файлов
    $validator = Validator::make($request->all(), [
        'files.*' => 'required|file|mimes:doc,pdf,docx,zip,jpeg,jpg,png,exe|max:2048',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Ошибка валидаци',
            'errors' => $validator->errors(),
        ])->setStatusCode(422);
    }

    // Получаем массив файлов
    $files = $request->file('files');
    $responses = [];
    // Перебираем каждый файл для загрузки
    foreach ($files as $file) {
        // Проверяем валидность файла
        if ($file->isValid()) {
            // Генерируем уникальное имя файла
            $fileName = time() . '_' . $file->getClientOriginalName();

            // Путь сохранения файла
            $filePath = 'uploads/'. Auth::id();

            // Сохраняем файл
            $file->storeAs($filePath, $fileName);

            // Создаем запись о файле в БД
            $note = File::create([
                'user_id' => Auth::id(),
                'name' => $fileName,
                'extension' => $file->extension(), // Добавьте расширение файла
                'path' => $filePath, // Добавьте путь к файлу
                'file_id' => Str::random(10), // Уникальный идентификатор файла, если используется
            ]);
            $url = route('files.get', ['file_id' => $note->file_id]);
        }
        $responses[] = [
            "success"=> true,
     "code"=> 200,
     "message"=> "Success",
     "name"=>  $note->name,
     "url"=> $url,
     "file_id"=>$note->file_id

        ];
    }


    return response()->json($responses);


}
    public function edit(NameCheckRequest $request, $file_id)
    {
        // Проверка аутентификации пользователя
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user',
            ])->setStatusCode(401);
        }

        // Проверка существования файла
        $file = File::where('file_id', $file_id)->first();
        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ])->setStatusCode(404);
        }

        // Проверка владельца файла
        if ($file->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this file',
            ])->setStatusCode(403);
        }




        // Получаем старый путь к файлу
        $oldFilePath = 'uploads/' . $file->user_id . '/' . $file->name;

        // Формируем новый путь к файлу
        $newFilePath = 'uploads/' . $file->user_id . '/' . $request->name;

        // Переименовываем файл
        Storage::move($oldFilePath, $newFilePath);

        // Обновляем имя файла в базе данных
        $file->name = $request->name;
        $file->save();

        // Возвращаем успешный ответ
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Renamed',
        ])->setStatusCode(200);
    }
    public  function destroy($file_id){
        $file = File::where('file_id', $file_id)->first();
        $file->delete();
        return response(["success"=> true,
        "code"=> 200,
        "message"=> "File deleted"
    ]);
    }

    public function download($file_id){
        $file = File::where('file_id', $file_id)->first();
        $path = Storage::disk("local")->path("\uploads\\1\\". $file->name);
        return response()->download($path, basename($path));
    }


    public function owned(Request $request){

        $files = File::where('user_id', $request->user()->id)->get();
        return response(FileResource::collection($files));
    }
    public function allowed(Request $request){

    $allowedRights = Right::where('user_id', $request->user()->id)->get();
    $allowedRightsIds = [];
    if (!$allowedRights) throw new ApiException(404, 'С ВАМИ НЕ ДЕЛИЛИСЬ ФАЙЛАМИ');

        foreach ($allowedRights as $right){
            $allowedRightsIds[] = $right->file_id;

        }



    $allowedFiles = File::whereIn('id', $allowedRightsIds)->get();


        return response(FileResource::collection($allowedFiles));

    }

}
