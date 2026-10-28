<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Custom Invoices: invoices / quotations written by hand, not tied to a storefront
 * order (phone or showroom sales, wholesale quotes, services). Line items are kept as JSON
 * on the row, a snapshot of what was billed, so editing a product later never changes an
 * invoice that was already sent. Creating one never touches stock or the orders table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->string('type', 20)->default('invoice'); // invoice | quotation | proforma | receipt
            $table->string('status', 20)->default('unpaid')->index(); // draft | unpaid | partial | paid | cancelled

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // linked customer account, if any
            $table->string('customer_name');
            $table->string('customer_phone', 40)->nullable()->index();
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();

            $table->date('issue_date');
            $table->date('due_date')->nullable();

            $table->json('items'); // [{product_id, name, description, qty, price, discount}]
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('discount_type', 10)->default('fixed'); // fixed | percent
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0); // the amount actually taken off
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('payment_method', 40)->nullable();

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('accent_color', 9)->nullable(); // per-invoice override of Settings → Invoice

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('issue_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_invoices');
    }
};
