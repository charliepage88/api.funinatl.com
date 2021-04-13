<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Tag;
use App\Http\Controllers\Controller;

class TagsController extends Controller
{
    /**
    * Index
    *
    * @param Request $request
    *
    * @return Response
    */
    public function index(Request $request)
    {
        $tags = Tag::ordered()->paginate(15);

        return view('admin.tags.index', compact('tags'));
    }

    /**
    * Create
    *
    * @param Request $request
    *
    * @return Redirect|Response
    */
    public function create(Request $request)
    {
        $tag = new Tag;

        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255'
            ]);

            $tag->findOrCreateFromString($request->input('name'));

            return redirect(route('admin.tags.index'))->with('is-success', 'Tag has been created!');
        }

        return view('admin.tags.create', compact('tag'));
    }

    /**
    * Edit
    *
    * @param Request $request
    * @param Tag $tag
    *
    * @return Redirect|Response
    */
    public function edit(Request $request, Tag $tag)
    {
        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255'
            ]);

            $tag->name = $request->input('name');

            $tag->save();

            return redirect(route('admin.tags.index'))->with('is-success', 'Tag has been saved!');
        }

        return view('admin.tags.edit', compact('tag'));
    }

    /**
    * Destroy
    *
    * @param Tag $tag
    *
    * @return Redirect
    */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect(route('admin.tags.index'))->with('is-success', 'Tag has been deleted!');
    }
}
