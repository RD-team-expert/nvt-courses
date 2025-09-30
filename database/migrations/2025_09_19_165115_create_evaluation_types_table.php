<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationTypesTable extends Migration
{
    public function up()
    {
        Schema::create('evaluation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_config_id')->constrained()->onDelete('cascade');
            $table->string('type_name');
            $table->integer('score_value')->unsigned();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_types');
    }
}
