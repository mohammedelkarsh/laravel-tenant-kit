<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('country', 2);
            $table->string('national_id', 32)->nullable();
            $table->string('level', 20);
            $table->string('status', 20)->index();
            $table->boolean('passed')->default(false);
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('extraction_driver', 50)->nullable();
            $table->json('warnings')->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('extracted_fields')->nullable();
            $table->json('internal_meta')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
