<?php

namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\AfishaResource;
use App\Models\Afisha;
use Illuminate\Support\Facades\Storage;

class AfishaController extends RestController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){

        $afishas = Afisha::where('active', '1')->get();
        //where('active', '=', '1');
        return $this->sendResponse(AfishaResource::collection($afishas));
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
            $input['image'] = $request->input('image');

            if ($input['image'] == null) {
                $errors[] = "Изображение не выбрано";
            }

            if (empty($errors)) {
                // Генерируем уникальное имя для изображения
                $imageName = uniqid(20) . '.' . $extension;
                
                // Сохраняем изображение в хранилище с уникальным именем
                $path = $image->storeAs('public/afishas', $imageName);
                if($path == null){
                    $errors[] = "Ошибка сохрания изображения";
                } else{

                
                $input['photo_path'] = '/storage/afishas/' . $imageName;

                $afisha = Afisha::create($input);

                return $this->sendResponse($afisha);
                }

            }
        } else {
            $errors[] = "Изображение отсутствует";
        }

        if (!empty($errors)) {
            return $this->sendError("Ошибка добавления афиши", $errors, 400);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $afisha = Afisha::find($id);

        if($afisha == null){
            return $this->sendError("Афиша не найдена", [], 404);
        }

        $pathArr = explode('/', $afisha->photo_path);
        $photo_name = end($pathArr);
        $photo_path = '/public/afishas/' . $photo_name;
        Storage::delete($photo_path);
        $afisha->delete();
    }
}
