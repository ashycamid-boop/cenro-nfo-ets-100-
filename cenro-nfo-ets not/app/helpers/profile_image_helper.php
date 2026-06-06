<?php

function saveProfileImageFromDataUrl(string $dataUrl, ?int $userId = null): string
{
    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $dataUrl, $matches)) {
        throw new Exception('Invalid cropped image format.');
    }

    $mime = strtolower($matches[1]);
    $raw = substr($dataUrl, strpos($dataUrl, ',') + 1);
    $binary = base64_decode(str_replace(' ', '+', $raw), true);
    if ($binary === false) {
        throw new Exception('Invalid cropped image data.');
    }

    $uploadDir = __DIR__ . '/../../public/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new Exception('Unable to create uploads directory.');
    }

    $prefix = $userId !== null ? ('user_' . $userId . '_') : '';
    $extension = ($mime === 'jpeg' || $mime === 'jpg') ? '.jpg' : '.png';
    $filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . $extension;
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    // If GD exists, normalize to 512x512. If not, save raw decoded data.
    $canUseGd = function_exists('imagecreatefromstring')
        && function_exists('imagecreatetruecolor')
        && function_exists('imagecopyresampled')
        && function_exists('imagepng')
        && function_exists('imagesavealpha')
        && function_exists('imagealphablending');

    if ($canUseGd) {
        $image = imagecreatefromstring($binary);
        if ($image === false) {
            throw new Exception('Unable to read cropped image.');
        }

        $targetSize = 512;
        $canvas = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetSize, $targetSize, $transparent);

        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetSize, $targetSize, $srcWidth, $srcHeight);

        if (!imagepng($canvas, $destination, 6)) {
            imagedestroy($image);
            imagedestroy($canvas);
            throw new Exception('Unable to save cropped image.');
        }
        imagedestroy($image);
        imagedestroy($canvas);
    } else {
        if (file_put_contents($destination, $binary) === false) {
            throw new Exception('Unable to save cropped image.');
        }
    }

    return 'public/uploads/' . $filename;
}

function saveUploadedProfileImageFile(array $file, ?int $userId = null): ?string
{
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Failed to upload profile picture.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new Exception('Profile picture too large. Max 2MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        throw new Exception('Only JPG and PNG images are allowed.');
    }

    $uploadDir = __DIR__ . '/../../public/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new Exception('Unable to create uploads directory.');
    }

    $ext = $mime === 'image/png' ? '.png' : '.jpg';
    $prefix = $userId !== null ? ('user_' . $userId . '_') : '';
    $filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . $ext;
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to save uploaded profile picture.');
    }

    return 'public/uploads/' . $filename;
}
