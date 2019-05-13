<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LocationsCategoryId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_indexes');

            if (!Schema::hasColumn('locations', 'category_id')) {
                $table->integer('category_id')->nullable()->after('slug');
            }

            $table->index([
                'slug',
                'category_id'
            ], 'locations_indexes');
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
