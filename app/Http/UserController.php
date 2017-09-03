<?php

namespace App\Http\Controllers;
use App\User;
use Facebook\Exceptions as facebookExceptions;
use Facebook\Facebook;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles user profile for the application and
    | redirecting them to your login if there is no access token .
    |
    */


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $facebookObject=null;
    protected $accessToken=null;
    protected $userProfile = null;
    protected $error = null;

    public function __construct()
    {

        if(!isset($_SESSION['fb_long_living_access_token'])){
            return redirect('user');
        }else{
            $this->facebookObject=new Facebook([
                'app_id' => getenv('FB_APP_ID'),
                'app_secret' => getenv('FB_APP_SECRET'),
                'default_graph_version' => getenv('FB_DEFAULT_GRAPH_VERSION'),
            ]);
            $this->accessToken = $_SESSION['fb_long_living_access_token'];
        }
    }

    /**
     * @desc gets user profile from facebook
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     */

    public function index(){
        $this->userProfile  = $this->getUserProfile();
        $profileData['name']=$this->userProfile['name'];
        $profileData['picture']=$this->userProfile['picture']['url'];
        $this->saveUser($profileData);
        return view('profile',['userProfile'=>$profileData]);
    }

    /**
     * @desc save user to database
     * @param $profileData
     */
    private function saveUser($profileData){
        $userModal=User::where('fb_id',$this->userProfile['id'])->where('is_active',1)->first();
        if($userModal==null){
            $userModal = new User();
        }
        $userData =  array();
        $userData['fb_id'] = $this->userProfile['id'];
        $userData['name'] = $this->userProfile['name'];
        $userData['profile_picture'] = $profileData['picture'];
        $userData['is_active'] = 1;
        $userData['access_token'] = $_SESSION['fb_long_living_access_token'];
        $userModal->fill($userData)->save();
    }

    /**
     * @desc connects to facebook and get user data
     * @return \Facebook\FacebookResponse|\Facebook\GraphNodes\GraphUser|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    private function getUserProfile(){
        try{
            $userProfile = $this->facebookObject->get('/me?fields=id,name,picture', $this->accessToken);
            $userProfile = $userProfile->getGraphUser();
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
        } catch(facebookExceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $this->error='Graph returned an error: ' . $e->getMessage();
            return view('login',['error'=>$this->error]);
            exit;
        } catch(facebookExceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $this->error='Facebook SDK returned an error: ' . $e->getMessage();
            return view('login',['error'=>$this->error]);
            exit;
        }
        return $userProfile;
    }

}
