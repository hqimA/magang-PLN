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
        Schema::create('rincian_sparepart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_riwayat_servis')->constrained('riwayat_servis')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama_sparepart', 150);
            $table->integer('jumlah')->default(1);
            $table->decimal('harga_satuan', 12, 2);
            $table->decimal('subtotal', 12, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rincian_sparepart');
    }
};
