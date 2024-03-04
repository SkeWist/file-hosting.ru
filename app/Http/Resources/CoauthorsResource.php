<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoauthorsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = User::find($this->user_id);
        return [
            'fullname' => $user->first_name. ' ' . $user->last_name,
            'email' => $user->email,
            'type'=>'co-author',
  'code'=> 200


            // Другие поля вашей модели, которые вы хотите включить
        ];
    }
}
