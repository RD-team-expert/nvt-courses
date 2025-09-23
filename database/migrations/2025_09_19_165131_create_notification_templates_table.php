<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_templates');
    }
}
