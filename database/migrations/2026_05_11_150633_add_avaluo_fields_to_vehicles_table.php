<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('ficha_numero', 20)->nullable()->unique()->after('inventario_dtb');
            $table->date('fecha_inspeccion')->nullable()->after('fecha_notificacion');
            $table->string('aviso_prensa', 150)->nullable()->after('fecha_inspeccion');
            $table->string('condicion_bien', 30)->nullable()->after('aviso_prensa')->index();
            $table->unsignedSmallInteger('tiempo_vida_util_anios')->nullable()->after('condicion_bien');
            $table->text('estado_fisico')->nullable()->after('tiempo_vida_util_anios');
            $table->decimal('valor_economico', 15, 2)->nullable()->after('estado_fisico');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'ficha_numero',
                'fecha_inspeccion',
                'aviso_prensa',
                'condicion_bien',
                'tiempo_vida_util_anios',
                'estado_fisico',
                'valor_economico',
            ]);
        });
    }
};
