<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The shop's name and contact details, edited once (Settings → Business Profile). They used to be
 * typed separately for the footer, contact page, top bar, invoices and emails, so changing a phone
 * number meant finding every copy. Saving here writes each value to every setting that holds it.
 */
class BusinessProfile
{
    /** field => settings that show it, with a place name for the admin. */
    public const COPIES = [
        'name' => ['site_name' => 'Website, emails, SMS', 'og_site_name' => 'Facebook / social previews', 'mail_from_name' => 'Email sender name'],
        'phone' => ['company_phone' => 'Footer, invoices, emails', 'contact_phone' => 'Contact page, Google info', 'topbar_phone' => 'Top bar'],
        'email' => ['company_email' => 'Invoices', 'support_email' => 'Footer, emails', 'contact_email' => 'Contact page', 'topbar_email' => 'Top bar'],
        'address' => ['company_address' => 'Footer, invoices, emails, Google info', 'contact_address' => 'Contact page'],
    ];

    /** Single-setting fields. */
    private const SINGLE = [
        'legal_name' => 'company_name',
        'tagline' => 'site_tagline',
        'hours' => 'contact_hours',
        'whatsapp' => 'whatsapp_link',
        'tax' => 'invoice_tax_number',
    ];

    /** The top bar appears as soon as it has a value, so a bar the admin left empty stays off. */
    private const ONLY_IF_ALREADY_USED = ['topbar_phone', 'topbar_email'];

    /** Free text that usually spells out the shop's name. */
    private const NAME_IN_TEXT = ['site_title', 'seo_meta_title', 'seo_meta_description', 'copyright_text', 'footer_description', 'invoice_footer_text'];

    /** What the form opens with. */
    public static function current(): array
    {
        $first = fn (string $field) => collect(array_keys(self::COPIES[$field]))->map(fn ($key) => setting($key))->first(fn ($v) => filled($v));

        $name = setting('site_name') ?: setting('company_name');
        $legal = setting('company_name');

        return [
            'name' => $name,
            'legal_name' => $legal && $legal !== $name ? $legal : '',
            'phone' => $first('phone'),
            'email' => $first('email'),
            'address' => $first('address'),
            'tagline' => setting('site_tagline'),
            'hours' => setting('contact_hours'),
            'whatsapp' => setting('whatsapp_link'),
            'tax' => setting('invoice_tax_number'),
        ];
    }

    /** Fields whose saved copies disagree: field => [place => value]. Saving makes them one. */
    public static function differences(): array
    {
        $out = [];
        foreach (self::COPIES as $field => $copies) {
            $values = collect($copies)->mapWithKeys(fn ($place, $key) => [$place => trim((string) setting($key))])->filter(fn ($v) => $v !== '');
            if ($values->unique()->count() > 1) {
                $out[$field] = $values->all();
            }
        }

        return $out;
    }

    /** @return string[] other texts the new shop name was written into */
    public static function save(array $input): array
    {
        $oldName = trim((string) setting('site_name'));
        $newName = trim((string) $input['name']);
        $input['whatsapp'] = self::whatsappLink($input['whatsapp'] ?? null);

        $writes = [];
        foreach (self::COPIES as $field => $copies) {
            foreach (array_keys($copies) as $key) {
                if (!in_array($key, self::ONLY_IF_ALREADY_USED, true) || filled(setting($key))) {
                    $writes[$key] = trim((string) ($input[$field] ?? ''));
                }
            }
        }
        foreach (self::SINGLE as $field => $key) {
            $writes[$key] = trim((string) ($input[$field] ?? ''));
        }

        $alsoChanged = [];
        if ($oldName !== '' && $oldName !== $newName) {
            $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($oldName, '/') . '(?![\p{L}\p{N}])/u';
            foreach (self::NAME_IN_TEXT as $key) {
                $text = (string) setting($key);
                $replaced = preg_replace($pattern, addcslashes($newName, '\\$'), $text);
                if ($replaced !== null && $replaced !== $text) {
                    $writes[$key] = $replaced;
                    $alsoChanged[] = $key;
                }
            }
        }

        self::write($writes);

        return $alsoChanged;
    }

    /** One upload becomes the logo on the website and on invoices. */
    public static function saveLogo(string $path): void
    {
        self::write(['site_logo' => $path, 'invoice_logo' => $path]);
    }

    public static function write(array $values): void
    {
        Setting::withoutEvents(function () use ($values) {
            foreach ($values as $key => $value) {
                $row = Setting::firstOrNew(['key' => $key]);
                $row->value = $value;
                $row->group ??= 'general';
                $row->save();
            }
        });
        Setting::bust();
    }

    /** Accepts a number or a full link; 017… becomes https://wa.me/88017…. */
    private static function whatsappLink(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, 'http')) {
            return $value;
        }
        $digits = preg_replace('/\D/', '', normalize_digits($value));
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            $digits = '88' . $digits;
        }

        return $digits === '' ? '' : 'https://wa.me/' . $digits;
    }
}
