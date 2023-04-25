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
        Schema::create('tmp_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uid');
            $table->string('type');
            $table->json('transaction');
            $table->float('tip', 10, 5);
            $table->bigInteger('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_transactions');
    }
};
