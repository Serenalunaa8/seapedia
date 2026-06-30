<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_reviews', function (Blueprint $table) {
            $table->id();
            // nullable: guest tanpa akun boleh submit review aplikasi
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reviewer_name');
            $table->unsignedTinyInteger('rating'); // 1-5, divalidasi di FormRequest
            $table->text('comment'); // render dengan escaping di frontend (cegah XSS)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_reviews');
    }
};