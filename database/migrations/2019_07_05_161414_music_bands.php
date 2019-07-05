<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MusicBands extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('music_bands', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->string('spotify_artist_id')->nullable();
            $table->json('spotify_json')->nullable();
            $table->timestamps();
        });

        Schema::create('event_music_bands', function (Blueprint $table) {
            $table->integer('event_id');
            $table->integer('music_band_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('music_bands');
        Schema::dropIfExists('event_music_bands');
    }
}
