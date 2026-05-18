<?php

namespace App\Services\CommercialTool;

use App\Models\CommercialToolImage;
use App\Models\Image;
use App\Repositories\CommercialTool\CommercialToolRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CommercialToolService
{
    public function __construct(
        public CommercialToolRepository $repo
    ) {}

    // public function createCommercialTool($data, $files, $serviceProviderId)
    // {
    //     $data['user_id'] = $serviceProviderId;
    //     $tools = $this->repo->createCommercialTool($data);  // returns an array of tools

    //     foreach ($tools as $index => $tool) {
    //         // Skip if no images uploaded for this tool
    //         if (!isset($files[$index]) || empty($files[$index]))
    //             continue;

    //         $imagePaths = [];

    //         // foreach ($files[$index] as $image) {
    //         //     $extension = $image->getClientOriginalExtension();
    //         //     $filename = uniqid() . '.' . $extension;
    //         //     $destinationPath = storage_path('app/public/commercialtools/' . $filename);

    //         //     // Resize image to 800px width while keeping aspect ratio
    //         //     list($width, $height) = getimagesize($image);
    //         //     $newWidth = 800;
    //         //     $newHeight = intval($height * ($newWidth / $width));

    //         //     $sourceImage = match ($extension) {
    //         //         'jpg', 'jpeg' => imagecreatefromjpeg($image),
    //         //         'png' => imagecreatefrompng($image),
    //         //         'webp' => imagecreatefromwebp($image),
    //         //         default => null,
    //         //     };

    //         //     if ($sourceImage) {
    //         //         $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    //         //         imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    //         //         // Save the resized image
    //         //         match ($extension) {
    //         //             'jpg', 'jpeg' => imagejpeg($resizedImage, $destinationPath, 75),
    //         //             'png' => imagepng($resizedImage, $destinationPath, 6),
    //         //             'webp' => imagewebp($resizedImage, $destinationPath, 75),
    //         //             default => null,
    //         //         };

    //         //         imagedestroy($sourceImage);
    //         //         imagedestroy($resizedImage);

    //         //         $imagePaths[] = [
    //         //             'image_path' => 'commercialtools/' . $filename,
    //         //             'created_at' => now(),
    //         //             'updated_at' => now(),
    //         //         ];
    //         //     }
    //         // }
    //          // Normalize to array - handle both single file and array of files
    //     $fileArray = is_array($files[$index]) ? $files[$index] : [$files[$index]];
    //          foreach ($fileArray as $image) {
    //         if (!$image) continue;

    //         $extension = $image->getClientOriginalExtension();
    //         $filename = uniqid() . '.' . $extension;
    //         $destinationPath = storage_path('app/public/commercialtools/' . $filename);

    //     Log::info('DestinationPath: ' . $destinationPath);
    //         // Ensure directory exists
    //         $directory = dirname($destinationPath);
    //         if (!is_dir($directory)) {
    //             mkdir($directory, 0755, true);
    //         }

    //         // Your image processing code...
    //         list($width, $height) = getimagesize($image);
    //         $newWidth = 800;
    //         $newHeight = intval($height * ($newWidth / $width));

    //         $sourceImage = match ($extension) {
    //             'jpg', 'jpeg' => imagecreatefromjpeg($image),
    //             'png' => imagecreatefrompng($image),
    //             'webp' => imagecreatefromwebp($image),
    //             default => null,
    //         };

    //         if ($sourceImage) {
    //             $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    //             imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    //             match ($extension) {
    //                 'jpg', 'jpeg' => imagejpeg($resizedImage, $destinationPath, 75),
    //                 'png' => imagepng($resizedImage, $destinationPath, 6),
    //                 'webp' => imagewebp($resizedImage, $destinationPath, 75),
    //                 default => null,
    //             };

    //             imagedestroy($sourceImage);
    //             imagedestroy($resizedImage);

    //             $imagePaths[] = [
    //                 'image_path' => 'commercialtools/' . $filename,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ];
    //         }
    //     }

    //         // Insert images for this tool
    //         Image::insert($imagePaths);

    //         $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
    //         $insertedCount = count($imagePaths);
    //         $ids = range($lastId - $insertedCount + 1, $lastId);

    //         // Link each image to this specific tool
    //         $imageTool = [];
    //         foreach ($ids as $id) {
    //             $imageTool[] = [
    //                 'image_id' => $id,
    //                 'commercial_tool_id' => $tool->id,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ];
    //         }
    //         CommercialToolImage::insert($imageTool);

    //         // Optional: prepend full URL to images
    //         foreach ($tool->toolImages as $img) {
    //             $img->image_path = Str::startsWith($img->image_path, ['http://', 'https://'])
    //                 ? $img->image_path
    //                 : env('APP_URL') . 'storage/app/public/' . $img->image_path;
    //         }
    //     }

    //     return $tools;
    // }

    // public function createCommercialTool($data, $files, $serviceProviderId) /// WORKING OLD
    // {
    //     $data['user_id'] = $serviceProviderId;
    //     $tools = $this->repo->createCommercialTool($data);


    //     // Debug the exact structure of files
    //     foreach ($files as $toolIndex => $toolFiles) {
    //         if (is_array($toolFiles)) {
    //             \Log::info("Tool $toolIndex has " . count($toolFiles) . ' images');
    //             foreach ($toolFiles as $imageIndex => $file) {
    //                 \Log::info("  -> Image $imageIndex: " . (is_object($file) ? $file->getClientOriginalName() : 'invalid'));
    //             }
    //         } else {
    //             \Log::info("Tool $toolIndex has single file: " . (is_object($toolFiles) ? $toolFiles->getClientOriginalName() : 'invalid'));
    //         }
    //     }

    //     // Process each tool with its corresponding multiple images
    //     foreach ($tools as $toolIndex => $tool) {
    //         // \Log::info("Processing tool index: $toolIndex with ID: " . $tool->id);

    //         $imagePaths = [];

    //         // Check if there are files for this specific tool index
    //         if (isset($files[$toolIndex])) {
    //             $toolFiles = $files[$toolIndex];

    //             // If it's a single file, convert to array for consistent processing
    //             $filesToProcess = is_array($toolFiles) ? $toolFiles : [$toolFiles];

    //             \Log::info('Processing ' . count($filesToProcess) . ' images for tool ID: ' . $tool->id);

    //             foreach ($filesToProcess as $imageIndex => $image) {
    //                 if (!$image) {
    //                     \Log::info("Skipping null image at index $imageIndex for tool $toolIndex");
    //                     continue;
    //                 }

    //                 $extension = $image->getClientOriginalExtension();
    //                 $filename = uniqid() . '.' . $extension;
    //                 $destinationPath = storage_path('app/public/commercialtools/' . $filename);

    //                 // Ensure directory exists
    //                 $directory = dirname($destinationPath);
    //                 if (!is_dir($directory)) {
    //                     mkdir($directory, 0755, true);
    //                 }

    //                 list($width, $height) = getimagesize($image);
    //                 $newWidth = 800;
    //                 $newHeight = intval($height * ($newWidth / $width));

    //                 $sourceImage = match ($extension) {
    //                     'jpg', 'jpeg' => imagecreatefromjpeg($image),
    //                     'png' => imagecreatefrompng($image),
    //                     'webp' => imagecreatefromwebp($image),
    //                     default => null,
    //                 };

    //                 if ($sourceImage) {
    //                     $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    //                     imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    //                     match ($extension) {
    //                         'jpg', 'jpeg' => imagejpeg($resizedImage, $destinationPath, 75),
    //                         'png' => imagepng($resizedImage, $destinationPath, 6),
    //                         'webp' => imagewebp($resizedImage, $destinationPath, 75),
    //                         default => null,
    //                     };

    //                     imagedestroy($sourceImage);
    //                     imagedestroy($resizedImage);

    //                     $imagePaths[] = [
    //                         'image_path' => 'commercialtools/' . $filename,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];

    //                     \Log::info("Successfully processed image $imageIndex: $filename for tool ID: " . $tool->id);
    //                 }
    //             }

    //             // Database operations for this tool's images
    //             if (!empty($imagePaths)) {
    //                 \Log::info('Inserting ' . count($imagePaths) . ' images into database for tool ID: ' . $tool->id);

    //                 // Insert images
    //                 Image::insert($imagePaths);

    //                 // Get the inserted image IDs
    //                 $insertedImages = Image::whereIn('image_path', array_column($imagePaths, 'image_path'))->get();

    //                 // Create pivot records for this tool
    //                 $pivotData = [];
    //                 foreach ($insertedImages as $image) {
    //                     $pivotData[] = [
    //                         'commercial_tool_id' => $tool->id,
    //                         'image_id' => $image->id,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];
    //                 }

    //                 CommercialToolImage::insert($pivotData);
    //                 // \Log::info("Created " . count($pivotData) . " pivot records for tool ID: " . $tool->id);
    //             }
    //         } else {
    //             \Log::info("No files found for tool index: $toolIndex");
    //         }
    //     }

    //     // Load relationships with images and return formatted response
    //     return $this->formatToolsWithImages($tools);
    // }



public function createCommercialToolForAdmin($data, $files, $serviceProviderId)
    {
        $data['user_id'] = $serviceProviderId;
        $tools = $this->repo->createCommercialTool($data);

        $masterToolId = null;
        $masterImageIds = [];

        // Debug uploaded files
        foreach ($files as $toolIndex => $toolFiles) {
            if (is_array($toolFiles)) {
                \Log::info("Tool $toolIndex has " . count($toolFiles) . ' images');
            } else {
                \Log::info("Tool $toolIndex has single image");
            }
        }

        foreach ($tools as $toolIndex => $tool) {
            /**
             * -------------------------------------------------
             * If images already uploaded → reuse them
             * -------------------------------------------------
             */
            if ($masterToolId !== null) {
                \Log::info("Reusing images from tool ID $masterToolId for tool ID {$tool->id}");

                $pivotData = [];
                foreach ($masterImageIds as $imageId) {
                    $pivotData[] = [
                        'commercial_tool_id' => $tool->id,
                        'image_id' => $imageId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                CommercialToolImage::insert($pivotData);
                continue;
            }

            /**
             * -------------------------------------------------
             * First tool → upload images
             * -------------------------------------------------
             */
            if (!isset($files[$toolIndex])) {
                \Log::warning("No files found for tool index: $toolIndex");
                continue;
            }

            $toolFiles = is_array($files[$toolIndex]) ? $files[$toolIndex] : [$files[$toolIndex]];
            $imagePaths = [];

            Log::info('Uploading ' . count($toolFiles) . ' images for tool ID: ' . $tool->id);

            // foreach ($toolFiles as $imageIndex => $image) {

            //     if (!$image) {
            //         continue;
            //     }

            //     $extension = strtolower($image->getClientOriginalExtension());
            //     $filename = uniqid() . '.' . $extension;
            //     // $destinationPath = storage_path('commercialtools/' . $filename); // OLD

            //     if (!is_dir(dirname($destinationPath))) {
            //         mkdir(dirname($destinationPath), 0755, true);
            //     }

            //     [$width, $height] = getimagesize($image);
            //     $newWidth = 800;
            //     $newHeight = intval($height * ($newWidth / $width));

            //     $sourceImage = match ($extension) {
            //         'jpg', 'jpeg' => imagecreatefromjpeg($image),
            //         'png' => imagecreatefrompng($image),
            //         'webp' => imagecreatefromwebp($image),
            //         default => null,
            //     };

            //     if (!$sourceImage) {
            //         continue;
            //     }

            //     $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            //     imagecopyresampled(
            //         $resizedImage,
            //         $sourceImage,
            //         0, 0, 0, 0,
            //         $newWidth,
            //         $newHeight,
            //         $width,
            //         $height
            //     );

            //     match ($extension) {
            //         'jpg', 'jpeg' => imagejpeg($resizedImage, $destinationPath, 75),
            //         'png' => imagepng($resizedImage, $destinationPath, 6),
            //         'webp' => imagewebp($resizedImage, $destinationPath, 75),
            //     };

            //     imagedestroy($sourceImage);
            //     imagedestroy($resizedImage);

            //     $imagePaths[] = [
            //         'image_path' => 'commercialtools/' . $filename,
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ];
            // }

            foreach ($toolFiles as $image) {
                if (!$image) {
                    continue;
                }

                $path = $image->store('commercialtools', 'public');  // التخزين في storage/app/public/commercialtools

                $imagePaths[] = [
                    'image_path' => $path,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ];
            }

            if (empty($imagePaths)) {
                continue;
            }

            /**
             * -------------------------------------------------
             * Insert images & attach to FIRST tool
             * -------------------------------------------------
             */
            Image::insert($imagePaths);

            $insertedImages = Image::whereIn(
                'image_path',
                array_column($imagePaths, 'image_path')
            )->get();

            $pivotData = [];
            foreach ($insertedImages as $image) {
                $pivotData[] = [
                    'commercial_tool_id' => $tool->id,
                    'image_id' => $image->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            CommercialToolImage::insert($pivotData);

            /**
             * -------------------------------------------------
             * Save master images for reuse
             * -------------------------------------------------
             */
            $masterToolId = $tool->id;
            $masterImageIds = $insertedImages->pluck('id')->toArray();

            \Log::info("Master images stored for tool ID {$tool->id}");
        }

        return $this->formatToolsWithImages($tools);
    }

    public function createCommercialTool($data, $files, $serviceProviderId)
{
    $data['user_id'] = $serviceProviderId;
    $tools = $this->repo->createCommercialTool($data);

    $masterToolId = null;
    $masterImageIds = [];

    // Debug uploaded files
    foreach ($files as $toolIndex => $toolFiles) {
        if (is_array($toolFiles)) {
            \Log::info("Tool $toolIndex has " . count($toolFiles) . ' images');
        } else {
            \Log::info("Tool $toolIndex has single image");
        }
    }

    foreach ($tools as $toolIndex => $tool) {

        /**
         * -------------------------------------------------
         * If images already uploaded → reuse them
         * -------------------------------------------------
         */
        if ($masterToolId !== null) {

            \Log::info("Reusing images from tool ID $masterToolId for tool ID {$tool->id}");

            $pivotData = [];
            foreach ($masterImageIds as $imageId) {
                $pivotData[] = [
                    'commercial_tool_id' => $tool->id,
                    'image_id' => $imageId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            CommercialToolImage::insert($pivotData);
            continue;
        }

        /**
         * -------------------------------------------------
         * First tool → upload images
         * -------------------------------------------------
         */
        if (!isset($files[$toolIndex])) {
            \Log::warning("No files found for tool index: $toolIndex");
            continue;
        }

        $toolFiles = is_array($files[$toolIndex]) ? $files[$toolIndex] : [$files[$toolIndex]];
        $imagePaths = [];

        \Log::info('Uploading ' . count($toolFiles) . ' images for tool ID: ' . $tool->id);

        foreach ($toolFiles as $imageIndex => $image) {

            if (!$image) {
                continue;
            }

            $extension = strtolower($image->getClientOriginalExtension());
            $filename = uniqid() . '.' . $extension;
            $destinationPath = storage_path('app/public/commercialtools/' . $filename);

            if (!is_dir(dirname($destinationPath))) {
                mkdir(dirname($destinationPath), 0755, true);
            }

            [$width, $height] = getimagesize($image);
            $newWidth = 800;
            $newHeight = intval($height * ($newWidth / $width));

            $sourceImage = match ($extension) {
                'jpg', 'jpeg' => imagecreatefromjpeg($image),
                'png' => imagecreatefrompng($image),
                'webp' => imagecreatefromwebp($image),
                default => null,
            };

            if (!$sourceImage) {
                continue;
            }

            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled(
                $resizedImage,
                $sourceImage,
                0, 0, 0, 0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            match ($extension) {
                'jpg', 'jpeg' => imagejpeg($resizedImage, $destinationPath, 75),
                'png' => imagepng($resizedImage, $destinationPath, 6),
                'webp' => imagewebp($resizedImage, $destinationPath, 75),
            };

            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            $imagePaths[] = [
                'image_path' => 'commercialtools/' . $filename,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($imagePaths)) {
            continue;
        }

        /**
         * -------------------------------------------------
         * Insert images & attach to FIRST tool
         * -------------------------------------------------
         */
        Image::insert($imagePaths);

        $insertedImages = Image::whereIn(
            'image_path',
            array_column($imagePaths, 'image_path')
        )->get();

        $pivotData = [];
        foreach ($insertedImages as $image) {
            $pivotData[] = [
                'commercial_tool_id' => $tool->id,
                'image_id' => $image->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        CommercialToolImage::insert($pivotData);

        /**
         * -------------------------------------------------
         * Save master images for reuse
         * -------------------------------------------------
         */
        $masterToolId = $tool->id;
        $masterImageIds = $insertedImages->pluck('id')->toArray();

        \Log::info("Master images stored for tool ID {$tool->id}");
    }

    return $this->formatToolsWithImages($tools);
}


    // Helper method to format tools with images
    private function formatToolsWithImages($tools)
    {
        $formattedTools = [];

        foreach ($tools as $tool) {
            // Load the toolImages relationship
            $tool->load('toolImages');

            $toolArray = $tool->toArray();

            // Format images with full URLs
            $images = [];
            foreach ($tool->toolImages as $image) {
                $fullUrl = $this->getImageFullUrl($image->image_path);
                $images[] = [
                    'id' => $image->id,
                    // 'image_path' => $image->image_path,
                    'image_path' => $fullUrl,
                    // 'full_url' => $fullUrl,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at,
                ];
            }

            $toolArray['tool_images'] = $images;
            $formattedTools[] = $toolArray;
        }

        return $formattedTools;
    }

    // Helper method to get full image URL
    private function getImageFullUrl($imagePath)
    {
        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        $filename = basename($imagePath);
        return env('APP_URL') . 'storage/app/public/commercialtools/' . $filename;
    }

    //     public function createCommercialTool($data, $files, $serviceProviderId)
    // {
    //     $data['user_id'] = $serviceProviderId;
    //     $tools = $this->repo->createCommercialTool($data);

    //     \Log::info('Tools created: ' . count($tools));
    //     \Log::info('Files received: ' . count($files));

    //     // If we have tools and files, process all files for the first tool
    //     if (!empty($tools) && !empty($files)) {
    //         $tool = $tools[0]; // Get the first tool
    //         $imagePaths = [];

    //         \Log::info("Processing all " . count($files) . " files for tool ID: " . $tool->id);

    //         foreach ($files as $fileIndex => $file) {
    //             \Log::info("Processing file index: $fileIndex");

    //             // Handle both single file and array of files
    //             $filesToProcess = is_array($file) ? $file : [$file];

    //             foreach ($filesToProcess as $image) {
    //                 if (!$image) continue;

    //                 $extension = $image->getClientOriginalExtension();
    //                 $filename = uniqid() . '.' . $extension;
    //                 $destinationPath = storage_path('app/public/commercialtools/' . $filename);

    //                 // Ensure directory exists
    //                 $directory = dirname($destinationPath);
    //                 if (!is_dir($directory)) {
    //                     mkdir($directory, 0755, true);
    //                 }

    //                 list($width, $height) = getimagesize($image);
    //                 $newWidth = 800;
    //                 $newHeight = intval($height * ($newWidth / $width));

    //                 $sourceImage = match ($extension) {
    //                     'jpg', 'jpeg' => imagecreatefromjpeg($image),
    //                     'png' => imagecreatefrompng($image),
    //                     'webp' => imagecreatefromwebp($image),
    //                     default => null,
    //                 };

    //                 if ($sourceImage) {
    //                     $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    //                     imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    //                     match ($extension) {
    //                         'jpg', 'jpeg' => imagejpeg($resizedImage, $destinationPath, 75),
    //                         'png' => imagepng($resizedImage, $destinationPath, 6),
    //                         'webp' => imagewebp($resizedImage, $destinationPath, 75),
    //                         default => null,
    //                     };

    //                     imagedestroy($sourceImage);
    //                     imagedestroy($resizedImage);

    //                     $imagePaths[] = [
    //                         'image_path' => 'commercialtools/' . $filename,
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];

    //                     \Log::info("Successfully processed image: $filename");
    //                 }
    //             }
    //         }

    //         // Database operations
    //         if (!empty($imagePaths)) {
    //             \Log::info("Inserting " . count($imagePaths) . " images into database");

    //             Image::insert($imagePaths);

    //             $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
    //             $insertedCount = count($imagePaths);
    //             $ids = range($lastId - $insertedCount + 1, $lastId);

    //             $imageTool = [];
    //             foreach ($ids as $id) {
    //                 $imageTool[] = [
    //                     'image_id' => $id,
    //                     'commercial_tool_id' => $tool->id,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];
    //             }
    //             CommercialToolImage::insert($imageTool);
    //         }
    //     }

    //     return $tools;
    // }

    public function updateCommercialTool($data, $files, $toolId)
    {
        return DB::transaction(function () use ($data, $files, $toolId) {
            // Update the CommercialTool using the repository
            // \Log::info('Received data:', $data);
            $tool = $this->repo->updateCommercialTool($data, $toolId);

            // Process uploaded files
            $imagePaths = [];
            foreach ($files as $image) {
                $path = $image->store('commercialtools', 'public');  // Store in 'public/activities' directory
                $imagePaths[] = [
                    'image_path' => $path,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert new images into the Image table
            $imageIds = [];
            if (!empty($imagePaths)) {
                Image::insert($imagePaths);
                $imageIds = Image::latest()->take(count($files))->pluck('id')->toArray();
            }

            // Prepare relationships for CommercialToolImage
            $imageActivity = [];
            foreach ($imageIds as $imageId) {
                $imageActivity[] = [
                    'image_id' => $imageId,
                    'commercial_tool_id' => $tool->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert image relationships into CommercialToolImage table
            if (!empty($imageActivity)) {
                CommercialToolImage::insert($imageActivity);
            }

            // Eager load related images for the CommercialTool
            $tool->load(['toolImages']);

            return $tool;
        });
    }

    public function addORdeletewishlist(array $data)
    {
        return $this->repo->addORdeletewishlist($data);
    }

    public function get_wishlist($perPage): LengthAwarePaginator
    {
        return $this->repo->get_wishlist($perPage);
    }
}
