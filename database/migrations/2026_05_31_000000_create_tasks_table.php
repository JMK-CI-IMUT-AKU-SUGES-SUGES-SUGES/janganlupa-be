<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Jalankan: php artisan migrate
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Foreign key ke tabel users — setiap task dimiliki oleh satu user
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // Hapus task jika user dihapus

            $table->string('title');                  // Judul tugas
            $table->text('description')->nullable();  // Deskripsi tugas (opsional)

            // Status sesuai dengan frontend: 'Belum Selesai' | 'Berjalan' | 'Selesai'
            $table->enum('status', ['Belum Selesai', 'Berjalan', 'Selesai'])
                  ->default('Belum Selesai');

            // Label mata kuliah, contoh: 'Pemweb', 'PBO', 'IMK', 'Bhs. Indo'
            $table->string('label')->nullable();

            // Deadline / tarikh akhir tugas
            $table->date('due_date')->nullable();

            // Progres penyelesaian (0–100 peratus)
            $table->unsignedTinyInteger('progress')->default(0);

            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
