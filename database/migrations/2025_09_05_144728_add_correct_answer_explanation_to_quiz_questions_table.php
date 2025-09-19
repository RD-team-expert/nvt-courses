<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->text('correct_answer_explanation')->nullable()->after('correct_answer');
        });
    }

    public function down()
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('correct_answer_explanation');
        });
    }
};
