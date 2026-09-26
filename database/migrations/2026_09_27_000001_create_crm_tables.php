<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A CRM "contact" is a PERSON the shop deals with, keyed by phone number — not a user
        // account. Most orders on this store are guest checkouts (no user_id), so building the
        // CRM on the users table alone would miss the majority of real customers. A contact can
        // optionally be linked to a registered account (user_id) once one exists.
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable()->unique();   // normalized 11-digit local form
            $table->string('email')->nullable()->index();
            $table->string('city', 100)->nullable();
            $table->date('birthday')->nullable();
            $table->string('source', 30)->default('manual');     // order | registration | newsletter | lead | contact_form | manual | import
            $table->string('status', 20)->default('active');     // active | blocked | do_not_contact
            $table->boolean('is_vip')->default(false);

            // Denormalized metrics (recomputed by CrmMetrics — see app/Services/Crm)
            $table->string('lifecycle_stage', 20)->default('prospect')->index(); // prospect | new | active | at_risk | lost
            $table->unsignedInteger('orders_count')->default(0);     // valid orders only (not cancelled/refunded)
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('cancelled_count')->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->decimal('avg_order_value', 12, 2)->default(0);
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable()->index();
            $table->unsignedInteger('avg_days_between_orders')->nullable();
            $table->date('predicted_next_order_on')->nullable();
            $table->unsignedTinyInteger('churn_risk')->default(0);   // 0-100

            $table->unsignedTinyInteger('rfm_r')->nullable();
            $table->unsignedTinyInteger('rfm_f')->nullable();
            $table->unsignedTinyInteger('rfm_m')->nullable();
            $table->string('rfm_segment', 30)->nullable()->index();

            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('metrics_refreshed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('total_spent');
            $table->index('city');
        });

        Schema::create('crm_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('color', 20)->default('gray');
            $table->timestamps();
        });

        Schema::create('crm_contact_tag', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('crm_tags')->cascadeOnDelete();
            $table->primary(['contact_id', 'tag_id']);
        });

        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('source', 30)->default('other');       // website | contact_form | phone | whatsapp | facebook | walk_in | referral | other
            $table->string('stage', 20)->default('new')->index(); // new | contacted | qualified | proposal | negotiation | won | lost
            $table->decimal('value', 12, 2)->default(0);
            $table->date('expected_close_on')->nullable();
            $table->text('interest')->nullable();
            $table->string('lost_reason')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamps();

            $table->index(['stage', 'position']);
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // the staff member who logged it
            $table->string('type', 20);                 // note | call | email | sms | whatsapp | meeting | system
            $table->string('direction', 3)->nullable(); // in | out
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('outcome', 30)->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('occurred_at')->useCurrent();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'occurred_at']);
            $table->index(['lead_id', 'occurred_at']);
        });

        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 20)->default('follow_up');   // call | email | whatsapp | meeting | follow_up | other
            $table->string('priority', 10)->default('normal');  // low | normal | high | urgent
            $table->timestamp('due_at')->nullable();
            $table->string('status', 15)->default('open');      // open | done | cancelled
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reminded_at')->nullable();
            $table->boolean('is_auto')->default(false);          // created by the system (e.g. win-back), not a person
            $table->timestamps();

            $table->index(['assigned_to', 'status', 'due_at']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('crm_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 20)->default('indigo');
            $table->json('rules');
            $table->string('match', 3)->default('all');          // all (AND) | any (OR)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('contact_count')->default(0);
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
        });

        // Orders point straight at their CRM contact so every customer query is a plain indexed
        // join instead of re-normalizing free-typed phone numbers on the fly.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('crm_contact_id')->nullable()->after('user_id')->constrained('crm_contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crm_contact_id');
        });
        Schema::dropIfExists('crm_segments');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_contact_tag');
        Schema::dropIfExists('crm_tags');
        Schema::dropIfExists('crm_contacts');
    }
};
