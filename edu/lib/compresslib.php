<?php
function handleImageUpload($image)
{
    // Check for errors in image upload
    if ($image['error'] !== UPLOAD_ERR_OK) {
        return null;  // If there's an error, return null
    }

    // Check if the uploaded file is an image
    $imageType = exif_imagetype($image['tmp_name']);
    if ($imageType === false) {
        return null;
    }

    // Get the image file name (original file name)
    $imageName = basename($image['name']); // Get the original file name

    // Get the image file type (mime type)
    $imageType = exif_imagetype($image['tmp_name']);
    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF]; // Allowed image types

    // Define the path to store the image (in the "images" directory)
    $uploadDir = __DIR__ . '/../images/'; // Path relative to lib folder
    $uploadPath = $uploadDir . $imageName; // Use the original file name

    // Check if the upload directory exists and is writable
    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        return null;  // Directory is not writable
    }

    // Check if a file with the same name already exists and change the name if necessary
    if (file_exists($uploadPath)) {
        $fileInfo = pathinfo($imageName);
        $baseName = $fileInfo['filename']; // Extract base name without extension
        $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : ''; // Get the file extension
        $counter = 1;

        // Loop to find a new name if the file already exists
        while (file_exists($uploadDir . $baseName . "($counter)" . $extension)) {
            $counter++;  // Increment counter to create a new name
        }

        // Create the new name with the counter
        $imageName = $baseName . "($counter)" . $extension;
        $uploadPath = $uploadDir . $imageName; // Update the upload path with the new name
    }

    // Create an image resource from the uploaded file
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $imageResource = imagecreatefromjpeg($image['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $imageResource = imagecreatefrompng($image['tmp_name']);
            imagealphablending($imageResource, false);  // Disable alpha blending
            imagesavealpha($imageResource, true);       // Enable saving alpha
            break;
        case IMAGETYPE_GIF:
            $imageResource = imagecreatefromgif($image['tmp_name']);
            imagealphablending($imageResource, false);  // Disable alpha blending
            imagesavealpha($imageResource, true);       // Enable saving alpha
            break;
        default:
            return null;
    }

    // Compress the image (resize and reduce quality) if needed
    $compressedImage = compressImage($imageResource, $imageType);

    // Save the compressed image to the upload directory
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            if (!imagejpeg($compressedImage, $uploadPath, 85)) {
                return null;
            }
            break;
        case IMAGETYPE_PNG:
            if (!imagepng($compressedImage, $uploadPath, 8)) {
                return null;
            }
            break;
        case IMAGETYPE_GIF:
            if (!imagegif($compressedImage, $uploadPath)) {
                return null;
            }
            break;
    }

    // Free up memory
    imagedestroy($imageResource);
    imagedestroy($compressedImage);

    // If the file was successfully uploaded and compressed, return the image name
    return $imageName;  // Return the file name to store in the database
}

// Function to compress the image (resize and reduce quality)
function compressImage($imageResource, $imageType)
{
    $maxWidth = 2000;  // Maximum width for compression
    $maxHeight = 2000;  // Maximum height for compression

    // Get original image dimensions
    $originalWidth = imagesx($imageResource);
    $originalHeight = imagesy($imageResource);

    // Calculate aspect ratio
    $aspectRatio = $originalWidth / $originalHeight;

    // New dimensions based on the max width/height
    if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
        if ($aspectRatio > 1) {
            // Landscape: Resize by width
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $aspectRatio;
        } else {
            // Portrait: Resize by height
            $newHeight = $maxHeight;
            $newWidth = $maxHeight * $aspectRatio;
        }
        // Create a new blank image with the new dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF images
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        imagecopyresampled($newImage, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        return $newImage;  // Return the resized image
    }

    return $imageResource;  // Return the original image if no resizing is needed
}
?>