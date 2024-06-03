<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\error;

class PartnerController extends RestController
{

    public function index(Request $request)
    {
        $partners = Partner::all();        
        //return $this->sendResponse($partners);
        return $this->sendResponse(PartnerResource::collection($partners));
    }

    public function create(Request $request)
    {

        $errors = [];

        if ($request->hasFile('image')) {
            $image = $request->file('image');


            // Проверяем размер изображения (например, максимальный размер 5MB)
            if ($image->getSize() > 5 * 1024 * 1024) {
                $errors[] = 'Размер файла слишком большой.';
            }

            $imageSizeArr = getimagesize($image);
            if ($imageSizeArr[0] > 512 && $imageSizeArr[1] > 512) {
                $errors[] = 'Ширина и высота изображения не должна превашать 512x512';
            }

            // Проверяем расширение изображения
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $extension = $image->getClientOriginalExtension();
            if (!in_array($extension, $allowedExtensions)) {
                $errors[] = 'Недопустимое расширение изображения. Разрешены только jpg, jpeg, png, gif.';
            }


            $input = [];
            $input['name'] = $request->input('name');
            $input['url'] = $request->input('url');

            if ($input['name'] == null) {
                $errors[] = "Имя партнера не указано";
            }

            if (empty($errors)) {
                // Генерируем уникальное имя для изображения
                $imageName = uniqid(20) . '.' . $extension;
                
                // Сохраняем изображение в хранилище с уникальным именем
                $path = $image->storeAs('public/partners', $imageName);
                if($path == null){
                    $errors[] = "Ошибка сохрания изображения";
                } else{

                
                $input['photo_path'] = '/storage/partners/' . $imageName;

                $partner = Partner::create($input);

                return $this->sendResponse($partner);
                }

            }
        } else {
            $errors[] = "Изображение отсутствует";
        }

        if (!empty($errors)) {
            return $this->sendError("Ошибка добавления партнера", $errors, 400);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $partner = Partner::find($id);

        if($partner == null){
            return $this->sendError("Партнер не найден", [], 404);
        }

        $pathArr = explode('/', $partner->photo_path);
        $photo_name = end($pathArr);
        $photo_path = '/public/partners/' . $photo_name;
        Storage::delete($photo_path);
        $partner->delete();
    }
}
