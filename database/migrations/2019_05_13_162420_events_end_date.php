<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EventsEndDate extends Migration
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

            if (Schema::hasColumn('events', 'date')) {
                $table->renameColumn('date', 'start_date');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            $table->index([
                'slug',
                'location_id',
                'event_type_id',
                'category_id',
                'start_date',
                'end_date',
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
