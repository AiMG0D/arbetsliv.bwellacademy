<?php

namespace App\Http\Controllers;

use App\User;
use App\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function getComplete(Request $request)
    {
        $id = $request->input('id');
        $code = $request->input('code');

        $user = User::findOrFail($id);
        if ($user->registration_code !== $code) {
            abort(404);
        }

        $data = [
            'id' => $id,
            'code' => $code,
            'user' => $user,
        ];

        return view('auth.register', $data);
    }

    public function getRegister(Request $request)
    {
        $id = $request->input('id');

        $section = Section::findOrFail($id);

        $data = [
            'section' => $section,
            'id' => $id,
        ];

        return view('auth.register-new', $data);
    }

    public function postRegister(Request $request)
    {

        $rules = [
            'email' => 'required|email',
            'first_name' => 'required',
            'last_name' => 'required',
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
            'secret_code' => 'required',
            'id' => 'required',
            'birth_date' =>'required|date',
            'sex' => 'required|in:M,F',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $id = $request->input('id');
        $section = Section::findOrFail($id);

        if ($section->secret_code != $request->input('secret_code')) {
            return back()->withErrors(['secret_code' => 'Secret Code is incorrect.'])->withInput();
        }

        $email = $request->input('email');

        $exists = User::where('email', $email)->count() > 0;
        if ($exists) {
            return back()->withErrors(['email' => 'User already exists with this email.'])->withInput();
        }

        $user = new User;

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $email;
        $user->sex = $request->input('sex');
        $user->birth_date = \Carbon\Carbon::parse($request->input('birth_date'));
        $user->section_id = $id;
        $user->password = Hash::make($request->input('password'));
        $user->qr_signup = true;
        $user->save();

        return redirect(url('/'));

    }


    public function postComplete(Request $request)
    {
        $id = $request->input('id');
        $code = $request->input('code');

        $user = User::findOrFail($id);
        if ($user->registration_code !== $code) {
            abort(404);
        }

        $rules = [
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $user->registration_code = '';
        $user->password = Hash::make($request->input('password'));
        $user->save();

        if (Auth::attempt(['email' => $user->email, 'password' => $request->input('password')], true)) {
            Session::flush();
        }

        return redirect(url('/'));
    }
}
