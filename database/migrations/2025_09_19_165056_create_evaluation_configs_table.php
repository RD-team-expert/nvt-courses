<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('evaluation_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('max_score')->unsigned();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluation_configs');
    }
}
