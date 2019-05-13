<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EventTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->index([
                'slug'
            ], 'event_types_indexes');
        });

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'event_type_id')) {
                $table->integer('event_type_id')->default(1)->after('category_id');
            }

            $table->dropIndex('events_indexes');

            $table->index([
                'slug',
                'location_id',
                'user_id',
                'event_type_id',
                'category_id',
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
        //
    }
}
