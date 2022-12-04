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
        Schema::create('imports', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("customer_name");
            $table->uuid("worker_id");
            $table->integer("request_type"); // 0 daily 1 weekly 2 monthly 3 yearly
            $table->double("price"); // السعر الكلي
            $table->double("remainder_price"); // السعر الباقي
            $table->double("received_price"); // السعر المتبقي 
            $table->integer("status"); // 1طلب عند الزبون0   طلب راجع     
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
        Schema::dropIfExists('imports');
    }
};