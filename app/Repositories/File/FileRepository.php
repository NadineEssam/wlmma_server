<?php

namespace App\Repositories\File;

use App\Models\File;

class FileRepository
{
    public function save($file_data)
    {
        return File::insertGetId($file_data, 'id');
    }
}
