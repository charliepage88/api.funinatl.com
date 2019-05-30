<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Category;
use App\Http\Controllers\Controller;

use DB;

class CategoriesController extends Controller
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
        $categories = Category::orderBy('name', 'asc')->paginate(15);

        return view('admin.categories.index', compact('categories'));
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
        $category = new Category;

        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255|unique:categories,name'
            ]);

            $category->fill($request->all());

            $category->save();

            if ($category->is_default) {
                DB::table('categories')->where('id', '!=', $category->id)
                    ->update([ 'is_default' => 0 ]);
            }

            return redirect(route('admin.categories.index'))->with('is-success', 'Category has been created!');
        }

        return view('admin.categories.create', compact('category'));
    }

    /**
    * Edit
    *
    * @param Request $request
    * @param Category $category
    *
    * @return Redirect|Response
    */
    public function edit(Request $request, Category $category)
    {
        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255|unique:categories,name,' . $category->id . ',id'
            ]);

            $category->fill($request->all());

            $category->save();

            if ($category->is_default) {
                DB::table('categories')->where('id', '!=', $category->id)
                    ->update([ 'is_default' => 0 ]);
            }

            return redirect(route('admin.categories.index'))->with('is-success', 'Category has been saved!');
        }

        return view('admin.categories.edit', compact('category'));
    }

    /**
    * Destroy
    *
    * @param Category $category
    *
    * @return Redirect
    */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect(route('admin.category.index'))->with('is-success', 'Category has been deleted!');
    }
}
