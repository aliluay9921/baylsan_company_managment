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
        Schema::create('workers', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("full_name");
            $table->string("passport_no");
            $table->string("nationality");
            $table->integer("age");
            $table->integer("status"); // 0 available 1 rented
            $table->date("date_entry");
            $table->date("date_issuance_visa");
            $table->softDeletes();

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
        Schema::dropIfExists('workers');
    }
};