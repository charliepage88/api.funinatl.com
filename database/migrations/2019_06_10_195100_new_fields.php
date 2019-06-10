<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NewFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'is_family_friendly')) {
                $table->boolean('is_family_friendly')->default(false)->after('is_sold_out');
            }

            if (!Schema::hasColumn('events', 'source')) {
                $table->string('source')->default('provider')->after('active');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'is_family_friendly')) {
                $table->boolean('is_family_friendly')->default(false)->after('description');
            }
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
