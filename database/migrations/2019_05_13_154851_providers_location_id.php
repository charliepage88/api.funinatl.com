<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProvidersLocationId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'website')) {
                $table->dropColumn('website');
            }

            if (!Schema::hasColumn('providers', 'location_id')) {
                $table->integer('location_id')->nullable()->after('slug');
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
