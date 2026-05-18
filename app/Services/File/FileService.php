<?php

namespace App\Services\File;

use App\Core\Helpers\ResponseHelper;
use App\Repositories\File\FileRepository;
use App\Repositories\Pilot\PilotRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FileService
{
    public function __construct(
        protected FileRepository $fileRepo,
    ) {}

    public function upload($file)
    {
        $full_name = $file->hashName();
        $extension = $file->extension();
        $size = $file->getSize();
        $path = public_path('storage/');
        $mime_type = $file->getMimeType();

        $file->move($path, $full_name);


        $file_data = [
            'name' => $full_name,
            'extension' => $extension,
            'path' => $path,
            'mime_type' => $mime_type,
            'size' => $size,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        $id = $this->fileRepo->save($file_data);

        return ['id' => $id];
    }
}
