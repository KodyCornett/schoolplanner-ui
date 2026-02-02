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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('canvas_url')->nullable();
            $table->integer('horizon')->default(14);
            $table->integer('soft_cap')->default(4);
            $table->integer('hard_cap')->default(8);
            $table->boolean('skip_weekends')->default(false);
            $table->decimal('busy_weight', 3, 2)->default(1.0);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
