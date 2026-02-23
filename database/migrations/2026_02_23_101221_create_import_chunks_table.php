<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_number');
            $table->string('file_path');
            $table->string('status')->default('pending');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('duplicates')->default(0);
            $table->unsignedInteger('invalid')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_chunks');
    }
};
