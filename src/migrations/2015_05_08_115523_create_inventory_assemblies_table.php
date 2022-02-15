<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryAssembliesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('inventory_assemblies', function (Blueprint $table) {

            $table->id();
            $table->timestamps();
            $table->foreignId('inventory_id')->unsigned();
            $table->foreignId('part_id')->unsigned();
            $table->integer('quantity')->nullable();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('part_id')->references('id')->on('inventories')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('inventory_assemblies');
    }
}
