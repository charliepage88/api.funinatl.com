<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EventsIsSoldOut extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_indexes');

            if (!Schema::hasColumn('events', 'is_sold_out')) {
                $table->boolean('is_sold_out')->default(0)->after('active');
            }

            $table->index([
                'slug',
                'location_id',
                'event_type_id',
                'category_id',
                'start_date',
                'end_date',
                'featured',
                'active',
                'is_sold_out'
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
