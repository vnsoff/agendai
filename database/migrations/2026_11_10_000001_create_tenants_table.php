<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->timestamps();
        });

        // Every user belongs to exactly one business. Email stays globally unique, so one
        // login always resolves to one tenant without asking "which business?".
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('role')->after('email')->default('staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('tenants');
    }
};
