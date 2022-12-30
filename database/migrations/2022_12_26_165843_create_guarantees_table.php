<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guarantees', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("customer_name");
            $table->uuid("worker_id");
            $table->string("finger_print_intelligence"); // بصمة مخابرات
            $table->string("book_work"); //دفتر عمل
            $table->text("note"); //دفتر عمل
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('guarantees');
    }
};