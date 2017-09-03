<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Facebook\Facebook;
use Facebook\Exceptions as facebookExceptions;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';
    protected $loggedIn = false;
    protected $facebookObject=null;
    protected $fbRedirectLoginHelper=null;
    protected $fbOAuthClient=null;
    protected $error = "";
    protected $longLivingAccessToken =null;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->facebookObject=new Facebook([
            'app_id' => getenv('FB_APP_ID'),
            'app_secret' => getenv('FB_APP_SECRET'),
            'default_graph_version' => getenv('FB_DEFAULT_GRAPH_VERSION'),
        ]);
        $this->fbRedirectLoginHelper = $this->facebookObject->getRedirectLoginHelper();
        $this->fbOAuthClient = $this->facebookObject->getOAuth2Client();

    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     */

    public function index(){
        // If logged in redirect to profile
        if(!$this->loggedIn){
            $loginUrl = $this->fbRedirectLoginHelper->getLoginUrl(getenv('FB_CALLBACK'));
            return view('login',[
                'loginUrl'=>$loginUrl,
                'error'=>$this->error
            ]);
        }else{
            return redirect('user');
        }
    }

    /**
     * desc : facebook callback when clicking on login button saves the access token in a session
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function fbCallBack(){
        try {
          $accessToken = $this->fbRedirectLoginHelper->getAccessToken();
            } catch(facebookExceptions\FacebookResponseException $e) {
                // When Graph returns an error
                $this->error = 'Graph returned an error: ' . $e->getMessage();
                return view('login',['error'=>$this->error]);
                exit;
            } catch(facebookExceptions\FacebookSDKException $e) {
                // When validation fails or other local issues
                $this->error = 'Facebook SDK returned an error: ' . $e->getMessage();
                return view('login',['error'=>$this->error]);
                exit;
            }
            $accessToken = $this->validateAccessToken($accessToken);
            $this->longLivingAccessToken = $this->getLongLivingToken($accessToken);
            $_SESSION['fb_long_living_access_token'] = $this->longLivingAccessToken;
            $this->loggedIn = true;
            return redirect('user');
    }

    /**
     * @desc deauth when user removes the app from the facebook
     */
    public function fbDeauthCallBack(){
        $result = $this->parseSignedRequest($_POST['signed_request']);
        User::where('access_token',$_SESSION['fb_long_living_access_token'])->where('fb_id',$result->user_id)->update(array('is_active' => 0));
        session_destroy();
        return redirect('/');
    }

    /**
     * @param $signed_request
     * @return array of user data
     */
    private function parseSignedRequest($signedRequest) {
            list($encoded_sig, $payload) = explode('.', $signedRequest, 2);
            $secret = getenv('FB_APP_SECRET');
            // Use your app secret here

            // decode the data
            $sig = base64_url_decode($encoded_sig);
            $data = json_decode(base64_url_decode($payload), true);

            // confirm the signature
            $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
            if ($sig !== $expected_sig) {
                error_log('Bad Signed JSON signature!');
                return null;
            }

        }
    /**
     * @desc logs a user out and destroy his session
     */
    public function logout(){
        session_destroy();
        return redirect('/');
    }

    /**
     * @param $accessToken object from facebook
     * @desc validates the access token and making sure it's true
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    private function validateAccessToken($accessToken){
        if (! isset($accessToken)) {
            if ($this->fbRedirectLoginHelper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                $this->error.="Error: " . $this->fbRedirectLoginHelper->getError() . "\n";
                $this->error.="Error Code: " . $this->fbRedirectLoginHelper->getErrorCode() . "\n";
                $this->error.="Error Reason: " . $this->fbRedirectLoginHelper->getErrorReason() . "\n";
                $this->error.="Error Description: " . $this->fbRedirectLoginHelper->getErrorDescription() . "\n";
                return view('login',['error'=>$this->error]);
                exit;
            } else {
                header('HTTP/1.0 400 Bad Request');
                $this->error='Bad request';
                return view('login',['error'=>$this->error]);
                exit;
            }
            exit;
        }
        return $accessToken;
    }

    /**
     * @param $accessToken
     * @desc gets Longliving token from facebook
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    private function getLongLivingToken($accessToken)
    {
        if (!$accessToken->isLongLived()) {
            try {
                $accessToken = $this->fbOAuthClient->getLongLivedAccessToken($accessToken);
            } catch (facebookExceptions\FacebookSDKException $e) {
                $this->error="<p>Error getting long-lived access token: " . $this->fbRedirectLoginHelper->getMessage() . "</p>\n\n";
                return view('login',['error'=>$this->error]);
                exit;
            }
        }
        return $accessToken->getValue();
    }

}
