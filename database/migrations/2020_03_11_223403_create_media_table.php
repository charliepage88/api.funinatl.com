<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediaTable extends Migration
{
  public function up()
  {
    Schema::table('media', function (Blueprint $table) {
      if (!Schema::hasColumn('media', 'conversions_disk')) {
        $table->string('conversions_disk')->nullable()->after('disk');
      }

      if (!Schema::hasColumn('media', 'uuid')) {
        $table->uuid('uuid')->nullable()->after('model_id');
      }
    });
  }
}
