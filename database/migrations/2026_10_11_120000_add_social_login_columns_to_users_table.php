<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // One nullable column per provider (not a generic provider/provider_id pair) —
            // an account can arrive via email/password and later also link Google, or sign
            // up with Google first and later add Facebook too, so a user can hold more than
            // one provider id at once. Each is unique so the same Google/Facebook/LinkedIn
            // account can't end up linked to two different users.
            $table->string('google_id')->nullable()->unique()->after('password');
            $table->string('facebook_id')->nullable()->unique()->after('google_id');
            $table->string('linkedin_id')->nullable()->unique()->after('facebook_id');
            $table->string('avatar_url')->nullable()->after('linkedin_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'facebook_id', 'linkedin_id', 'avatar_url']);
        });
    }
};
