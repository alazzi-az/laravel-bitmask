<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dummy_models', function (Blueprint $table) {
            $table->id();
            $table->integer('permissions')->nullable();
            $table->integer('archive_data_flag')->nullable();
            $table->integer('flags')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dummy_models');
    }
};
