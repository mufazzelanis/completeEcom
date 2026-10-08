<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use XMLWriter;

/**
 * Google Merchant Center product feed (RSS 2.0 + g: namespace), fetched daily by Merchant
 * Center's "scheduled fetch" so prices/stock stay in sync automatically.
 *
 * Every value deliberately mirrors the product page's own Product JSON-LD
 * (products/show.blade.php) — Merchant Center crawls the landing page and disapproves items
 * whose feed price/availability don't match what the page says, so both read the same
 * columns the same way: price + sale_price, schema_availability falling back to stock.
 */
class GoogleFeedController extends Controller
{
    private const AVAILABILITY = [
        'InStock' => 'in_stock', 'OutOfStock' => 'out_of_stock', 'SoldOut' => 'out_of_stock',
        'Discontinued' => 'out_of_stock', 'PreOrder' => 'preorder', 'BackOrder' => 'backorder',
    ];

    public function index()
    {
        $currency = setting('currency_code', 'BDT');
        $shippingRate = ShippingCalculator::calculate(0, ShippingCalculator::ZONE_DHAKA);

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $xml->startElement('channel');
        $xml->writeElement('title', setting('site_name', config('app.name')));
        $xml->writeElement('link', route('home'));
        $xml->writeElement('description', setting('site_name', config('app.name')) . ' product feed');

        Product::active()
            ->with(['brand', 'category', 'subcategory', 'images'])
            ->where('type', '!=', 'digital') // Shopping ads don't accept digital goods
            ->whereNotNull('image')
            ->where('price', '>', 0)
            ->orderBy('id')
            ->chunk(200, function ($products) use ($xml, $currency, $shippingRate) {
                foreach ($products as $product) {
                    $this->writeItem($xml, $product, $currency, $shippingRate);
                }
            });

        $xml->endElement(); // channel
        $xml->endElement(); // rss

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function writeItem(XMLWriter $xml, Product $product, string $currency, float $shippingRate): void
    {
        $price = (float) $product->price;
        $salePrice = $product->sale_price !== null ? (float) $product->sale_price : null;
        $availability = self::AVAILABILITY[$product->schema_availability]
            ?? ($product->stock > 0 ? 'in_stock' : 'out_of_stock');
        $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($product->description ?: $product->short_description))))
            ?: $product->name;

        $xml->startElement('item');
        // The numeric id never changes (SKUs can be edited or left blank) — Merchant Center
        // treats a changed id as a brand-new product and drops its history.
        $xml->writeElement('g:id', (string) $product->id);
        $xml->writeElement('g:title', Str::limit($product->name, 150, ''));
        $xml->writeElement('g:description', Str::limit($description, 5000, ''));
        $xml->writeElement('g:link', $product->canonical_url ?: route('products.show', $product));
        // url(): Storage::url() can be root-relative depending on filesystem config; Google needs absolute.
        $xml->writeElement('g:image_link', url(Storage::url($product->image)));
        foreach ($product->images->take(10) as $image) {
            $xml->writeElement('g:additional_image_link', url(Storage::url($image->image)));
        }
        $xml->writeElement('g:availability', $availability);
        $xml->writeElement('g:price', number_format($price, 2, '.', '') . " {$currency}");
        if ($salePrice !== null && $salePrice < $price) {
            $xml->writeElement('g:sale_price', number_format($salePrice, 2, '.', '') . " {$currency}");
        }
        $xml->writeElement('g:condition', strtolower(str_replace('Condition', '', $product->schema_condition ?: 'NewCondition')));

        if ($product->brand) {
            $xml->writeElement('g:brand', $product->brand->name);
        }
        // identifier_exists=no only when Google truly has nothing to match on — claiming
        // "no identifiers" for a product that has a GTIN gets it disapproved.
        if ($product->gtin) {
            $xml->writeElement('g:gtin', $product->gtin);
        }
        if ($product->mpn) {
            $xml->writeElement('g:mpn', $product->mpn);
        }
        if (!$product->gtin && !($product->mpn && $product->brand)) {
            $xml->writeElement('g:identifier_exists', 'no');
        }

        $productType = collect([$product->category?->name, $product->subcategory?->name])->filter()->implode(' > ');
        if ($productType) {
            $xml->writeElement('g:product_type', $productType);
        }

        $xml->startElement('g:shipping');
        $xml->writeElement('g:country', 'BD');
        $xml->writeElement('g:price', number_format($shippingRate, 2, '.', '') . " {$currency}");
        $xml->endElement();

        $xml->endElement(); // item
    }
}
