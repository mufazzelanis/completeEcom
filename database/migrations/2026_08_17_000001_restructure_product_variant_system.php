<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the old "colors" and "variants" as two independent stock pools
     * with a real combination matrix: every purchasable color x size pairing
     * becomes its own row with its own sku/price/stock. See
     * product_variant_combinations below.
     */
    public function up(): void
    {
        // Snapshot the old data before altering anything, so we can carry it
        // forward into the new combinations table further down.
        $oldVariants = DB::table('product_variants')->get();
        $oldColors = DB::table('product_colors')->get();

        Schema::rename('product_variants', 'product_sizes');
        Schema::table('product_sizes', function (Blueprint $table) {
            $table->dropColumn(['sku', 'price', 'stock']);
        });

        Schema::table('product_colors', function (Blueprint $table) {
            $table->dropColumn('stock');
        });

        Schema::create('product_variant_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_color_id')->nullable()->constrained('product_colors')->onDelete('cascade');
            $table->foreignId('product_size_id')->nullable()->constrained('product_sizes')->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // null = use product base price
            $table->integer('stock')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->migrateExistingData($oldVariants, $oldColors);
    }

    /**
     * Carries forward any pre-existing color/size rows into the new combinations
     * table. A product that only ever had one of the two attributes maps cleanly
     * (one combination per row, same stock). A product that had BOTH colors and
     * sizes previously kept two unrelated stock pools, so there is no way to know
     * how that stock actually splits across pairs — those get the full color x
     * size grid at stock 0, left for an admin to fill in for real.
     */
    private function migrateExistingData($oldVariants, $oldColors): void
    {
        $variantsByProduct = $oldVariants->groupBy('product_id');
        $colorsByProduct = $oldColors->groupBy('product_id');
        $productIds = $variantsByProduct->keys()->merge($colorsByProduct->keys())->unique();

        foreach ($productIds as $productId) {
            $sizes = $variantsByProduct->get($productId, collect());
            $colors = $colorsByProduct->get($productId, collect());
            $now = now();
            $rows = [];

            if ($colors->isNotEmpty() && $sizes->isNotEmpty()) {
                foreach ($colors as $color) {
                    foreach ($sizes as $size) {
                        $rows[] = [
                            'product_id' => $productId,
                            'product_color_id' => $color->id,
                            'product_size_id' => $size->id,
                            'sku' => null,
                            'price' => null,
                            'stock' => 0,
                            'sort_order' => 0,
                            'is_active' => $color->is_active && $size->is_active,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            } elseif ($colors->isNotEmpty()) {
                foreach ($colors as $i => $color) {
                    $rows[] = [
                        'product_id' => $productId,
                        'product_color_id' => $color->id,
                        'product_size_id' => null,
                        'sku' => null,
                        'price' => null,
                        'stock' => (int) ($color->stock ?? 0),
                        'sort_order' => $i,
                        'is_active' => $color->is_active,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            } elseif ($sizes->isNotEmpty()) {
                foreach ($sizes as $i => $size) {
                    $rows[] = [
                        'product_id' => $productId,
                        'product_color_id' => null,
                        'product_size_id' => $size->id,
                        'sku' => $size->sku,
                        'price' => $size->price,
                        'stock' => (int) ($size->stock ?? 0),
                        'sort_order' => $i,
                        'is_active' => $size->is_active,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (empty($rows)) {
                continue;
            }

            DB::table('product_variant_combinations')->insert($rows);

            $totalStock = collect($rows)->where('is_active', true)->sum('stock');
            DB::table('products')->where('id', $productId)->update(['stock' => $totalStock]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_combinations');

        Schema::table('product_colors', function (Blueprint $table) {
            $table->integer('stock')->nullable()->after('image');
        });

        Schema::table('product_sizes', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
            $table->decimal('price', 10, 2)->nullable()->after('sku');
            $table->integer('stock')->default(0)->after('price');
        });
        Schema::rename('product_sizes', 'product_variants');
    }
};
