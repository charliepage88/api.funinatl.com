<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->string('website')->nullable();
            $table->datetime('last_scraped')->nullable();
            $table->string('scrape_url');
            $table->boolean('active')->default(1);

            $table->softDeletes();
            $table->timestamps();

            $table->index([
                'slug',
                'active'
            ], 'providers_indexes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('providers');
    }
}
