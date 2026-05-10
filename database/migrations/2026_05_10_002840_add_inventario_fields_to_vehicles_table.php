<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('inventario_dtb')->nullable()->after('id')->index();
            $table->string('linea', 100)->nullable()->after('modelo');
            $table->unsignedSmallInteger('cilindraje')->nullable()->after('engine_number')->comment('cm³');
            $table->string('organismo_transito', 150)->nullable()->after('cilindraje');
            $table->string('peso_bruto', 30)->nullable()->after('organismo_transito');
            $table->string('peso_neto', 30)->nullable()->after('peso_bruto');
            $table->string('ubicacion_fisica', 150)->nullable()->after('peso_neto');
            $table->string('servicio', 30)->nullable()->after('ubicacion_fisica')->index();
            $table->unsignedInteger('tiempo_inmovilizacion_dias')->nullable()->after('servicio');
            $table->string('causal_inmovilizacion', 100)->nullable()->after('tiempo_inmovilizacion_dias');
            $table->date('fecha_ingreso')->nullable()->after('causal_inmovilizacion')->index();
            $table->date('fecha_notificacion')->nullable()->after('fecha_ingreso');
            $table->string('resolucion', 100)->nullable()->after('fecha_notificacion');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'inventario_dtb',
                'linea',
                'cilindraje',
                'organismo_transito',
                'peso_bruto',
                'peso_neto',
                'ubicacion_fisica',
                'servicio',
                'tiempo_inmovilizacion_dias',
                'causal_inmovilizacion',
                'fecha_ingreso',
                'fecha_notificacion',
                'resolucion',
            ]);
        });
    }
};
