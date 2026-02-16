<?php

namespace App\Services;

use App\Models\BirthdayContact;
use Carbon\Carbon;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class BirthdayImageGenerator
{
    private array $config;
    private ImageManager $manager;

    public function __construct()
    {
        $this->config = config('birthday-template');
        $this->manager = new ImageManager(new Driver());
    }

    public function generate(BirthdayContact $contact): string
    {
        $templatePath = $this->config['template_path'];
        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found at: {$templatePath}");
        }

        $template = $this->manager->read($templatePath);

        if ($contact->photo_path) {
            $this->addContactPhoto($template, $contact->photo_path);
        }

        $this->addName($template, $contact->name);

        // Now renders DATE instead of "Turning X today"
        $this->addBirthdayDate($template, $contact->birthday);

        return $this->saveImage($template, $contact->id);
    }

    private function addContactPhoto($canvas, string $photoPath): void
    {
        $photo = null;

        if (filter_var($photoPath, FILTER_VALIDATE_URL)) {
            try {
                $photo = $this->manager->read($photoPath);
            } catch (\Exception $e) {
                return;
            }
        } else {
            $possiblePaths = [
                $photoPath,
                storage_path('app/public/' . $photoPath),
                storage_path('app/private/' . $photoPath),
                storage_path('app/' . $photoPath),
                public_path($photoPath)
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $photo = $this->manager->read($path);
                    break;
                }
            }
        }

        if (!$photo) {
            return;
        }

        $config = $this->config['photo'];

        $photo->cover($config['width'], $config['height']);

        if ($config['border_radius'] >= 50) {
            $photo = $this->makeCircular($photo, $config['width']);
        }

        if ($config['border_width'] > 0) {
            $photo = $this->addBorder(
                $photo,
                $config['border_width'],
                $config['border_color'],
                $config['border_radius']
            );
        }

        $canvas->place($photo, 'top-left', $config['x'], $config['y']);
    }

    private function addName($canvas, string $name): void
    {
        $config = $this->config['name'];
        $fontPath = $this->getFontPath($config['font_path'], true);

        $canvas->text($name, $config['x'], $config['y'], function ($font) use ($config, $fontPath) {
            if ($fontPath) {
                $font->file($fontPath);
            }

            $font->size($config['font_size']);
            $font->color($config['color']);
            $font->align($config['align']);
            $font->valign('middle');
        });
    }

    // ⭐ NEW — DATE FORMATTER
    private function addBirthdayDate($canvas, $birthday): void
    {
        $config = $this->config['age'];
        $fontPath = $this->getFontPath($config['font_path'], true);

        $date = Carbon::parse($birthday)->format('jS M');

        $canvas->text($date, $config['x'], $config['y'], function ($font) use ($config, $fontPath) {
            if ($fontPath) {
                $font->file($fontPath);
            }

            $font->size($config['font_size']);
            $font->color($config['color']);
            $font->align($config['align']);
            $font->valign('middle');
        });
    }

    private function getFontPath(string $configuredPath, bool $isBold = false): ?string
    {
        if (file_exists($configuredPath)) {
            return $configuredPath;
        }

        $fallback = $isBold ? 'C:\Windows\Fonts\arialbd.ttf' : 'C:\Windows\Fonts\arial.ttf';

        return file_exists($fallback) ? $fallback : null;
    }

    private function makeCircular($image, int $diameter)
    {
        $radius = $diameter / 2;
        $native = $image->core()->native();

        imagealphablending($native, false);
        imagesavealpha($native, true);

        for ($x = 0; $x < $diameter; $x++) {
            for ($y = 0; $y < $diameter; $y++) {
                $dx = $x - $radius;
                $dy = $y - $radius;
                $distance = sqrt($dx * $dx + $dy * $dy);

                if ($distance > $radius) {
                    imagesetpixel($native, $x, $y, imagecolorallocatealpha($native, 0, 0, 0, 127));
                }
            }
        }

        return $image;
    }

    private function addBorder($image, int $width, string $color, int $borderRadius)
    {
        $size = $image->width() + ($width * 2);

        $bordered = $this->manager->create($size, $size)->fill($color);

        if ($borderRadius >= 50) {
            $this->makeCircular($bordered, $size);
        }

        $bordered->place($image, 'center');

        return $bordered;
    }

    private function saveImage($image, int $contactId): string
    {
        $fileName = 'birthday_' . $contactId . '_' . time() . '.png';
        $directory = storage_path('app/public/birthday-generated');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . '/' . $fileName;

        $image->save($path);

        return $path;
    }
}
