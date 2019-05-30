<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\User;
use App\Http\Controllers\Controller;

use Bouncer;

class UsersController extends Controller
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
        $users = User::orderBy('created_at', 'desc')->paginate(15);

        return view('admin.users.index', compact('users'));
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
        $user = new User;

        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required',
                'password' => 'required',
                'email' => 'required|max:255|unique:users,email',
                'role' => 'required'
            ]);

            $user->fill($request->except('password', 'role'));

            $user->password = bcrypt($request->password);

            $user->save();

            if ($request->has('role')) {
                $user->assign($request->role);
            }

            return redirect(route('admin.users.index'))->with('is-success', 'User has been created!');
        }

        $roles = Bouncer::role()->pluck('title', 'name');

        return view('admin.users.create', compact('user', 'roles'));
    }

    /**
    * Edit
    *
    * @param Request $request
    * @param User $user
    *
    * @return Redirect|Response
    */
    public function edit(Request $request, User $user)
    {
        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required',
                'email' => 'required|max:255|unique:users,email,' . $user->id . ',id',
                'role' => 'required'
            ]);

            $user->fill($request->except('password', 'role'));

            if ($request->has('password')) {
                $user->password = bcrypt($request->password);
            }

            $user->save();

            if ($request->has('role')) {
                Bouncer::sync($user)->roles([ $request->role ]);
            }

            return redirect(route('admin.users.index'))->with('is-success', 'User has been saved!');
        }

        $roles = Bouncer::role()->pluck('title', 'name');

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
    * Destroy
    *
    * @param User $user
    *
    * @return Redirect
    */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect(route('admin.user.index'))->with('is-success', 'User has been deleted!');
    }
}
