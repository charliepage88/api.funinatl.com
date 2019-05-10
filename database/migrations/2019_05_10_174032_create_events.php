<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->integer('location_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->date('date');
            $table->string('price')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->boolean('featured')->default(0);
            $table->boolean('active')->default(1);
            $table->string('website')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index([
                'slug',
                'location_id',
                'user_id',
                'date',
                'featured',
                'active'
            ], 'events_indexes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
