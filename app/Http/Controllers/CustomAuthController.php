<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomAuthController extends Controller
{

    /**
     * It takes to login page
     * @author Ahsan Qureshi <ahsanqureshi1603@gmail.com>
     * 
     * @return view
     */

    public function index()
    {
        return view('auth.login');
    }

    /**
     * 
     * Custom login for user
     * @author Ahsan Qureshi <ahsanqureshi1603@gmail.com>
     * 
     * @param  Request      $request
     * @param  string       $request->email         : User email
     * @param  string       $request->password      : User Password
     *  
     * @redirect /dashboard
     */

    public function customLogin(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            return redirect()->intended('dashboard')
                ->withSuccess('Signed in');
        }

        return redirect("login")->withSuccess('Login details are not valid');
    }

    /**
     * Takes to Register page
     * Can create new user or the page
     * @author Ahsan Qureshi <ahsanqureshi1603@gmail.com>
     * 
     * @return view
     */

    public function registration()
    {
        return view('auth.registration');
    }

    /**
     * Creates new user
     * 
     * @author Ahsan Qureshi <ahsanqureshi1603@gmail.com>
     * 
     * @param  Request      $request
     * @param  string       $request->email         : User email
     * @param  string       $request->fname         : User fname
     * @param  string       $request->lname         : User lname
     * @param  string       $request->password      : User Password
     *  
     * @redirect /dashboard
     */

    public function customRegistration(Request $request)
    {
        $request->validate([
            'fname' => 'required',
            'lname' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $data = $request->all();
        $check = $this->create($data);
        return redirect("dashboard")->withSuccess('You have signed-in');
    }

    /**
     * Creates new user
     * 
     * @author Ahsan Qureshi <ahsanqureshi1603@gmail.com>
     * 
     * @param  array   $data     : new user data
     * 
     * @return User
     */

    public function create(array $data)
    {
        return User::create([
            'fname' => $data['fname'],
            'lname' => $data['lname'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);
    }

    /**
     * Takes auth user to Dashboard
     * 
     * @author Ahsan Qureshi <ahsanqureshi1603@gmail.com>
     * 
     * @redirect /dashboard
     */

    public function dashboard()
    {
        if (Auth::check()) {
            return view('dashboard');
        }

        return redirect("login")->withSuccess('You are not allowed to access');
    }

    /**
     * Clears user auth session
     * Logs out user
     * 
     * @author Ahsan Qureshi <ahsanqureshi1603@gmail.com>
     * 
     * @redirect /login
     */
    
    public function signOut()
    {
        Session::flush();
        Auth::logout();
        return redirect('login');
    }
}
