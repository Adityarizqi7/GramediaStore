<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->string('language')->nullable();
            $table->integer('total_pages');
            $table->bigInteger('price');
            $table->bigInteger('sale_price')->nullable();
            $table->bigInteger('sale_percentage')->nullable();
            $table->integer('stock');
            $table->string('status');
            $table->string('type');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unsignedBigInteger('book_id')->index();
            $table->foreign('book_id')->references('id')->on('books');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
