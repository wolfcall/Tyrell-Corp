<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Data\Mappers\RoomMapper;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller {

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/calendar';

    /**
     * Create a new controller instance.
     */
    public function __construct() {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm() {
        return view('login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request) {
        $this->validate($request, [
            'id' => 'required', 'password' => 'required',
        ]);

        if (Auth::attempt($request->only('id', 'password'), $request->only('remember'))) {
            // Authentication passed...
            return redirect()->intended($this->redirectTo);
        }

        return redirect()->back()
                        ->withInput($request->only('id', 'remember'))
                        ->withErrors([
                            'id' => Lang::get('auth.failed'),
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request) {
        $roomMapper = RoomMapper::getInstance();
        $roomMapper->clearStudent(Auth::id());

        if (isset($_SESSION["view"]) && $_SESSION["view"] == true) {

            $_SESSION["timestamp"] = date("Y-m-d G:i:s");
            $_SESSION["user"] = Auth::id();
            unset($_SESSION["view"]);
        }

        $this->guard()->logout();
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect('/');
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard() {
        return Auth::guard();
    }

}
