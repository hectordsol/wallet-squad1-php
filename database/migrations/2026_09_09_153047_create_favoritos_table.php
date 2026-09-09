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
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id('id_favorito');
            $table->foreignId('id_cuenta')
                ->constrained('cuentas', 'id_cuenta')
                ->cascadeOnDelete();
            $table->string('cbu_favorito', 22);
            $table->foreign('cbu_favorito')
                ->references('cbu')
                ->on('cuentas')
                ->restrictOnDelete();
            $table->unique(['id_cuenta', 'cbu_favorito']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
