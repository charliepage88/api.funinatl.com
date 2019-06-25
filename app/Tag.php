<?php

namespace App;

use Spatie\Tags\Tag as ParentModel;

use DB;

class Tag extends ParentModel
{
    /**
    * Related Count
    *
    * @return integer
    */
    public function relatedCount()
    {
        $count = DB::table('taggables')->where('tag_id', $this->id)->count();

        return $count;
    }
}
