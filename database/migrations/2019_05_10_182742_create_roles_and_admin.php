<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolesAndAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // create roles
        $admin = Bouncer::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Administrator'
        ]);

        $moderator = Bouncer::role()->firstOrCreate([
            'name' => 'moderator',
            'title' => 'Moderator'
        ]);

        $user = Bouncer::role()->firstOrCreate([
            'name' => 'user',
            'title' => 'Regular User'
        ]);

        // create first admin
        $firstAdmin = new \App\User;

        $firstAdmin->name = 'Charlie Page';
        $firstAdmin->email = 'charliepage88@gmail.com';
        $firstAdmin->email_verified_at = date('Y-m-d H:i:s');
        $firstAdmin->password = bcrypt('fusion');

        $firstAdmin->save();

        // assign new admin to role
        $firstAdmin->assign('admin');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
