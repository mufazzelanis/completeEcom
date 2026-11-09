<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Turns an invoice view into a PDF. Uses mPDF rather than dompdf because dompdf can't join Bangla
 * letters: vowel signs land on the wrong side and conjuncts fall apart, so customers' Bangla names
 * and addresses printed wrong.
 */
class InvoicePdf
{
    /** Height of the coloured band across the top of the first page, in mm. */
    public const BAND_MM = 44;

    public const LOGO_HEIGHT_MM = 13;

    public const LOGO_MAX_MM = 58;

    /** How many gaps between sections share the spare room on a short invoice. */
    private const AIR_GAPS = 4;

    /** @param string|null $accent overrides Settings → Invoice for this one document */
    public static function render(string $view, array $data, ?string $accent = null): string
    {
        // the images travel inside the HTML, which is more text than PCRE's default limit allows
        ini_set('pcre.backtrack_limit', '10000000');

        $tmp = storage_path('app/mpdf'); // mPDF's font cache; git-ignored
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true); // two first-ever downloads at once may race; either one creating it is fine
        }

        $data += ['theme' => self::theme($accent), 'store' => self::store(), 'money' => self::moneyFormatter()];

        // Laid out twice: the first pass measures what's left of the last page, the second uses it
        // so a one-item order doesn't end halfway down with a blank page below it.
        $mpdf = self::build($view, $data, $tmp, 0, 0);

        // A long order whose thank-you panel alone spills onto one more page goes without it
        if ($mpdf->page > 1) {
            $compact = self::build($view, $data, $tmp, 0, 0, true);
            if ($compact->page < $mpdf->page) {
                return $compact->Output('', 'S');
            }
        }

        $free = $mpdf->h - $mpdf->bMargin - $mpdf->y - 2;
        if ($free > 1) {
            $air = $mpdf->page === 1 ? min(5, $free * .5 / self::AIR_GAPS) : 0;
            $mpdf = self::build($view, $data, $tmp, $air, $free - $air * self::AIR_GAPS);
        }

        return $mpdf->Output('', 'S');
    }

    private static function build(string $view, array $data, string $tmp, float $air, float $push, bool $compact = false): Mpdf
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tmp,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 8,
            'margin_bottom' => 24,
            'margin_footer' => 7,
            'fontDir' => array_merge((new ConfigVariables())->getDefaults()['fontDir'], [storage_path('fonts')]),
            'fontdata' => (new FontVariables())->getDefaults()['fontdata'] + [
                'hind' => ['R' => 'HindSiliguri-Regular.ttf', 'B' => 'HindSiliguri-Bold.ttf', 'useOTL' => 0xFF],
            ],
            'default_font' => 'hind',
            'watermark_font' => 'hind',
            'showWatermarkText' => true,
        ]);
        $mpdf->SetCreator((string) setting('site_name', 'Store'));

        // Drawn on the page itself so the band can run edge to edge past the text margins
        $mpdf->AddPage();
        self::fill($mpdf, $data['theme']['dark'], 0, self::BAND_MM);
        self::fill($mpdf, $data['theme']['accent'], 0, 1.6);
        if ($data['store']['logo']) {
            self::logoPlate($mpdf, $data['store']['logo']);
        }

        $mpdf->WriteHTML(view($view, $data + ['air' => $air, 'push' => $push, 'compact' => $compact])->render());

        return $mpdf;
    }

    /** The white rounded plate behind the logo, so any logo colour reads on the dark band. */
    private static function logoPlate(Mpdf $mpdf, string $logo): void
    {
        [$w, $h] = getimagesizefromstring(base64_decode(substr($logo, strlen('data:image/png;base64,'))));
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->RoundedRect(12, 12.5, min(self::LOGO_MAX_MM, self::LOGO_HEIGHT_MM * $w / $h) + 10, self::LOGO_HEIGHT_MM + 6, 2.5, 'F');
    }

    /** The PDF as an HTTP response: opened in the browser, or saved as a file. */
    public static function response(string $bytes, string $filename, bool $inline = false)
    {
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"',
        ]);
    }

    private static function fill(Mpdf $mpdf, string $hex, float $y, float $height): void
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $mpdf->SetFillColor($r, $g, $b);
        $mpdf->Rect(0, $y, 210, $height, 'F');
    }

    /** Admin's two colours and the shades worked out from them, always readable on their background. */
    public static function theme(?string $accent): array
    {
        $valid = fn (?string $hex, string $fallback) => preg_match('/^#[0-9a-f]{6}$/i', (string) $hex) ? $hex : $fallback;
        $accent = $valid($accent ?: setting('invoice_accent_color'), '#6366f1');
        $dark = $valid(setting('invoice_dark_color'), '#111827');

        return [
            'accent' => $accent,
            'dark' => $dark,
            'onDark' => readable_text_color($dark, '#ffffff'),
            'accentText' => darken_for_white_text($accent), // labels and numbers printed on white
            'tint' => hex_shade($accent, 0.92),
            'tintLine' => hex_shade($accent, 0.75),
        ];
    }

    /** The seller's details, printed on every document. */
    public static function store(): array
    {
        return [
            'name' => setting('company_name') ?: setting('site_name', 'Store'),
            'email' => setting('company_email') ?: setting('support_email'),
            'phone' => setting('company_phone'),
            'address' => setting('company_address') ?: 'Dhaka, Bangladesh',
            'tax' => setting('invoice_tax_number'),
            'bank' => setting('invoice_bank_details'),
            'footer' => setting('invoice_footer_text', 'Thank you for shopping with us!'),
            'logo' => self::image(setting('invoice_logo'), 500) ?: self::image(setting('site_logo'), 500),
            'signature' => self::image(setting('invoice_signature'), 400),
            'showWords' => setting('invoice_show_words', '1') == '1',
            'currencyWord' => match (setting('currency_code', 'BDT')) {
                'BDT' => 'Taka', 'USD' => 'Dollars', 'INR' => 'Rupees', 'EUR' => 'Euros', 'GBP' => 'Pounds',
                default => setting('currency_code', 'BDT'),
            },
        ];
    }

    private static function moneyFormatter(): \Closure
    {
        $symbol = setting('currency_symbol', '৳');
        $right = setting('currency_position', 'left') === 'right';

        return function ($amount) use ($symbol, $right) {
            $formatted = number_format((float) $amount, 2);

            return $right ? $formatted . ' ' . $symbol : $symbol . $formatted;
        };
    }

    /**
     * A public-disk image as a small embedded PNG, whatever format it was uploaded in. Product
     * photos go through here too, so a 20-item invoice doesn't carry 20 full-size pictures.
     */
    public static function image(?string $path, int $maxWidth): ?string
    {
        $disk = Storage::disk('public');
        if (!$path || str_starts_with($path, 'http') || !$disk->exists($path)) {
            return null;
        }
        // an empty or damaged upload must cost the invoice one picture, not the whole download
        try {
            $source = @imagecreatefromstring((string) $disk->get($path));
        } catch (\Throwable) {
            return null;
        }
        if (!$source) {
            return null;
        }
        imagepalettetotruecolor($source);
        $width = imagesx($source);
        $height = imagesy($source);
        $newWidth = min($maxWidth, $width);
        $newHeight = max(1, (int) round($height * $newWidth / $width));

        $copy = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagecopyresampled($copy, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        ob_start();
        imagepng($copy, null, 9);
        imagedestroy($copy);

        return 'data:image/png;base64,' . base64_encode(ob_get_clean());
    }

    public static function showPhotos(): bool
    {
        return setting('invoice_show_photos', '1') == '1';
    }
}
