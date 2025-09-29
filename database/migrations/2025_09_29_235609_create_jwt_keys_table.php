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
        Schema::create('jwt_keys', function (Blueprint $table) {
            $table->id();
            $table->string('kid', 64)->unique();
            $table->text('public_pem');
            $table->longText('private_pem_encrypted');
            $table->boolean('active')->default(false)->index();
            $table->timestamp('deprecates_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jwt_keys');
    }
};
